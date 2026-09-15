<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiSuggestionResource;
use App\Http\Traits\ApiResponse;
use App\Models\AiSuggestion;
use App\Services\AiService;
use App\Services\AnomalyDetectionService;
use App\Services\DemandForecastService;
use Illuminate\Http\Request;

class AiApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AiService $aiService,
        private AnomalyDetectionService $anomalyService,
        private DemandForecastService $forecastService,
    ) {}

    /**
     * Active AI suggestions for the user's company.
     */
    public function suggestions(Request $request)
    {
        $companyId = $request->user()->company_id;

        $suggestions = AiSuggestion::forCompany($companyId)
            ->active()
            ->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            ->limit(20)
            ->get();

        return $this->success(
            AiSuggestionResource::collection($suggestions),
            'Suggestions retrieved.'
        );
    }

    /**
     * Accept a suggestion.
     */
    public function acceptSuggestion(Request $request, AiSuggestion $suggestion)
    {
        if ($suggestion->company_id !== $request->user()->company_id) {
            return $this->error('Suggestion not found.', 404);
        }

        if ($suggestion->status !== AiSuggestion::STATUS_PENDING) {
            return $this->error('Suggestion is no longer pending.', 422);
        }

        $this->aiService->recordFeedback(
            $suggestion,
            true,
            $request->input('notes'),
            $request->user()->id
        );

        return $this->success(
            new AiSuggestionResource($suggestion->fresh()),
            'Suggestion accepted.'
        );
    }

    /**
     * Reject a suggestion.
     */
    public function rejectSuggestion(Request $request, AiSuggestion $suggestion)
    {
        if ($suggestion->company_id !== $request->user()->company_id) {
            return $this->error('Suggestion not found.', 404);
        }

        if ($suggestion->status !== AiSuggestion::STATUS_PENDING) {
            return $this->error('Suggestion is no longer pending.', 422);
        }

        $this->aiService->recordFeedback(
            $suggestion,
            false,
            $request->input('notes'),
            $request->user()->id
        );

        return $this->success(
            new AiSuggestionResource($suggestion->fresh()),
            'Suggestion rejected.'
        );
    }

    /**
     * SKUs at risk of stockout.
     */
    public function stockoutRisk(Request $request)
    {
        $companyId = $request->user()->company_id;
        $withinDays = (int) ($request->days ?? 7);

        $risks = $this->forecastService->getStockoutRisk($companyId, $withinDays);

        return $this->success($risks, 'Stockout risks retrieved.');
    }

    /**
     * Current anomalies.
     */
    public function anomalies(Request $request)
    {
        $companyId = $request->user()->company_id;

        $summary = $this->anomalyService->getAnomalySummary($companyId);

        return $this->success($summary, 'Anomalies retrieved.');
    }
}
