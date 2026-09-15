<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankStatementImport;
use App\Models\BankTransaction;
use App\Models\Payment;
use App\Models\ReconciliationRule;
use App\Models\Settlement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BankReconciliationService
{
    /**
     * Import a bank statement file and create transactions.
     */
    public function importBankStatement(BankAccount $account, string $filePath, string $format): BankStatementImport
    {
        $fileHash = md5_file($filePath);

        // Dedup check
        $existing = BankStatementImport::where('file_hash', $fileHash)
            ->where('company_id', $account->company_id)
            ->first();

        if ($existing) {
            throw new \RuntimeException("This file has already been imported (Import #{$existing->id}).");
        }

        $import = BankStatementImport::create([
            'company_id'      => $account->company_id,
            'bank_account_id' => $account->id,
            'file_name'       => basename($filePath),
            'file_path'       => $filePath,
            'file_hash'       => $fileHash,
            'format'          => $format,
            'status'          => BankStatementImport::STATUS_PROCESSING,
            'imported_by'     => auth()->id(),
        ]);

        try {
            $rows = $this->parseFile($filePath, $format);

            if (empty($rows)) {
                $import->update([
                    'status'        => BankStatementImport::STATUS_FAILED,
                    'error_message' => 'No data rows found in the file.',
                ]);
                throw new \RuntimeException('No data rows found in the file.');
            }

            $batchId        = Str::uuid()->toString();
            $imported        = 0;
            $duplicates      = 0;
            $periodStart     = null;
            $periodEnd       = null;

            DB::transaction(function () use ($account, $rows, $batchId, &$imported, &$duplicates, &$periodStart, &$periodEnd) {
                foreach ($rows as $row) {
                    $transactionDate = $this->parseDate($row['date'] ?? $row['transaction_date'] ?? $row['txn_date'] ?? $row['posting_date'] ?? null);

                    if (! $transactionDate) {
                        continue;
                    }

                    // Track period
                    if (! $periodStart || $transactionDate < $periodStart) {
                        $periodStart = $transactionDate;
                    }
                    if (! $periodEnd || $transactionDate > $periodEnd) {
                        $periodEnd = $transactionDate;
                    }

                    $description    = trim($row['description'] ?? $row['narration'] ?? $row['particulars'] ?? $row['remarks'] ?? '');
                    $referenceNumber = trim($row['reference_number'] ?? $row['ref_no'] ?? $row['utr'] ?? $row['cheque_no'] ?? $row['ref'] ?? '');
                    $credit         = abs((float) str_replace(',', '', $row['credit'] ?? $row['credit_amount'] ?? $row['deposit'] ?? 0));
                    $debit          = abs((float) str_replace(',', '', $row['debit'] ?? $row['debit_amount'] ?? $row['withdrawal'] ?? 0));
                    $balance        = isset($row['balance']) || isset($row['running_balance']) || isset($row['closing_balance'])
                        ? (float) str_replace(',', '', $row['balance'] ?? $row['running_balance'] ?? $row['closing_balance'] ?? 0)
                        : null;

                    // Determine type and amount
                    if ($credit > 0) {
                        $transactionType = 'credit';
                        $amount = $credit;
                    } elseif ($debit > 0) {
                        $transactionType = 'debit';
                        $amount = $debit;
                    } else {
                        // Check for a combined amount field
                        $rawAmount = (float) str_replace(',', '', $row['amount'] ?? 0);
                        if ($rawAmount > 0) {
                            $transactionType = 'credit';
                            $amount = $rawAmount;
                        } elseif ($rawAmount < 0) {
                            $transactionType = 'debit';
                            $amount = abs($rawAmount);
                        } else {
                            continue; // Skip zero-amount rows
                        }
                    }

                    // Duplicate detection within same account
                    $existingTxn = BankTransaction::where('bank_account_id', $account->id)
                        ->where('transaction_date', $transactionDate)
                        ->where('amount', $amount)
                        ->where('transaction_type', $transactionType)
                        ->where('description', $description)
                        ->first();

                    if ($existingTxn) {
                        $duplicates++;
                        continue;
                    }

                    BankTransaction::create([
                        'company_id'       => $account->company_id,
                        'bank_account_id'  => $account->id,
                        'transaction_date' => $transactionDate,
                        'value_date'       => $this->parseDate($row['value_date'] ?? null),
                        'description'      => $description,
                        'reference_number' => $referenceNumber ?: null,
                        'transaction_type' => $transactionType,
                        'amount'           => $amount,
                        'running_balance'  => $balance,
                        'category'         => 'uncategorized',
                        'match_status'     => BankTransaction::MATCH_UNMATCHED,
                        'import_batch_id'  => $batchId,
                        'raw_data'         => $row,
                    ]);

                    $imported++;
                }
            });

            $import->update([
                'total_records'    => count($rows),
                'imported_records' => $imported,
                'duplicate_records' => $duplicates,
                'period_start'     => $periodStart,
                'period_end'       => $periodEnd,
                'status'           => $duplicates > 0 && $imported > 0
                    ? BankStatementImport::STATUS_PARTIALLY_COMPLETED
                    : ($imported > 0 ? BankStatementImport::STATUS_COMPLETED : BankStatementImport::STATUS_FAILED),
                'error_message'    => $imported === 0 ? 'No new transactions imported (all duplicates or invalid).' : null,
            ]);

            // Recalculate bank balance
            $account->recalculateBalance();

            return $import->fresh();
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            $import->update([
                'status'        => BankStatementImport::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
            Log::error('Bank statement import failed', ['error' => $e->getMessage(), 'file' => $filePath]);
            throw new \RuntimeException('Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Auto-reconcile unmatched bank transactions against settlements and payments.
     */
    public function autoReconcile(BankAccount $account, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = BankTransaction::where('bank_account_id', $account->id)
            ->where('match_status', BankTransaction::MATCH_UNMATCHED);

        if ($dateFrom) {
            $query->where('transaction_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('transaction_date', '<=', $dateTo);
        }

        $transactions = $query->get();
        $matched   = 0;
        $unmatched = 0;

        foreach ($transactions as $transaction) {
            $wasMatched = false;

            // Step 1: Match by UTR/reference number against payments
            if ($transaction->reference_number && ! $wasMatched) {
                $payment = Payment::where('company_id', $transaction->company_id)
                    ->where(function ($q) use ($transaction) {
                        $q->where('bank_reference', $transaction->reference_number)
                          ->orWhere('reference_number', $transaction->reference_number);
                    })
                    ->first();

                if ($payment) {
                    $this->applyMatch($transaction, Payment::class, $payment->id, 'auto_matched', 'marketplace_settlement');
                    $wasMatched = true;
                    $matched++;
                }
            }

            // Step 2: Match by reference number against settlements
            if ($transaction->reference_number && ! $wasMatched) {
                $settlement = Settlement::where('company_id', $transaction->company_id)
                    ->where('settlement_reference', $transaction->reference_number)
                    ->first();

                if ($settlement) {
                    $this->applyMatch($transaction, Settlement::class, $settlement->id, 'auto_matched', 'marketplace_settlement');
                    $wasMatched = true;
                    $matched++;
                }
            }

            // Step 3: Match by amount + date range against settlements (credits only)
            if (! $wasMatched && $transaction->transaction_type === 'credit') {
                $settlement = Settlement::where('company_id', $transaction->company_id)
                    ->where('net_payable', $transaction->amount)
                    ->whereBetween('settlement_date', [
                        $transaction->transaction_date->copy()->subDays(5),
                        $transaction->transaction_date->copy()->addDays(5),
                    ])
                    ->whereNotIn('status', [Settlement::STATUS_CLOSED])
                    ->first();

                if ($settlement) {
                    $this->applyMatch($transaction, Settlement::class, $settlement->id, 'auto_matched', 'marketplace_settlement');
                    $wasMatched = true;
                    $matched++;
                }
            }

            // Step 4: Apply reconciliation rules
            if (! $wasMatched) {
                $rules = ReconciliationRule::where('company_id', $transaction->company_id)
                    ->where('is_active', true)
                    ->where('auto_match', true)
                    ->orderBy('priority', 'desc')
                    ->get();

                foreach ($rules as $rule) {
                    if ($this->ruleMatches($rule, $transaction)) {
                        $transaction->update([
                            'category'     => $rule->category,
                            'match_status' => BankTransaction::MATCH_AUTO_MATCHED,
                            'matched_at'   => now(),
                        ]);
                        $wasMatched = true;
                        $matched++;
                        break;
                    }
                }
            }

            if (! $wasMatched) {
                $unmatched++;
            }
        }

        return [
            'matched'   => $matched,
            'unmatched' => $unmatched,
            'total'     => $matched + $unmatched,
        ];
    }

    /**
     * Manually match a transaction to a specific entity.
     */
    public function manualMatch(BankTransaction $transaction, string $entityType, int $entityId): void
    {
        $category = 'other';

        if ($entityType === Settlement::class || $entityType === 'Settlement') {
            $entityType = Settlement::class;
            $category = 'marketplace_settlement';
        } elseif ($entityType === Payment::class || $entityType === 'Payment') {
            $entityType = Payment::class;
            $category = 'marketplace_settlement';
        }

        $this->applyMatch($transaction, $entityType, $entityId, 'manually_matched', $category);
    }

    /**
     * Remove a match from a transaction.
     */
    public function unmatch(BankTransaction $transaction): void
    {
        $transaction->update([
            'match_status'       => BankTransaction::MATCH_UNMATCHED,
            'matched_entity_type' => null,
            'matched_entity_id'   => null,
            'matched_at'         => null,
            'matched_by'         => null,
            'category'           => BankTransaction::CATEGORY_UNCATEGORIZED,
        ]);
    }

    /**
     * Mark a transaction as ignored with a reason.
     */
    public function markIgnored(BankTransaction $transaction, string $reason): void
    {
        $transaction->update([
            'match_status' => BankTransaction::MATCH_IGNORED,
            'notes'        => $reason,
            'matched_at'   => now(),
            'matched_by'   => auth()->id(),
        ]);
    }

    /**
     * Get reconciliation summary for a bank account within a period.
     */
    public function getReconciliationSummary(BankAccount $account, string $period): array
    {
        $startDate = now()->startOfMonth();
        $endDate   = now()->endOfMonth();

        if ($period) {
            try {
                $startDate = \Carbon\Carbon::parse($period . '-01')->startOfMonth();
                $endDate   = $startDate->copy()->endOfMonth();
            } catch (\Exception $e) {
                // Fallback to current month
            }
        }

        $transactions = BankTransaction::where('bank_account_id', $account->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        $totalCredits  = $transactions->where('transaction_type', 'credit')->sum('amount');
        $totalDebits   = $transactions->where('transaction_type', 'debit')->sum('amount');
        $matchedAmount = $transactions->whereIn('match_status', [BankTransaction::MATCH_AUTO_MATCHED, BankTransaction::MATCH_MANUALLY_MATCHED])
            ->where('transaction_type', 'credit')
            ->sum('amount');
        $unmatchedAmount = $transactions->where('match_status', BankTransaction::MATCH_UNMATCHED)
            ->where('transaction_type', 'credit')
            ->sum('amount');

        $totalCount   = $transactions->count();
        $matchedCount = $transactions->whereIn('match_status', [BankTransaction::MATCH_AUTO_MATCHED, BankTransaction::MATCH_MANUALLY_MATCHED])->count();

        return [
            'total_credits'    => $totalCredits,
            'total_debits'     => $totalDebits,
            'matched_amount'   => $matchedAmount,
            'unmatched_amount' => $unmatchedAmount,
            'match_rate'       => $totalCount > 0 ? round($matchedCount / $totalCount * 100, 1) : 0,
            'total_count'      => $totalCount,
            'matched_count'    => $matchedCount,
            'unmatched_count'  => $transactions->where('match_status', BankTransaction::MATCH_UNMATCHED)->count(),
            'disputed_count'   => $transactions->where('match_status', BankTransaction::MATCH_DISPUTED)->count(),
            'ignored_count'    => $transactions->where('match_status', BankTransaction::MATCH_IGNORED)->count(),
        ];
    }

    /**
     * Get the calculated bank balance from transactions.
     */
    public function getBankBalance(BankAccount $account): float
    {
        return $account->getCalculatedBalance();
    }

    /**
     * Get suggested matches for an unmatched transaction.
     */
    public function getSuggestedMatches(BankTransaction $transaction): array
    {
        $suggestions = [];

        // Only suggest matches for credit transactions (incoming money)
        if ($transaction->transaction_type === 'credit') {
            // Match settlements by amount (within 5% tolerance)
            $settlements = Settlement::where('company_id', $transaction->company_id)
                ->whereBetween('net_payable', [
                    $transaction->amount * 0.95,
                    $transaction->amount * 1.05,
                ])
                ->whereNotIn('status', [Settlement::STATUS_CLOSED])
                ->with('marketplaceAccount.marketplace')
                ->limit(10)
                ->get();

            foreach ($settlements as $settlement) {
                $confidence = $settlement->net_payable == $transaction->amount ? 'high' : 'medium';

                // Boost confidence if reference matches
                if ($transaction->reference_number && $settlement->settlement_reference === $transaction->reference_number) {
                    $confidence = 'high';
                }

                $suggestions[] = [
                    'entity_type' => 'Settlement',
                    'entity_id'   => $settlement->id,
                    'label'       => $settlement->settlement_reference . ' - ' . ($settlement->marketplaceAccount?->account_name ?? 'N/A'),
                    'amount'      => $settlement->net_payable,
                    'date'        => $settlement->settlement_date->format('d M Y'),
                    'confidence'  => $confidence,
                ];
            }

            // Match payments by amount
            $payments = Payment::where('company_id', $transaction->company_id)
                ->whereBetween('amount', [
                    $transaction->amount * 0.95,
                    $transaction->amount * 1.05,
                ])
                ->where('status', '!=', Payment::STATUS_CONFIRMED)
                ->limit(10)
                ->get();

            foreach ($payments as $payment) {
                $confidence = $payment->amount == $transaction->amount ? 'high' : 'medium';

                if ($transaction->reference_number && ($payment->bank_reference === $transaction->reference_number || $payment->reference_number === $transaction->reference_number)) {
                    $confidence = 'high';
                }

                $suggestions[] = [
                    'entity_type' => 'Payment',
                    'entity_id'   => $payment->id,
                    'label'       => ($payment->reference_number ?? 'PAY-' . $payment->id) . ' - ' . $payment->payment_type_label,
                    'amount'      => $payment->amount,
                    'date'        => $payment->paid_at->format('d M Y'),
                    'confidence'  => $confidence,
                ];
            }
        }

        // Sort by confidence (high first)
        usort($suggestions, function ($a, $b) {
            $order = ['high' => 0, 'medium' => 1, 'low' => 2];
            return ($order[$a['confidence']] ?? 3) <=> ($order[$b['confidence']] ?? 3);
        });

        return $suggestions;
    }

    // ── Private Helpers ─────────────────────────────────────────

    private function applyMatch(BankTransaction $transaction, string $entityType, int $entityId, string $matchStatus, string $category): void
    {
        $transaction->update([
            'matched_entity_type' => $entityType,
            'matched_entity_id'   => $entityId,
            'match_status'        => $matchStatus,
            'category'            => $category,
            'matched_at'          => now(),
            'matched_by'          => auth()->id(),
        ]);
    }

    private function ruleMatches(ReconciliationRule $rule, BankTransaction $transaction): bool
    {
        if (! $rule->match_pattern) {
            return false;
        }

        return match ($rule->match_field) {
            'reference_number' => $transaction->reference_number && preg_match('/' . $rule->match_pattern . '/i', $transaction->reference_number),
            'description'      => preg_match('/' . $rule->match_pattern . '/i', $transaction->description),
            'amount'           => (float) $transaction->amount === (float) $rule->match_pattern,
            'combination'      => preg_match('/' . $rule->match_pattern . '/i', $transaction->description . ' ' . $transaction->reference_number),
            default            => false,
        };
    }

    private function parseFile(string $filePath, string $format): array
    {
        return match ($format) {
            'csv'   => $this->parseCsv($filePath),
            'excel' => $this->parseCsv($filePath), // Fallback; in production use maatwebsite/excel
            default => $this->parseCsv($filePath),
        };
    }

    private function parseCsv(string $filePath): array
    {
        $rows   = [];
        $handle = fopen($filePath, 'r');

        if (! $handle) {
            throw new \RuntimeException('Unable to open file.');
        }

        $headers = fgetcsv($handle);

        if (! $headers) {
            fclose($handle);
            return [];
        }

        // Normalize headers
        $headers = array_map(function ($h) {
            return strtolower(trim(str_replace([' ', '-', '.'], '_', $h)));
        }, $headers);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === count($headers)) {
                $rows[] = array_combine($headers, $data);
            }
        }

        fclose($handle);

        return $rows;
    }

    private function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $value = trim($value);

        // Try common date formats
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'd M Y', 'd-M-Y', 'Y/m/d'];

        foreach ($formats as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $value);
                if ($date) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Fallback: try Carbon's natural parser
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
