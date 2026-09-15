<?php

namespace App\Services;

use App\Models\MarketplaceAccount;
use App\Models\Order;
use App\Models\Settlement;
use App\Models\SettlementLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettlementService
{
    public function __construct(
        private FlipkartSettlementParser $flipkartSettlementParser,
    ) {}
    /**
     * Import a settlement file (CSV/Excel) and create settlement + lines.
     * Uses file hash for deduplication.
     */
    public function importSettlementFile(MarketplaceAccount $account, string $filePath): Settlement
    {
        $fileHash = md5_file($filePath);

        // Dedup check
        $existing = Settlement::where('file_hash', $fileHash)
            ->where('marketplace_account_id', $account->id)
            ->first();

        if ($existing) {
            throw new \RuntimeException("This file has already been imported (Settlement #{$existing->id}).");
        }

        $rows = $this->parseFileWithDetection($filePath, $account);

        if (empty($rows)) {
            throw new \RuntimeException('No data rows found in the file.');
        }

        return DB::transaction(function () use ($account, $filePath, $fileHash, $rows) {
            $settlement = Settlement::create([
                'company_id'             => $account->company_id,
                'marketplace_account_id' => $account->id,
                'settlement_reference'   => $this->extractSettlementReference($rows),
                'settlement_date'        => now()->toDateString(),
                'total_orders'           => count($rows),
                'status'                 => Settlement::STATUS_IMPORTED,
                'imported_at'            => now(),
                'file_path'              => $filePath,
                'file_hash'              => $fileHash,
            ]);

            $totalOrderAmount   = 0;
            $totalShippingFee   = 0;
            $totalCommission    = 0;
            $totalTcs           = 0;
            $totalTds           = 0;
            $totalPenalty       = 0;
            $totalOther         = 0;
            $totalNet           = 0;

            foreach ($rows as $row) {
                $sellingPrice  = (float) ($row['selling_price'] ?? $row['order_amount'] ?? $row['total_amount'] ?? 0);
                $shippingFee   = (float) ($row['shipping_fee'] ?? $row['shipping_charges'] ?? 0);
                $commission    = (float) ($row['marketplace_commission'] ?? $row['commission'] ?? $row['platform_fee'] ?? 0);
                $tcs           = (float) ($row['tcs_amount'] ?? $row['tcs'] ?? 0);
                $tds           = (float) ($row['tds_amount'] ?? $row['tds'] ?? 0);
                $penalty       = (float) ($row['penalty_amount'] ?? $row['penalty'] ?? 0);
                $otherDed      = (float) ($row['other_deductions'] ?? $row['other_charges'] ?? 0);
                $netAmount     = (float) ($row['net_amount'] ?? $row['settlement_amount'] ?? ($sellingPrice - $commission - $tcs - $tds - $penalty - $otherDed));

                SettlementLine::create([
                    'settlement_id'          => $settlement->id,
                    'marketplace_order_id'   => $row['marketplace_order_id'] ?? $row['order_id'] ?? $row['order_no'] ?? '',
                    'sub_order_id'           => $row['sub_order_id'] ?? $row['sub_order_no'] ?? null,
                    'product_name'           => $row['product_name'] ?? $row['item_name'] ?? null,
                    'sku_code'               => $row['sku_code'] ?? $row['sku'] ?? $row['seller_sku'] ?? null,
                    'quantity'               => (int) ($row['quantity'] ?? $row['qty'] ?? 1),
                    'selling_price'          => $sellingPrice,
                    'shipping_fee'           => $shippingFee,
                    'marketplace_commission' => $commission,
                    'tcs_amount'             => $tcs,
                    'tds_amount'             => $tds,
                    'penalty_amount'         => $penalty,
                    'other_deductions'       => $otherDed,
                    'net_amount'             => $netAmount,
                    'settlement_type'        => $this->detectSettlementType($row),
                    'match_status'           => SettlementLine::MATCH_UNMATCHED,
                ]);

                $totalOrderAmount += $sellingPrice;
                $totalShippingFee += $shippingFee;
                $totalCommission  += $commission;
                $totalTcs         += $tcs;
                $totalTds         += $tds;
                $totalPenalty     += $penalty;
                $totalOther       += $otherDed;
                $totalNet         += $netAmount;
            }

            $settlement->update([
                'total_order_amount'    => $totalOrderAmount,
                'total_shipping_fee'    => $totalShippingFee,
                'total_commission'      => $totalCommission,
                'total_tcs'             => $totalTcs,
                'total_tds'             => $totalTds,
                'total_penalty'         => $totalPenalty,
                'total_other_deductions' => $totalOther,
                'net_payable'           => $totalNet,
            ]);

            return $settlement->fresh();
        });
    }

    /**
     * Auto-reconcile settlement lines by matching marketplace_order_id to internal orders.
     */
    public function reconcileSettlement(Settlement $settlement): array
    {
        $settlement->update(['status' => Settlement::STATUS_PROCESSING]);

        $lines = $settlement->lines()->where('match_status', SettlementLine::MATCH_UNMATCHED)->get();
        $matched   = 0;
        $unmatched = 0;

        foreach ($lines as $line) {
            $order = Order::where('company_id', $settlement->company_id)
                ->where('marketplace_order_id', $line->marketplace_order_id)
                ->first();

            if ($order) {
                $line->update([
                    'order_id'     => $order->id,
                    'match_status' => SettlementLine::MATCH_MATCHED,
                ]);
                $matched++;
            } else {
                $unmatched++;
            }
        }

        // Determine settlement status after reconciliation
        $totalLines      = $settlement->lines()->count();
        $totalMatched    = $settlement->lines()->where('match_status', SettlementLine::MATCH_MATCHED)->count();
        $totalDisputed   = $settlement->lines()->where('match_status', SettlementLine::MATCH_DISPUTED)->count();

        if ($totalMatched === $totalLines) {
            $settlement->update(['status' => Settlement::STATUS_RECONCILED]);
        } elseif ($totalMatched > 0) {
            $settlement->update(['status' => Settlement::STATUS_PARTIALLY_RECONCILED]);
        } elseif ($totalDisputed > 0) {
            $settlement->update(['status' => Settlement::STATUS_DISPUTED]);
        } else {
            $settlement->update(['status' => Settlement::STATUS_IMPORTED]);
        }

        return [
            'matched'   => $matched,
            'unmatched' => $unmatched,
            'total'     => $matched + $unmatched,
        ];
    }

    /**
     * Get reconciliation summary counts and amounts.
     */
    public function getReconciliationSummary(Settlement $settlement): array
    {
        $lines = $settlement->lines;

        return [
            'total_lines'     => $lines->count(),
            'matched_count'   => $lines->where('match_status', SettlementLine::MATCH_MATCHED)->count(),
            'unmatched_count' => $lines->where('match_status', SettlementLine::MATCH_UNMATCHED)->count(),
            'disputed_count'  => $lines->where('match_status', SettlementLine::MATCH_DISPUTED)->count(),
            'ignored_count'   => $lines->where('match_status', SettlementLine::MATCH_IGNORED)->count(),
            'matched_amount'  => $lines->where('match_status', SettlementLine::MATCH_MATCHED)->sum('net_amount'),
            'unmatched_amount' => $lines->where('match_status', SettlementLine::MATCH_UNMATCHED)->sum('net_amount'),
            'disputed_amount' => $lines->where('match_status', SettlementLine::MATCH_DISPUTED)->sum('net_amount'),
            'match_percentage' => $lines->count() > 0
                ? round($lines->where('match_status', SettlementLine::MATCH_MATCHED)->count() / $lines->count() * 100, 1)
                : 0,
        ];
    }

    /**
     * Manually match a settlement line to an internal order.
     */
    public function markLineMatched(SettlementLine $line, Order $order): void
    {
        $line->update([
            'order_id'     => $order->id,
            'match_status' => SettlementLine::MATCH_MATCHED,
            'match_notes'  => 'Manually matched by user.',
        ]);
    }

    /**
     * Flag a settlement line as disputed.
     */
    public function markLineDisputed(SettlementLine $line, string $reason): void
    {
        $line->update([
            'match_status' => SettlementLine::MATCH_DISPUTED,
            'match_notes'  => $reason,
        ]);
    }

    /**
     * Close/finalize a settlement.
     */
    public function closeSettlement(Settlement $settlement): void
    {
        $settlement->update(['status' => Settlement::STATUS_CLOSED]);
    }

    // ── Private Helpers ─────────────────────────────────────────

    /**
     * Detect file format and parse with appropriate parser.
     */
    private function parseFileWithDetection(string $filePath, MarketplaceAccount $account): array
    {
        // Check if the marketplace account is Flipkart
        $marketplace = $account->marketplace;
        $isFlipkart = $marketplace && strtolower($marketplace->code) === 'flipkart';

        if ($isFlipkart) {
            return $this->flipkartSettlementParser->parseSettlementFile($filePath);
        }

        // Auto-detect from CSV headers
        $handle = fopen($filePath, 'r');
        if ($handle) {
            $headers = fgetcsv($handle);
            fclose($handle);

            if ($headers && FlipkartSettlementParser::isFlipkartFormat($headers)) {
                return $this->flipkartSettlementParser->parseSettlementFile($filePath);
            }
        }

        // Default to generic parser
        return $this->parseFile($filePath);
    }

    /**
     * Parse CSV or Excel file into an array of rows.
     */
    private function parseFile(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['csv', 'txt'])) {
            return $this->parseCsv($filePath);
        }

        // For Excel files, attempt CSV parsing as fallback
        // In production, integrate maatwebsite/excel or similar
        return $this->parseCsv($filePath);
    }

    /**
     * Parse a CSV file into associative arrays.
     */
    private function parseCsv(string $filePath): array
    {
        $rows = [];
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

    /**
     * Extract settlement reference from data rows.
     */
    private function extractSettlementReference(array $rows): string
    {
        $firstRow = $rows[0] ?? [];

        return $firstRow['settlement_id'] ?? $firstRow['settlement_reference'] ?? $firstRow['settlement_no'] ?? 'STL-' . now()->format('YmdHis');
    }

    /**
     * Detect settlement line type from row data.
     */
    private function detectSettlementType(array $row): string
    {
        $type = strtolower($row['type'] ?? $row['transaction_type'] ?? $row['order_type'] ?? '');

        if (str_contains($type, 'return') || str_contains($type, 'refund')) {
            return SettlementLine::TYPE_RETURN;
        }
        if (str_contains($type, 'penalty')) {
            return SettlementLine::TYPE_PENALTY;
        }
        if (str_contains($type, 'adjust')) {
            return SettlementLine::TYPE_ADJUSTMENT;
        }
        if (str_contains($type, 'compensat')) {
            return SettlementLine::TYPE_COMPENSATION;
        }

        return SettlementLine::TYPE_SALE;
    }
}
