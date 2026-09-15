<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AnalyticsService $analyticsService,
    ) {}

    /**
     * Analytics KPIs (revenue, profit, trends).
     */
    public function dashboard(Request $request)
    {
        $companyId = $request->user()->company_id;

        $data = $this->analyticsService->getDashboardData($companyId);

        return $this->success($data, 'Analytics dashboard retrieved.');
    }

    /**
     * Top/bottom performing designs.
     */
    public function designPerformance(Request $request)
    {
        $companyId = $request->user()->company_id;
        $sortBy = $request->input('sort_by', 'profit');

        $ranking = $this->analyticsService->getDesignRanking($companyId, $sortBy);

        return $this->success($ranking, 'Design performance retrieved.');
    }

    /**
     * Profit over time data points.
     */
    public function profitTrend(Request $request)
    {
        $companyId = $request->user()->company_id;
        $period = $request->input('period', 'daily');
        $count = min((int) ($request->input('count', 30)), 90);

        $trend = $this->analyticsService->getProfitTrend($companyId, $period, $count);

        return $this->success($trend, 'Profit trend retrieved.');
    }
}
