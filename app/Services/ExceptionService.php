<?php

namespace App\Services;

use App\Models\AppException;
use App\Models\ExceptionAssignment;
use App\Models\ExceptionCategory;
use App\Models\ExceptionComment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExceptionService
{
    /**
     * Raise a new exception with auto-categorization and auto-assignment.
     */
    public function raiseException(array $data): AppException
    {
        return DB::transaction(function () use ($data) {
            // Auto-detect category from exception_type if not provided
            $categoryId = $data['category_id'] ?? null;
            $category = null;

            if (! $categoryId && ! empty($data['exception_type'])) {
                $category = ExceptionCategory::active()
                    ->where('code', $data['exception_type'])
                    ->first();
                $categoryId = $category?->id;
            } elseif ($categoryId) {
                $category = ExceptionCategory::find($categoryId);
            }

            $exception = AppException::create([
                'company_id'     => $data['company_id'],
                'category_id'    => $categoryId,
                'exception_type' => $data['exception_type'],
                'severity'       => $data['severity'] ?? $category?->default_severity ?? 'medium',
                'entity_type'    => $data['entity_type'] ?? null,
                'entity_id'      => $data['entity_id'] ?? null,
                'title'          => $data['title'],
                'description'    => $data['description'] ?? null,
                'status'         => AppException::STATUS_OPEN,
            ]);

            // Auto-assign based on category rules
            if ($category && $category->default_assignee_role) {
                $assignee = User::where('company_id', $data['company_id'])
                    ->where('role', $category->default_assignee_role)
                    ->where('status', 'active')
                    ->first();

                if ($assignee) {
                    $this->assignException($exception, $assignee->id);
                }
            }

            return $exception;
        });
    }

    /**
     * Assign an exception to a user.
     */
    public function assignException(AppException $exception, int $userId, ?int $assignedBy = null): ExceptionAssignment
    {
        return DB::transaction(function () use ($exception, $userId, $assignedBy) {
            $category = $exception->category;
            $dueAt = null;

            if ($category && $category->sla_hours) {
                $dueAt = $exception->created_at->addHours($category->sla_hours);
            }

            // Set priority based on severity
            $priorityMap = [
                'critical' => ExceptionAssignment::PRIORITY_URGENT,
                'high'     => ExceptionAssignment::PRIORITY_HIGH,
                'medium'   => ExceptionAssignment::PRIORITY_MEDIUM,
                'low'      => ExceptionAssignment::PRIORITY_LOW,
            ];

            $assignment = ExceptionAssignment::create([
                'exception_id' => $exception->id,
                'assigned_to'  => $userId,
                'assigned_by'  => $assignedBy,
                'status'       => ExceptionAssignment::STATUS_ASSIGNED,
                'priority'     => $priorityMap[$exception->severity] ?? ExceptionAssignment::PRIORITY_MEDIUM,
                'due_at'       => $dueAt,
            ]);

            // Update exception assigned_to and status
            $exception->update([
                'assigned_to' => $userId,
                'status'      => AppException::STATUS_IN_REVIEW,
            ]);

            return $assignment;
        });
    }

    /**
     * Escalate an exception.
     */
    public function escalateException(AppException $exception, string $reason): AppException
    {
        return DB::transaction(function () use ($exception, $reason) {
            // Bump severity if possible
            $severityOrder = ['low' => 0, 'medium' => 1, 'high' => 2, 'critical' => 3];
            $reverseOrder  = array_flip($severityOrder);
            $currentLevel  = $severityOrder[$exception->severity] ?? 1;

            if ($currentLevel < 3) {
                $exception->severity = $reverseOrder[$currentLevel + 1];
            }

            $exception->save();

            // Update latest assignment to escalated
            $assignment = $exception->latestAssignment;
            if ($assignment) {
                $assignment->update([
                    'status'   => ExceptionAssignment::STATUS_ESCALATED,
                    'priority' => ExceptionAssignment::PRIORITY_URGENT,
                    'notes'    => ($assignment->notes ? $assignment->notes . "\n" : '') . 'Escalated: ' . $reason,
                ]);
            }

            // Add a comment about the escalation
            ExceptionComment::create([
                'exception_id' => $exception->id,
                'user_id'      => auth()->id(),
                'comment'      => 'Exception escalated: ' . $reason,
                'is_internal'  => true,
                'created_at'   => now(),
            ]);

            return $exception;
        });
    }

    /**
     * Resolve an exception.
     */
    public function resolveException(AppException $exception, string $notes, int $userId): AppException
    {
        return DB::transaction(function () use ($exception, $notes, $userId) {
            $exception->update([
                'status'           => AppException::STATUS_RESOLVED,
                'resolution'       => $notes,
                'resolution_notes' => $notes,
                'resolved_by'      => $userId,
                'resolved_at'      => now(),
            ]);

            // Close the latest assignment
            $assignment = $exception->latestAssignment;
            if ($assignment) {
                $assignment->update([
                    'status'       => ExceptionAssignment::STATUS_RESOLVED,
                    'completed_at' => now(),
                ]);
            }

            return $exception;
        });
    }

    /**
     * Add a comment to an exception.
     */
    public function addComment(AppException $exception, int $userId, string $comment, bool $isInternal = false): ExceptionComment
    {
        return ExceptionComment::create([
            'exception_id' => $exception->id,
            'user_id'      => $userId,
            'comment'      => $comment,
            'is_internal'  => $isInternal,
            'created_at'   => now(),
        ]);
    }

    /**
     * Get exception dashboard data for a company.
     */
    public function getExceptionDashboard(int $companyId): array
    {
        $base = AppException::forCompany($companyId);

        $openExceptions = (clone $base)->unresolved()->count();
        $criticalCount  = (clone $base)->unresolved()->critical()->count();
        $resolvedToday  = (clone $base)->where('status', 'resolved')
            ->whereDate('resolved_at', today())->count();

        // SLA breaches
        $slaBreaches = $this->checkSlaBreaches($companyId)->count();

        // By severity
        $bySeverity = [];
        foreach (AppException::SEVERITIES as $severity) {
            $bySeverity[$severity] = (clone $base)->unresolved()->bySeverity($severity)->count();
        }

        // By category
        $byCategory = (clone $base)->unresolved()
            ->select('exception_type', DB::raw('count(*) as count'))
            ->groupBy('exception_type')
            ->pluck('count', 'exception_type')
            ->toArray();

        return [
            'open_exceptions' => $openExceptions,
            'critical_count'  => $criticalCount,
            'sla_breaches'    => $slaBreaches,
            'resolved_today'  => $resolvedToday,
            'by_severity'     => $bySeverity,
            'by_category'     => $byCategory,
        ];
    }

    /**
     * Find exceptions that have breached their SLA.
     */
    public function checkSlaBreaches(int $companyId)
    {
        return AppException::forCompany($companyId)
            ->unresolved()
            ->whereHas('category', function ($q) {
                $q->whereNotNull('sla_hours');
            })
            ->with('category')
            ->get()
            ->filter(fn (AppException $e) => $e->isSlaBreached());
    }

    /**
     * Auto-detect exceptions by running checks against company data.
     */
    public function autoDetectExceptions(int $companyId): array
    {
        $detected = [];

        // 1. Unmatched settlement lines older than 3 days
        $unmatchedLines = DB::table('settlement_lines')
            ->join('settlements', 'settlements.id', '=', 'settlement_lines.settlement_id')
            ->where('settlements.company_id', $companyId)
            ->where('settlement_lines.match_status', 'unmatched')
            ->where('settlement_lines.created_at', '<', now()->subDays(3))
            ->count();

        if ($unmatchedLines > 0) {
            $existing = AppException::forCompany($companyId)
                ->where('exception_type', 'settlement_discrepancy')
                ->open()
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (! $existing) {
                $detected[] = $this->raiseException([
                    'company_id'     => $companyId,
                    'exception_type' => 'settlement_discrepancy',
                    'severity'       => 'high',
                    'title'          => "{$unmatchedLines} unmatched settlement lines older than 3 days",
                    'description'    => "There are {$unmatchedLines} settlement lines that remain unmatched for more than 3 days. Please review and reconcile.",
                    'entity_type'    => 'settlement_line',
                ]);
            }
        }

        // 2. Orders stuck in same status > 48 hours
        $stuckOrders = DB::table('orders')
            ->where('company_id', $companyId)
            ->whereNotIn('status', ['delivered', 'cancelled', 'return', 'rto'])
            ->where('updated_at', '<', now()->subHours(48))
            ->count();

        if ($stuckOrders > 0) {
            $existing = AppException::forCompany($companyId)
                ->where('exception_type', 'order_stuck')
                ->open()
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (! $existing) {
                $detected[] = $this->raiseException([
                    'company_id'     => $companyId,
                    'exception_type' => 'order_stuck',
                    'severity'       => 'medium',
                    'title'          => "{$stuckOrders} orders stuck for over 48 hours",
                    'description'    => "There are {$stuckOrders} orders that have not had a status change in over 48 hours. Please investigate.",
                    'entity_type'    => 'order',
                ]);
            }
        }

        // 3. Inventory items below minimum stock
        $lowStock = DB::table('inventory_items')
            ->where('company_id', $companyId)
            ->whereColumn('available_qty', '<', 'minimum_qty')
            ->where('minimum_qty', '>', 0)
            ->count();

        if ($lowStock > 0) {
            $existing = AppException::forCompany($companyId)
                ->where('exception_type', 'inventory_anomaly')
                ->open()
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (! $existing) {
                $detected[] = $this->raiseException([
                    'company_id'     => $companyId,
                    'exception_type' => 'inventory_anomaly',
                    'severity'       => 'medium',
                    'title'          => "{$lowStock} items below minimum stock level",
                    'description'    => "There are {$lowStock} inventory items with available quantity below their minimum stock threshold.",
                    'entity_type'    => 'inventory_item',
                ]);
            }
        }

        // 4. Returns pending inspection > 24 hours
        $pendingInspection = DB::table('returns')
            ->where('company_id', $companyId)
            ->where('status', 'received')
            ->where('updated_at', '<', now()->subHours(24))
            ->count();

        if ($pendingInspection > 0) {
            $existing = AppException::forCompany($companyId)
                ->where('exception_type', 'return_pending')
                ->open()
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (! $existing) {
                $detected[] = $this->raiseException([
                    'company_id'     => $companyId,
                    'exception_type' => 'return_pending',
                    'severity'       => 'low',
                    'title'          => "{$pendingInspection} returns awaiting inspection > 24 hours",
                    'description'    => "There are {$pendingInspection} returned items that have been received but not yet inspected for over 24 hours.",
                    'entity_type'    => 'return',
                ]);
            }
        }

        // 5. Claims without response > 5 days
        $staleClaimsCount = DB::table('claims')
            ->where('company_id', $companyId)
            ->whereIn('status', ['filed', 'under_review'])
            ->where('updated_at', '<', now()->subDays(5))
            ->count();

        if ($staleClaimsCount > 0) {
            $existing = AppException::forCompany($companyId)
                ->where('exception_type', 'claim_stale')
                ->open()
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (! $existing) {
                $detected[] = $this->raiseException([
                    'company_id'     => $companyId,
                    'exception_type' => 'claim_stale',
                    'severity'       => 'high',
                    'title'          => "{$staleClaimsCount} claims without response for over 5 days",
                    'description'    => "There are {$staleClaimsCount} claims that have not received a response in over 5 days.",
                    'entity_type'    => 'claim',
                ]);
            }
        }

        return $detected;
    }
}
