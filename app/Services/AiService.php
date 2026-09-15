<?php

namespace App\Services;

use App\Models\AiSuggestion;
use App\Models\AiTask;
use App\Models\AiTrainingData;
use App\Models\Design;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\ReturnOrder;
use App\Models\SettlementLine;
use App\Models\Sku;
use App\Models\SubOrder;
use Illuminate\Support\Facades\DB;

class AiService
{
    public function __construct(
        protected SkuIntelligence $skuIntelligence,
        protected AnomalyDetectionService $anomalyService,
        protected DemandForecastService $forecastService,
    ) {}

    // ── Document Processing ──────────────────────────────────────────

    /**
     * Analyze an uploaded document — determine type and extract data.
     * Heuristic v1: uses file extension and basic content analysis.
     */
    public function processDocument(string $filePath, string $expectedType = 'auto'): array
    {
        $startTime = microtime(true);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
        $fileName = basename($filePath);

        // Determine document type from name/extension
        $detectedType = $expectedType;
        if ($expectedType === 'auto') {
            $nameLower = strtolower($fileName);
            if (str_contains($nameLower, 'label') || str_contains($nameLower, 'shipping')) {
                $detectedType = 'label';
            } elseif (str_contains($nameLower, 'manifest')) {
                $detectedType = 'manifest';
            } elseif (str_contains($nameLower, 'settlement') || str_contains($nameLower, 'payment')) {
                $detectedType = 'settlement';
            } elseif (str_contains($nameLower, 'invoice') || str_contains($nameLower, 'bill')) {
                $detectedType = 'invoice';
            } elseif (in_array($extension, ['csv', 'xlsx', 'xls'])) {
                $detectedType = 'spreadsheet';
            } elseif (in_array($extension, ['pdf'])) {
                $detectedType = 'pdf_document';
            } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                $detectedType = 'image';
            } else {
                $detectedType = 'unknown';
            }
        }

        $confidence = match ($detectedType) {
            'unknown' => 0.20,
            'image', 'pdf_document', 'spreadsheet' => 0.50,
            default => 0.75,
        };

        $processingTime = round((microtime(true) - $startTime) * 1000);

        return [
            'detected_type'   => $detectedType,
            'file_name'       => $fileName,
            'file_extension'  => $extension,
            'file_size'       => $fileSize,
            'file_size_human' => $this->humanFileSize($fileSize),
            'confidence'      => $confidence,
            'model_used'      => 'heuristic_v1',
            'processing_ms'   => $processingTime,
            'extracted_data'  => [
                'file_type'  => $extension,
                'doc_type'   => $detectedType,
                'analyzable' => in_array($extension, ['csv', 'xlsx', 'xls', 'pdf']),
            ],
            'suggested_action' => match ($detectedType) {
                'label'      => 'Import via Label Import',
                'manifest'   => 'Import via Manifest Import',
                'settlement' => 'Import via Settlement Upload',
                'invoice'    => 'Review and record in finance',
                default      => 'Manual review required',
            },
        ];
    }

    // ── SKU Matching ─────────────────────────────────────────────────

    /**
     * Enhanced SKU matching — returns top 3 suggestions with confidence.
     */
    public function matchSku(string $externalSku, int $companyId, ?string $productName = null, ?string $color = null, ?string $size = null): array
    {
        $startTime = microtime(true);

        $matches = $this->skuIntelligence->findMatch(
            $externalSku,
            $productName,
            $color,
            $size,
            $companyId,
        );

        // Limit to top 3
        $topMatches = array_slice($matches, 0, 3);

        $results = [];
        foreach ($topMatches as $match) {
            $sku = $match['sku'];
            $results[] = [
                'sku_id'     => $sku->id,
                'sku_code'   => $sku->sku_code,
                'sku_name'   => $sku->short_name,
                'confidence' => $match['confidence'],
                'reason'     => $match['reason'],
                'strategy'   => $match['strategy'],
            ];
        }

        $processingTime = round((microtime(true) - $startTime) * 1000);

        return [
            'external_sku'   => $externalSku,
            'matches'        => $results,
            'best_match'     => $results[0] ?? null,
            'model_used'     => 'sku_intelligence_v1',
            'processing_ms'  => $processingTime,
        ];
    }

    // ── Anomaly Detection ────────────────────────────────────────────

    /**
     * Run full anomaly detection scan.
     */
    public function detectAnomalies(int $companyId): array
    {
        return $this->anomalyService->getAnomalySummary($companyId);
    }

    // ── Demand Forecasting ───────────────────────────────────────────

    /**
     * Generate demand forecast for a design.
     */
    public function generateDemandForecast(int $companyId, int $designId): array
    {
        return $this->forecastService->forecastDesign($companyId, $designId);
    }

    // ── Smart Suggestions ────────────────────────────────────────────

    /**
     * Generate actionable smart suggestions.
     */
    public function getSmartSuggestions(int $companyId): array
    {
        $suggestions = [];

        // 1. Stockout risk — items running low
        $stockoutRisks = $this->forecastService->getStockoutRisk($companyId, 7);
        foreach (array_slice($stockoutRisks, 0, 5) as $risk) {
            $suggestions[] = [
                'type'        => 'reorder',
                'priority'    => $risk['urgency'] === 'critical' ? 'critical' : 'high',
                'title'       => "Reorder {$risk['sku_code']} — only {$risk['days_of_stock']} days of stock left",
                'description' => "Current stock: {$risk['current_stock']} units. Daily run rate: {$risk['avg_daily']}/day.",
                'confidence'  => $risk['urgency'] === 'critical' ? 0.95 : 0.85,
                'entity_type' => 'App\\Models\\Sku',
                'entity_id'   => $risk['sku_id'],
                'action_data' => ['sku_id' => $risk['sku_id'], 'suggested_qty' => ceil($risk['avg_daily'] * 14)],
            ];
        }

        // 2. High return rate items — suggest price/quality review
        $returnData = DB::table('returns')
            ->where('returns.company_id', $companyId)
            ->where('returns.created_at', '>=', now()->subDays(30))
            ->join('sub_orders', 'returns.sub_order_id', '=', 'sub_orders.id')
            ->whereNotNull('sub_orders.sku_id')
            ->select('sub_orders.sku_id', DB::raw('COUNT(*) as return_count'))
            ->groupBy('sub_orders.sku_id')
            ->having('return_count', '>=', 3)
            ->get();

        foreach ($returnData->take(3) as $rd) {
            $totalOrders = SubOrder::forCompany($companyId)
                ->where('sku_id', $rd->sku_id)
                ->whereHas('order', function ($q) {
                    $q->where('created_at', '>=', now()->subDays(30));
                })
                ->count();

            if ($totalOrders > 0) {
                $returnRate = round(($rd->return_count / $totalOrders) * 100);
                $sku = Sku::with('variant.product.design')->find($rd->sku_id);
                $skuName = $sku?->sku_code ?? "SKU #{$rd->sku_id}";

                if ($returnRate > 15) {
                    $suggestions[] = [
                        'type'        => 'quality_alert',
                        'priority'    => $returnRate > 30 ? 'critical' : 'high',
                        'title'       => "Consider reviewing {$skuName} — high return rate ({$returnRate}%)",
                        'description' => "{$rd->return_count} returns out of {$totalOrders} orders in last 30 days.",
                        'confidence'  => 0.80,
                        'entity_type' => 'App\\Models\\Sku',
                        'entity_id'   => $rd->sku_id,
                        'action_data' => ['sku_id' => $rd->sku_id, 'return_rate' => $returnRate],
                    ];
                }
            }
        }

        // 3. Unmatched settlement lines
        $unmatchedLines = SettlementLine::whereHas('settlement', function ($q) use ($companyId) {
            $q->forCompany($companyId);
        })->where('match_status', 'unmatched')->count();

        if ($unmatchedLines > 0) {
            $suggestions[] = [
                'type'        => 'process_improvement',
                'priority'    => $unmatchedLines > 20 ? 'high' : 'medium',
                'title'       => "{$unmatchedLines} unmatched settlements need attention",
                'description' => "Match settlement lines to orders for accurate P&L reporting.",
                'confidence'  => 0.90,
                'entity_type' => null,
                'entity_id'   => null,
                'action_data' => ['count' => $unmatchedLines],
            ];
        }

        // 4. Top performing design — suggest stock increase
        $topDesign = DB::table('sub_orders')
            ->where('sub_orders.company_id', $companyId)
            ->whereNotNull('sub_orders.sku_id')
            ->join('orders', 'sub_orders.order_id', '=', 'orders.id')
            ->where('orders.order_date', '>=', now()->subDays(14))
            ->join('skus', 'sub_orders.sku_id', '=', 'skus.id')
            ->join('variants', 'skus.variant_id', '=', 'variants.id')
            ->join('products', 'variants.product_id', '=', 'products.id')
            ->select('products.design_id', DB::raw('SUM(sub_orders.quantity) as total_qty'))
            ->groupBy('products.design_id')
            ->orderByDesc('total_qty')
            ->first();

        if ($topDesign && $topDesign->total_qty > 0) {
            $design = Design::find($topDesign->design_id);
            if ($design) {
                $suggestions[] = [
                    'type'        => 'reorder',
                    'priority'    => 'medium',
                    'title'       => "{$design->name} is your top performer — increase stock",
                    'description' => "{$topDesign->total_qty} units sold in last 14 days. Ensure adequate stock levels.",
                    'confidence'  => 0.75,
                    'entity_type' => 'App\\Models\\Design',
                    'entity_id'   => $design->id,
                    'action_data' => ['design_id' => $design->id, 'units_14d' => $topDesign->total_qty],
                ];
            }
        }

        // Sort by priority
        $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        usort($suggestions, fn ($a, $b) => ($priorityOrder[$a['priority']] ?? 4) <=> ($priorityOrder[$b['priority']] ?? 4));

        return $suggestions;
    }

    // ── Image Classification (Placeholder) ───────────────────────────

    /**
     * Placeholder for future image classification.
     */
    public function classifyImage(string $imagePath): array
    {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        $fileSize = file_exists($imagePath) ? filesize($imagePath) : 0;

        return [
            'file_name'  => basename($imagePath),
            'extension'  => $extension,
            'file_size'  => $fileSize,
            'model_used' => 'placeholder_v1',
            'message'    => 'Image classification will be available when AI model is connected.',
            'confidence' => 0,
        ];
    }

    // ── Task Management ──────────────────────────────────────────────

    /**
     * Create and queue an AI task.
     */
    public function createTask(int $companyId, string $type, array $input, ?int $userId = null): AiTask
    {
        return AiTask::create([
            'company_id' => $companyId,
            'task_type'  => $type,
            'status'     => AiTask::STATUS_QUEUED,
            'input_data' => $input,
            'created_by' => $userId,
        ]);
    }

    /**
     * Process a queued task.
     */
    public function processTask(AiTask $task): AiTask
    {
        $task->update(['status' => AiTask::STATUS_PROCESSING]);
        $startTime = microtime(true);

        try {
            $result = match ($task->task_type) {
                AiTask::TYPE_DOCUMENT_PARSE  => $this->processDocument($task->input_data['file_path'] ?? '', $task->input_data['expected_type'] ?? 'auto'),
                AiTask::TYPE_SKU_MATCH       => $this->matchSku($task->input_data['external_sku'] ?? '', $task->company_id, $task->input_data['product_name'] ?? null),
                AiTask::TYPE_ANOMALY_DETECT  => $this->detectAnomalies($task->company_id),
                AiTask::TYPE_DEMAND_FORECAST => $this->generateDemandForecast($task->company_id, $task->input_data['design_id'] ?? 0),
                AiTask::TYPE_IMAGE_CLASSIFY  => $this->classifyImage($task->input_data['image_path'] ?? ''),
                AiTask::TYPE_SMART_SUGGEST   => ['suggestions' => $this->getSmartSuggestions($task->company_id)],
                default => ['error' => 'Unknown task type'],
            };

            $processingTime = round((microtime(true) - $startTime) * 1000);

            $task->update([
                'status'           => AiTask::STATUS_COMPLETED,
                'output_data'      => $result,
                'confidence_score' => $result['confidence'] ?? null,
                'model_used'       => $result['model_used'] ?? 'heuristic_v1',
                'processing_time_ms' => $processingTime,
                'processed_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            $task->update([
                'status'             => AiTask::STATUS_FAILED,
                'error_message'      => $e->getMessage(),
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000),
                'processed_at'       => now(),
            ]);
        }

        return $task->fresh();
    }

    // ── Feedback & Training ──────────────────────────────────────────

    /**
     * Record user feedback on a suggestion.
     */
    public function recordFeedback(AiSuggestion $suggestion, bool $accepted, ?string $notes = null, ?int $userId = null): void
    {
        $suggestion->update([
            'status'      => $accepted ? AiSuggestion::STATUS_ACCEPTED : AiSuggestion::STATUS_REJECTED,
            'accepted_by' => $accepted ? $userId : null,
            'accepted_at' => $accepted ? now() : null,
        ]);

        // Save training data
        AiTrainingData::create([
            'company_id'      => $suggestion->company_id,
            'data_type'       => $this->mapSuggestionTypeToDataType($suggestion->suggestion_type),
            'input_data'      => ['suggestion_type' => $suggestion->suggestion_type, 'title' => $suggestion->title],
            'expected_output' => ['accepted' => $accepted],
            'actual_output'   => $suggestion->action_data,
            'is_correct'      => $accepted,
            'feedback_notes'  => $notes,
            'created_by'      => $userId,
        ]);
    }

    /**
     * Store suggestions into the database.
     */
    public function storeSuggestions(int $companyId, array $suggestions, ?int $taskId = null): int
    {
        $count = 0;
        foreach ($suggestions as $s) {
            AiSuggestion::create([
                'company_id'      => $companyId,
                'ai_task_id'      => $taskId,
                'suggestion_type' => $s['type'],
                'entity_type'     => $s['entity_type'] ?? null,
                'entity_id'       => $s['entity_id'] ?? null,
                'title'           => $s['title'],
                'description'     => $s['description'],
                'confidence'      => $s['confidence'] ?? 0,
                'priority'        => $s['priority'] ?? 'medium',
                'status'          => AiSuggestion::STATUS_PENDING,
                'action_data'     => $s['action_data'] ?? null,
                'expires_at'      => now()->addDays(7),
            ]);
            $count++;
        }

        return $count;
    }

    // ── Helpers ───────────────────────────────────────────────────────

    protected function mapSuggestionTypeToDataType(string $suggestionType): string
    {
        return match ($suggestionType) {
            'sku_mapping'     => AiTrainingData::TYPE_SKU_MAPPING_FEEDBACK,
            'anomaly'         => AiTrainingData::TYPE_ANOMALY_FEEDBACK,
            default           => AiTrainingData::TYPE_CLASSIFICATION_CORRECTION,
        };
    }

    protected function humanFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1) . ' ' . $units[$i];
    }
}
