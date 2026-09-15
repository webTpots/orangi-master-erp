<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\MarketplaceAccount;
use App\Models\Sku;
use App\Models\Vendor;
use App\Models\VendorPerformance;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analytics
    ) {}

    // ── Owner Decision Dashboard ───────────────────────────────────

    public function dashboard()
    {
        $companyId = auth()->user()->company_id ?? 1;

        $data = $this->analytics->getDashboardData($companyId);
        $insights = $this->analytics->getWhyAnalysis($companyId);
        $profitTrend = $this->analytics->getProfitTrend($companyId, 'daily', 30);

        // P&L Summary (this month vs previous month)
        $monthStart = Carbon::now()->startOfMonth();
        $today = Carbon::today();
        $prevMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $prevMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $currentPnl = $this->analytics->getProfitAndLoss($companyId, $monthStart, $today);
        $prevPnl = $this->analytics->getProfitAndLoss($companyId, $prevMonthStart, $prevMonthEnd);

        return view('admin.analytics.dashboard', compact(
            'data',
            'insights',
            'profitTrend',
            'currentPnl',
            'prevPnl',
        ));
    }

    // ── P&L Statement ──────────────────────────────────────────────

    public function profitAndLoss(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $period = $request->get('period', 'month');

        $now = Carbon::now();
        [$from, $to, $prevFrom, $prevTo] = match ($period) {
            'quarter' => [
                $now->copy()->firstOfQuarter(),
                $now->copy(),
                $now->copy()->subQuarter()->firstOfQuarter(),
                $now->copy()->subQuarter()->lastOfQuarter(),
            ],
            'year' => [
                $now->copy()->startOfYear(),
                $now->copy(),
                $now->copy()->subYear()->startOfYear(),
                $now->copy()->subYear()->endOfYear(),
            ],
            default => [
                $now->copy()->startOfMonth(),
                $now->copy(),
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
            ],
        };

        $currentPnl = $this->analytics->getProfitAndLoss($companyId, $from, $to);
        $prevPnl = $this->analytics->getProfitAndLoss($companyId, $prevFrom, $prevTo);

        return view('admin.analytics.profit-loss', compact(
            'currentPnl',
            'prevPnl',
            'period',
            'from',
            'to',
        ));
    }

    // ── Design Performance ─────────────────────────────────────────

    public function designPerformance(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $sortBy = $request->get('sort', 'profit');

        $rankings = $this->analytics->getDesignRanking($companyId, $sortBy);

        // Enrich with design info
        $designIds = $rankings->pluck('design_id')->toArray();
        $designs = Design::whereIn('id', $designIds)->get()->keyBy('id');

        return view('admin.analytics.designs', compact(
            'rankings',
            'designs',
            'sortBy',
        ));
    }

    // ── Marketplace Performance ────────────────────────────────────

    public function marketplacePerformance()
    {
        $companyId = auth()->user()->company_id ?? 1;
        $monthStart = Carbon::now()->startOfMonth();
        $today = Carbon::today();

        $data = $this->analytics->getDashboardData($companyId);
        $marketplaceData = $data['marketplace_data'];

        // Enrich with account info
        $accountIds = $marketplaceData->pluck('marketplace_account_id')->toArray();
        $accounts = MarketplaceAccount::whereIn('id', $accountIds)
            ->with('marketplace')
            ->get()
            ->keyBy('id');

        return view('admin.analytics.marketplaces', compact(
            'marketplaceData',
            'accounts',
        ));
    }

    // ── Vendor Scorecard ───────────────────────────────────────────

    public function vendorPerformance()
    {
        $companyId = auth()->user()->company_id ?? 1;
        $monthStart = Carbon::now()->startOfMonth();

        $vendorData = VendorPerformance::forCompany($companyId)
            ->where('period_type', 'monthly')
            ->where('period_date', $monthStart->toDateString())
            ->with('vendor')
            ->get();

        // If no snapshot data, show all active vendors with empty metrics
        if ($vendorData->isEmpty()) {
            $vendors = Vendor::forCompany($companyId)->active()->get();
        } else {
            $vendors = collect();
        }

        return view('admin.analytics.vendors', compact(
            'vendorData',
            'vendors',
        ));
    }

    // ── Inventory Analytics ────────────────────────────────────────

    public function inventoryAnalytics()
    {
        $companyId = auth()->user()->company_id ?? 1;

        $data = $this->analytics->getInventoryAnalytics($companyId);

        // Enrich SKU data
        $allSkuIds = collect()
            ->merge($data['fast_movers']->pluck('sku_id'))
            ->merge($data['slow_movers']->pluck('sku_id'))
            ->merge($data['reorder_alerts']->pluck('sku_id'))
            ->unique()
            ->filter();

        $skus = Sku::whereIn('id', $allSkuIds)
            ->with('variant.product.design')
            ->get()
            ->keyBy('id');

        return view('admin.analytics.inventory', compact(
            'data',
            'skus',
        ));
    }
}
