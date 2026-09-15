<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiSuggestion;
use App\Models\AiTask;
use App\Models\Design;
use App\Services\AiService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function __construct(
        protected AiService $ai
    ) {}

    // ── Dashboard ────────────────────────────────────────────────────

    public function dashboard()
    {
        $companyId = auth()->user()->company_id ?? 1;

        $activeSuggestions = AiSuggestion::forCompany($companyId)
            ->active()
            ->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            ->limit(6)
            ->get();

        $stats = [
            'active_suggestions' => AiSuggestion::forCompany($companyId)->active()->count(),
            'tasks_processed'    => AiTask::forCompany($companyId)->status(AiTask::STATUS_COMPLETED)->count(),
            'tasks_today'        => AiTask::forCompany($companyId)
                ->whereDate('created_at', today())
                ->count(),
        ];

        // Quick anomaly count
        $anomalySummary = $this->ai->detectAnomalies($companyId);

        return view('admin.ai.dashboard', compact(
            'activeSuggestions',
            'stats',
            'anomalySummary',
        ));
    }

    // ── Suggestions ──────────────────────────────────────────────────

    public function suggestions(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $query = AiSuggestion::forCompany($companyId)->latest();

        if ($request->filled('type')) {
            $query->ofType($request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $suggestions = $query->paginate(20)->withQueryString();

        return view('admin.ai.suggestions', compact('suggestions'));
    }

    public function acceptSuggestion(AiSuggestion $suggestion)
    {
        $this->ai->recordFeedback($suggestion, true, null, auth()->id());

        return back()->with('success', 'Suggestion accepted.');
    }

    public function rejectSuggestion(AiSuggestion $suggestion)
    {
        $this->ai->recordFeedback($suggestion, false, null, auth()->id());

        return back()->with('success', 'Suggestion rejected.');
    }

    // ── Anomalies ────────────────────────────────────────────────────

    public function anomalies()
    {
        $companyId = auth()->user()->company_id ?? 1;

        $anomalySummary = $this->ai->detectAnomalies($companyId);
        $lastRun = AiTask::forCompany($companyId)
            ->ofType(AiTask::TYPE_ANOMALY_DETECT)
            ->latest()
            ->first();

        return view('admin.ai.anomalies', compact('anomalySummary', 'lastRun'));
    }

    public function runDetection()
    {
        $companyId = auth()->user()->company_id ?? 1;

        // Create and process task
        $task = $this->ai->createTask($companyId, AiTask::TYPE_ANOMALY_DETECT, [], auth()->id());
        $task = $this->ai->processTask($task);

        // Store anomalies as suggestions
        $anomalies = $task->output_data['anomalies'] ?? [];
        $stored = 0;
        foreach ($anomalies as $anomaly) {
            AiSuggestion::create([
                'company_id'      => $companyId,
                'ai_task_id'      => $task->id,
                'suggestion_type' => AiSuggestion::TYPE_ANOMALY,
                'entity_type'     => $anomaly['entity_type'] ? ('App\\Models\\' . ucfirst($anomaly['entity_type'])) : null,
                'entity_id'       => $anomaly['entity_id'],
                'title'           => $anomaly['title'],
                'description'     => $anomaly['description'],
                'confidence'      => match ($anomaly['severity']) {
                    'critical' => 0.95,
                    'high'     => 0.85,
                    'medium'   => 0.70,
                    default    => 0.50,
                },
                'priority'        => $anomaly['severity'] === 'critical' ? 'critical' : ($anomaly['severity'] === 'high' ? 'high' : 'medium'),
                'status'          => AiSuggestion::STATUS_PENDING,
                'action_data'     => ['action' => $anomaly['action'] ?? ''],
                'expires_at'      => now()->addDays(3),
            ]);
            $stored++;
        }

        return back()->with('success', "Anomaly detection complete. Found {$stored} anomalies.");
    }

    // ── Forecast ─────────────────────────────────────────────────────

    public function forecast(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $designs = Design::forCompany($companyId)->active()->orderBy('name')->get();
        $forecast = null;
        $stockoutRisks = [];
        $selectedDesignId = $request->get('design_id');

        if ($selectedDesignId) {
            $forecast = $this->ai->generateDemandForecast($companyId, (int) $selectedDesignId);
        }

        $stockoutRisks = app(\App\Services\DemandForecastService::class)->getStockoutRisk($companyId);

        return view('admin.ai.forecast', compact('designs', 'forecast', 'stockoutRisks', 'selectedDesignId'));
    }

    // ── Document Analyzer ────────────────────────────────────────────

    public function documentAnalyzer()
    {
        return view('admin.ai.document-analyzer');
    }

    public function analyzeDocument(Request $request)
    {
        $request->validate([
            'document' => 'required|file|max:10240',
        ]);

        $file = $request->file('document');
        $path = $file->store('ai-documents', 'local');
        $fullPath = storage_path('app/' . $path);

        $companyId = auth()->user()->company_id ?? 1;

        // Create task and process
        $task = $this->ai->createTask($companyId, AiTask::TYPE_DOCUMENT_PARSE, [
            'file_path'     => $fullPath,
            'original_name' => $file->getClientOriginalName(),
        ], auth()->id());

        $task = $this->ai->processTask($task);

        $result = $task->output_data;

        return view('admin.ai.document-analyzer', compact('result'));
    }

    // ── SKU Matcher ──────────────────────────────────────────────────

    public function skuMatcher()
    {
        $companyId = auth()->user()->company_id ?? 1;

        $recentTasks = AiTask::forCompany($companyId)
            ->ofType(AiTask::TYPE_SKU_MATCH)
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.ai.sku-matcher', compact('recentTasks'));
    }

    public function matchSku(Request $request)
    {
        $request->validate([
            'external_sku' => 'required|string|max:255',
        ]);

        $companyId = auth()->user()->company_id ?? 1;

        // Create task and process
        $task = $this->ai->createTask($companyId, AiTask::TYPE_SKU_MATCH, [
            'external_sku' => $request->external_sku,
            'product_name' => $request->product_name,
            'color'        => $request->color,
            'size'         => $request->size,
        ], auth()->id());

        $task = $this->ai->processTask($task);

        $result = $task->output_data;

        $recentTasks = AiTask::forCompany($companyId)
            ->ofType(AiTask::TYPE_SKU_MATCH)
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.ai.sku-matcher', compact('result', 'recentTasks'));
    }
}
