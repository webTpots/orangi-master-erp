<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\MarketplaceAccount;
use App\Models\Order;
use App\Models\Settlement;
use App\Models\Sku;
use App\Models\SkuMapping;
use App\Services\FlipkartPdfParser;
use App\Services\FlipkartSettlementParser;
use App\Services\LabelImportService;
use App\Services\SettlementService;
use Illuminate\Http\Request;

class FlipkartController extends Controller
{
    public function __construct(
        private LabelImportService $labelImportService,
        private SettlementService $settlementService,
    ) {}

    /**
     * Flipkart dashboard with KPIs and recent activity.
     */
    public function dashboard()
    {
        $companyId = auth()->user()->company_id;
        $flipkart = Marketplace::where('code', 'flipkart')->first();

        if (! $flipkart) {
            abort(404, 'Flipkart marketplace not configured.');
        }

        $flipkartAccountIds = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace_id', $flipkart->id)
            ->pluck('id');

        // KPIs
        $totalOrders = Order::where('company_id', $companyId)
            ->where('marketplace_id', $flipkart->id)
            ->count();

        $pendingDispatch = Order::where('company_id', $companyId)
            ->where('marketplace_id', $flipkart->id)
            ->whereIn('status', ['new', 'accepted', 'label_ready', 'picking'])
            ->count();

        $returnOrders = Order::where('company_id', $companyId)
            ->where('marketplace_id', $flipkart->id)
            ->whereIn('status', ['rto', 'return'])
            ->count();

        $totalSettlementValue = Settlement::where('company_id', $companyId)
            ->whereIn('marketplace_account_id', $flipkartAccountIds)
            ->sum('net_payable');

        $kpis = [
            'total_orders'     => $totalOrders,
            'pending_dispatch' => $pendingDispatch,
            'returns'          => $returnOrders,
            'settlement_value' => $totalSettlementValue,
        ];

        // Recent orders
        $recentOrders = Order::where('company_id', $companyId)
            ->where('marketplace_id', $flipkart->id)
            ->with('subOrders')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Settlement summary
        $recentSettlements = Settlement::where('company_id', $companyId)
            ->whereIn('marketplace_account_id', $flipkartAccountIds)
            ->orderByDesc('settlement_date')
            ->limit(5)
            ->get();

        // Unmapped SKUs count
        $unmappedCount = $this->getUnmappedSkuCount($companyId, $flipkart->id);

        return view('admin.flipkart.dashboard', compact(
            'kpis', 'recentOrders', 'recentSettlements', 'flipkart', 'unmappedCount'
        ));
    }

    /**
     * Flipkart SKU mapping page.
     */
    public function skuMapping()
    {
        $companyId = auth()->user()->company_id;
        $flipkart = Marketplace::where('code', 'flipkart')->firstOrFail();

        $flipkartAccount = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace_id', $flipkart->id)
            ->first();

        // Existing mappings
        $mappings = SkuMapping::where('company_id', $companyId)
            ->where('source_type', 'marketplace')
            ->where('source_id', $flipkartAccount?->id)
            ->with('sku.variant.product.design')
            ->orderByDesc('created_at')
            ->paginate(25);

        // Internal SKUs for mapping dropdown
        $internalSkus = Sku::where('company_id', $companyId)
            ->with('variant.product.design')
            ->get();

        // Unmapped Flipkart SKUs (from orders that have no mapping)
        $unmappedSkus = collect();

        return view('admin.flipkart.sku-mapping', compact(
            'mappings', 'internalSkus', 'unmappedSkus', 'flipkart'
        ));
    }

    /**
     * Store a new Flipkart SKU mapping.
     */
    public function storeMapping(Request $request)
    {
        $request->validate([
            'external_identifier' => 'required|string|max:255',
            'sku_id'              => 'required|exists:skus,id',
            'external_name'       => 'nullable|string|max:255',
        ]);

        $companyId = auth()->user()->company_id;
        $flipkart = Marketplace::where('code', 'flipkart')->firstOrFail();

        $flipkartAccount = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace_id', $flipkart->id)
            ->first();

        // Check for duplicate mapping
        $existing = SkuMapping::where('company_id', $companyId)
            ->where('source_type', 'marketplace')
            ->where('source_id', $flipkartAccount?->id)
            ->where('external_identifier', $request->external_identifier)
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'This Flipkart SKU is already mapped.');
        }

        SkuMapping::create([
            'company_id'          => $companyId,
            'sku_id'              => $request->sku_id,
            'external_identifier' => $request->external_identifier,
            'source_type'         => 'marketplace',
            'source_id'           => $flipkartAccount?->id,
            'confidence_score'    => 1.00,
            'mapping_status'      => 'confirmed',
            'mapped_by'           => auth()->id(),
        ]);

        return redirect()->route('admin.flipkart.sku-mapping')
            ->with('success', 'Flipkart SKU mapping created successfully.');
    }

    /**
     * Flipkart label import page.
     */
    public function importLabels()
    {
        $companyId = auth()->user()->company_id;
        $flipkart = Marketplace::where('code', 'flipkart')->firstOrFail();

        return view('admin.flipkart.import-labels', compact('flipkart'));
    }

    /**
     * Process Flipkart label PDF upload.
     */
    public function processLabels(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:20480',
        ]);

        $companyId = auth()->user()->company_id;
        $flipkart = Marketplace::where('code', 'flipkart')->firstOrFail();

        $filePath = $request->file('pdf_file')->store('labels', 'local');

        try {
            $batch = $this->labelImportService->importPdf($filePath, $companyId, $flipkart->id);

            return redirect()->route('admin.labels.index')
                ->with('success', "Flipkart labels imported. {$batch->successful_records} orders linked from {$batch->total_records} pages.");
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Flipkart settlement import page.
     */
    public function importSettlement()
    {
        $companyId = auth()->user()->company_id;
        $flipkart = Marketplace::where('code', 'flipkart')->firstOrFail();

        $marketplaceAccounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace_id', $flipkart->id)
            ->get();

        return view('admin.flipkart.import-settlement', compact('flipkart', 'marketplaceAccounts'));
    }

    /**
     * Process Flipkart settlement CSV upload.
     */
    public function processSettlement(Request $request)
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
                ->with('success', "Flipkart settlement imported. {$settlement->total_orders} order lines found.");
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ── Private Helpers ─────────────────────────────────────────

    private function getUnmappedSkuCount(int $companyId, int $marketplaceId): int
    {
        return 0; // Will be calculated when orders exist
    }
}
