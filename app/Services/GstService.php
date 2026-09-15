<?php

namespace App\Services;

use App\Models\GstEntry;
use App\Models\Order;
use App\Models\Settlement;
use App\Models\TcsTdsRecord;
use Illuminate\Support\Facades\DB;

class GstService
{
    /**
     * Generate GST entry from an order using design's hsn_code and gst_rate.
     */
    public function generateGstEntry(Order $order): ?GstEntry
    {
        $order->load('subOrders.sku.variant.product.design');

        $firstSub = $order->subOrders->first();
        $design   = $firstSub?->sku?->variant?->product?->design;

        if (! $design) {
            return null;
        }

        $hsnCode  = $design->hsn_code ?? $order->hsn_code ?? '6211';
        $gstRate  = (float) ($design->gst_rate ?? 5);
        $taxable  = (float) ($order->taxable_value ?? $order->total_amount ?? 0);
        $supply   = $order->customer_state ?? null;

        $gstBreakdown = $this->calculateGst($taxable, $gstRate, $supply);

        return GstEntry::create([
            'company_id'      => $order->company_id,
            'order_id'        => $order->id,
            'invoice_number'  => $order->invoice_number,
            'invoice_date'    => $order->invoice_date ?? $order->order_date,
            'gst_type'        => GstEntry::TYPE_SALE,
            'place_of_supply' => $supply,
            'hsn_code'        => $hsnCode,
            'taxable_amount'  => $taxable,
            'cgst_rate'       => $gstBreakdown['cgst_rate'],
            'cgst_amount'     => $gstBreakdown['cgst_amount'],
            'sgst_rate'       => $gstBreakdown['sgst_rate'],
            'sgst_amount'     => $gstBreakdown['sgst_amount'],
            'igst_rate'       => $gstBreakdown['igst_rate'],
            'igst_amount'     => $gstBreakdown['igst_amount'],
            'total_gst'       => $gstBreakdown['total_gst'],
            'total_amount'    => $taxable + $gstBreakdown['total_gst'],
            'status'          => GstEntry::STATUS_DRAFT,
        ]);
    }

    /**
     * Calculate GST split: CGST+SGST for intra-state, IGST for inter-state.
     *
     * @param  float  $amount        Taxable amount
     * @param  float  $rate          Total GST rate (e.g. 5, 12, 18, 28)
     * @param  string|null $placeOfSupply   Customer state code
     * @param  string $companyState  Company's state code (default Gujarat = '24')
     */
    public function calculateGst(float $amount, float $rate, ?string $placeOfSupply, string $companyState = '24'): array
    {
        $isInterState = $placeOfSupply && strtolower(trim($placeOfSupply)) !== strtolower(trim($companyState));

        // Map state names to codes for comparison
        $gujaratNames = ['gujarat', 'gj', '24'];
        $supplyNormalized = strtolower(trim($placeOfSupply ?? ''));

        if (in_array($supplyNormalized, $gujaratNames)) {
            $isInterState = false;
        } elseif ($placeOfSupply) {
            $isInterState = true;
        }

        if ($isInterState) {
            return [
                'cgst_rate'   => 0,
                'cgst_amount' => 0,
                'sgst_rate'   => 0,
                'sgst_amount' => 0,
                'igst_rate'   => $rate,
                'igst_amount' => round($amount * $rate / 100, 2),
                'total_gst'   => round($amount * $rate / 100, 2),
            ];
        }

        $halfRate = $rate / 2;
        $halfAmount = round($amount * $halfRate / 100, 2);

        return [
            'cgst_rate'   => $halfRate,
            'cgst_amount' => $halfAmount,
            'sgst_rate'   => $halfRate,
            'sgst_amount' => $halfAmount,
            'igst_rate'   => 0,
            'igst_amount' => 0,
            'total_gst'   => $halfAmount * 2,
        ];
    }

    /**
     * Get GST summary aggregated by HSN code for a given period (YYYY-MM).
     */
    public function getGstSummary(int $companyId, string $period): array
    {
        $parts = explode('-', $period);
        $year  = $parts[0] ?? now()->year;
        $month = $parts[1] ?? now()->month;

        return GstEntry::where('company_id', $companyId)
            ->whereYear('invoice_date', $year)
            ->whereMonth('invoice_date', $month)
            ->whereIn('status', [GstEntry::STATUS_DRAFT, GstEntry::STATUS_FILED])
            ->select(
                'hsn_code',
                DB::raw('COUNT(*) as total_entries'),
                DB::raw('SUM(taxable_amount) as total_taxable'),
                DB::raw('SUM(cgst_amount) as total_cgst'),
                DB::raw('SUM(sgst_amount) as total_sgst'),
                DB::raw('SUM(igst_amount) as total_igst'),
                DB::raw('SUM(total_gst) as total_gst'),
                DB::raw('SUM(total_amount) as total_amount'),
            )
            ->groupBy('hsn_code')
            ->orderBy('hsn_code')
            ->get()
            ->toArray();
    }

    /**
     * Compute TCS/TDS records from a settlement.
     */
    public function computeTcsTds(Settlement $settlement): array
    {
        $records = [];

        $totalTcs = (float) $settlement->total_tcs;
        $totalTds = (float) $settlement->total_tds;
        $totalOrderAmount = (float) $settlement->total_order_amount;

        // Determine financial year and quarter
        $settlementDate = $settlement->settlement_date;
        $fy = $this->getFinancialYear($settlementDate);
        $quarter = $this->getQuarter($settlementDate);

        if ($totalTcs > 0) {
            $tcsRate = $totalOrderAmount > 0 ? round(($totalTcs / $totalOrderAmount) * 100, 2) : 1.00;

            $records[] = TcsTdsRecord::updateOrCreate(
                [
                    'company_id'             => $settlement->company_id,
                    'record_type'            => TcsTdsRecord::TYPE_TCS,
                    'marketplace_account_id' => $settlement->marketplace_account_id,
                    'settlement_id'          => $settlement->id,
                ],
                [
                    'section_code'    => '206C(1H)',
                    'financial_year'  => $fy,
                    'quarter'         => $quarter,
                    'gross_amount'    => $totalOrderAmount,
                    'rate'            => $tcsRate,
                    'amount'          => $totalTcs,
                    'status'          => TcsTdsRecord::STATUS_COMPUTED,
                ]
            );
        }

        if ($totalTds > 0) {
            $tdsRate = $totalOrderAmount > 0 ? round(($totalTds / $totalOrderAmount) * 100, 2) : 1.00;

            $records[] = TcsTdsRecord::updateOrCreate(
                [
                    'company_id'             => $settlement->company_id,
                    'record_type'            => TcsTdsRecord::TYPE_TDS,
                    'marketplace_account_id' => $settlement->marketplace_account_id,
                    'settlement_id'          => $settlement->id,
                ],
                [
                    'section_code'    => '194-O',
                    'financial_year'  => $fy,
                    'quarter'         => $quarter,
                    'gross_amount'    => $totalOrderAmount,
                    'rate'            => $tdsRate,
                    'amount'          => $totalTds,
                    'status'          => TcsTdsRecord::STATUS_COMPUTED,
                ]
            );
        }

        return $records;
    }

    // ── Private Helpers ─────────────────────────────────────────

    /**
     * Get Indian financial year string (e.g., '2026-27').
     */
    private function getFinancialYear($date): string
    {
        $month = $date->month;
        $year  = $date->year;

        if ($month <= 3) {
            return ($year - 1) . '-' . substr($year, 2);
        }

        return $year . '-' . substr($year + 1, 2);
    }

    /**
     * Get Indian financial quarter (Q1=Apr-Jun, Q2=Jul-Sep, Q3=Oct-Dec, Q4=Jan-Mar).
     */
    private function getQuarter($date): string
    {
        $month = $date->month;

        return match (true) {
            $month >= 4 && $month <= 6   => 'Q1',
            $month >= 7 && $month <= 9   => 'Q2',
            $month >= 10 && $month <= 12 => 'Q3',
            default                      => 'Q4',
        };
    }
}
