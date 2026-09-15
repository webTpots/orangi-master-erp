<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GstEntry;
use App\Models\MarketplaceAccount;
use App\Models\TcsTdsRecord;
use App\Services\GstService;
use Illuminate\Http\Request;

class GstController extends Controller
{
    public function __construct(
        private GstService $gstService,
    ) {}

    /**
     * GST entries list with filters.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = GstEntry::where('company_id', $companyId)
            ->with('order');

        // Filters
        if ($gstType = $request->get('gst_type')) {
            $query->where('gst_type', $gstType);
        }

        if ($period = $request->get('period')) {
            $query->forPeriod($period);
        }

        if ($hsnCode = $request->get('hsn_code')) {
            $query->where('hsn_code', $hsnCode);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $entries = $query->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        // Summary totals for the current filtered set
        $totals = [
            'taxable' => $entries->sum('taxable_amount'),
            'cgst'    => $entries->sum('cgst_amount'),
            'sgst'    => $entries->sum('sgst_amount'),
            'igst'    => $entries->sum('igst_amount'),
            'total'   => $entries->sum('total_amount'),
        ];

        // Distinct HSN codes for filter dropdown
        $hsnCodes = GstEntry::where('company_id', $companyId)
            ->distinct()
            ->pluck('hsn_code')
            ->sort()
            ->values();

        return view('admin.gst.index', compact('entries', 'totals', 'hsnCodes'));
    }

    /**
     * HSN-wise GST summary for filing (GSTR-1 style).
     */
    public function summary(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $period    = $request->get('period', now()->format('Y-m'));

        $summary = $this->gstService->getGstSummary($companyId, $period);

        // Calculate grand totals
        $grandTotals = [
            'taxable' => array_sum(array_column($summary, 'total_taxable')),
            'cgst'    => array_sum(array_column($summary, 'total_cgst')),
            'sgst'    => array_sum(array_column($summary, 'total_sgst')),
            'igst'    => array_sum(array_column($summary, 'total_igst')),
            'gst'     => array_sum(array_column($summary, 'total_gst')),
            'amount'  => array_sum(array_column($summary, 'total_amount')),
        ];

        return view('admin.gst.summary', compact('summary', 'period', 'grandTotals'));
    }

    /**
     * TCS/TDS report with filters.
     */
    public function tcsTds(Request $request)
    {
        $companyId     = auth()->user()->company_id;
        $financialYear = $request->get('financial_year', $this->getCurrentFinancialYear());
        $quarter       = $request->get('quarter');

        $query = TcsTdsRecord::where('company_id', $companyId)
            ->with('marketplaceAccount.marketplace')
            ->where('financial_year', $financialYear);

        if ($quarter) {
            $query->where('quarter', $quarter);
        }

        $records = $query->orderBy('record_type')
            ->orderBy('quarter')
            ->get();

        // Group by marketplace and type
        $tcsRecords = $records->where('record_type', TcsTdsRecord::TYPE_TCS);
        $tdsRecords = $records->where('record_type', TcsTdsRecord::TYPE_TDS);

        $totals = [
            'tcs_amount' => $tcsRecords->sum('amount'),
            'tds_amount' => $tdsRecords->sum('amount'),
        ];

        $marketplaceAccounts = MarketplaceAccount::where('company_id', $companyId)
            ->with('marketplace')
            ->get();

        return view('admin.gst.tcs-tds', compact(
            'records', 'tcsRecords', 'tdsRecords', 'totals',
            'financialYear', 'quarter', 'marketplaceAccounts'
        ));
    }

    // ── Private Helpers ─────────────────────────────────────────

    private function getCurrentFinancialYear(): string
    {
        $month = now()->month;
        $year  = now()->year;

        if ($month <= 3) {
            return ($year - 1) . '-' . substr($year, 2);
        }

        return $year . '-' . substr($year + 1, 2);
    }
}
