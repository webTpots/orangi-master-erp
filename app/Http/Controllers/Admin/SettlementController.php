<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\Order;
use App\Models\Settlement;
use App\Models\SettlementLine;
use App\Services\SettlementService;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function __construct(
        private SettlementService $settlementService,
    ) {}

    /**
     * List settlements with filters and KPI cards.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = Settlement::where('company_id', $companyId)
            ->with('marketplaceAccount.marketplace');

        // Filters
        if ($marketplaceAccountId = $request->get('marketplace_account_id')) {
            $query->where('marketplace_account_id', $marketplaceAccountId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($fromDate = $request->get('from_date')) {
            $query->where('settlement_date', '>=', $fromDate);
        }

        if ($toDate = $request->get('to_date')) {
            $query->where('settlement_date', '<=', $toDate);
        }

        $settlements = $query->orderByDesc('settlement_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        // KPIs
        $kpis = [
            'total_settlements'      => Settlement::where('company_id', $companyId)->count(),
            'total_net_payable'      => Settlement::where('company_id', $companyId)->sum('net_payable'),
            'matched_percentage'     => $this->getOverallMatchPercentage($companyId),
            'pending_reconciliation' => Settlement::where('company_id', $companyId)
                ->whereIn('status', ['imported', 'partially_reconciled'])
                ->count(),
        ];

        $marketplaceAccounts = MarketplaceAccount::where('company_id', $companyId)
            ->with('marketplace')
            ->get();

        return view('admin.settlements.index', compact('settlements', 'kpis', 'marketplaceAccounts'));
    }

    /**
     * Settlement detail with line items and reconciliation status.
     */
    public function show(Settlement $settlement)
    {
        $this->authorizeCompany($settlement);

        $settlement->load(['marketplaceAccount.marketplace', 'lines.order', 'payments']);

        $summary = $this->settlementService->getReconciliationSummary($settlement);

        return view('admin.settlements.show', compact('settlement', 'summary'));
    }

    /**
     * Upload settlement file form.
     */
    public function upload()
    {
        $companyId = auth()->user()->company_id;

        $marketplaceAccounts = MarketplaceAccount::where('company_id', $companyId)
            ->with('marketplace')
            ->get();

        return view('admin.settlements.upload', compact('marketplaceAccounts'));
    }

    /**
     * Process uploaded settlement file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'marketplace_account_id' => 'required|exists:marketplace_accounts,id',
            'file'                   => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $account = MarketplaceAccount::findOrFail($request->marketplace_account_id);

        if ($account->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $filePath = $request->file('file')->store('settlements', 'local');
        $fullPath = storage_path('app/' . $filePath);

        try {
            $settlement = $this->settlementService->importSettlementFile($account, $fullPath);

            return redirect()->route('admin.settlements.show', $settlement)
                ->with('success', "Settlement imported successfully. {$settlement->total_orders} order lines found.");
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Trigger auto-reconciliation for a settlement.
     */
    public function reconcile(Settlement $settlement)
    {
        $this->authorizeCompany($settlement);

        $result = $this->settlementService->reconcileSettlement($settlement);

        return redirect()->route('admin.settlements.show', $settlement)
            ->with('success', "Reconciliation complete. {$result['matched']} matched, {$result['unmatched']} unmatched out of {$result['total']} lines.");
    }

    /**
     * Manually match a settlement line to an order.
     */
    public function matchLine(Request $request, SettlementLine $line)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        if ($order->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $this->settlementService->markLineMatched($line, $order);

        return redirect()->back()->with('success', 'Line matched to order successfully.');
    }

    /**
     * Flag a settlement line as disputed.
     */
    public function disputeLine(Request $request, SettlementLine $line)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->settlementService->markLineDisputed($line, $request->reason);

        return redirect()->back()->with('success', 'Line flagged as disputed.');
    }

    /**
     * Close/finalize a settlement.
     */
    public function close(Settlement $settlement)
    {
        $this->authorizeCompany($settlement);

        $this->settlementService->closeSettlement($settlement);

        return redirect()->route('admin.settlements.show', $settlement)
            ->with('success', 'Settlement has been closed.');
    }

    // ── Private Helpers ─────────────────────────────────────────

    private function authorizeCompany(Settlement $settlement): void
    {
        if ($settlement->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }

    private function getOverallMatchPercentage(int $companyId): float
    {
        $totalLines = SettlementLine::whereHas('settlement', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->count();

        if ($totalLines === 0) {
            return 0;
        }

        $matchedLines = SettlementLine::whereHas('settlement', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->where('match_status', SettlementLine::MATCH_MATCHED)->count();

        return round($matchedLines / $totalLines * 100, 1);
    }
}
