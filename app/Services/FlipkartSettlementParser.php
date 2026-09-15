<?php

namespace App\Services;

class FlipkartSettlementParser
{
    /**
     * Flipkart column name mapping to internal field names.
     */
    private const COLUMN_MAP = [
        'order_id'              => 'marketplace_order_id',
        'order_item_id'         => 'sub_order_id',
        'order_date'            => 'order_date',
        'sku'                   => 'sku_code',
        'seller_sku'            => 'sku_code',
        'fsn'                   => 'fsn',
        'product_title'         => 'product_name',
        'product_name'          => 'product_name',
        'quantity'              => 'quantity',
        'qty'                   => 'quantity',
        'selling_price'         => 'selling_price',
        'item_selling_price'    => 'selling_price',
        'total_amount'          => 'selling_price',
        'shipping_fee'          => 'shipping_fee',
        'shipping_charges'      => 'shipping_fee',
        'marketplace_fee'       => 'marketplace_commission',
        'commission'            => 'marketplace_commission',
        'platform_fee'          => 'marketplace_commission',
        'collection_fee'        => 'collection_fee',
        'fixed_fee'             => 'fixed_fee',
        'tax_on_commission'     => 'tcs_amount',
        'gst_on_commission'     => 'tcs_amount',
        'tcs'                   => 'tcs_amount',
        'tcs_amount'            => 'tcs_amount',
        'tds'                   => 'tds_amount',
        'tds_amount'            => 'tds_amount',
        'settlement_value'      => 'net_amount',
        'net_amount'            => 'net_amount',
        'total_settlement'      => 'net_amount',
        'payment_type'          => 'payment_type',
        'payment_mode'          => 'payment_type',
        'neft_utr'              => 'neft_utr',
        'utr_number'            => 'neft_utr',
        'settlement_id'         => 'settlement_reference',
        'settlement_reference'  => 'settlement_reference',
        'type'                  => 'type',
        'transaction_type'      => 'type',
        'order_type'            => 'type',
        'penalty'               => 'penalty_amount',
        'penalty_amount'        => 'penalty_amount',
        'protection_fund'       => 'other_deductions',
        'other_deductions'      => 'other_deductions',
    ];

    /**
     * Flipkart-specific header keywords to detect this is a Flipkart settlement file.
     */
    private const FLIPKART_SIGNATURES = [
        'neft_utr',
        'utr_number',
        'settlement_value',
        'marketplace_fee',
        'collection_fee',
        'tax_on_commission',
        'protection_fund',
        'fsn',
    ];

    /**
     * Parse a Flipkart settlement CSV file and return structured data.
     *
     * @return array<int, array>
     */
    public function parseSettlementFile(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');

        if (! $handle) {
            throw new \RuntimeException('Unable to open settlement file.');
        }

        $rawHeaders = fgetcsv($handle);

        if (! $rawHeaders) {
            fclose($handle);
            return [];
        }

        $headers = $this->normalizeColumns($rawHeaders);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($headers)) {
                continue;
            }

            $row = array_combine($headers, $data);

            // Normalize numeric fields
            $row = $this->normalizeNumericFields($row);

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Map Flipkart CSV column names to internal field names.
     *
     * @param  array<string> $headers  Raw CSV header row
     * @return array<string>           Normalized internal field names
     */
    public function normalizeColumns(array $headers): array
    {
        return array_map(function ($header) {
            $normalized = strtolower(trim(str_replace([' ', '-', '.'], '_', $header)));

            return self::COLUMN_MAP[$normalized] ?? $normalized;
        }, $headers);
    }

    /**
     * Aggregate settlement data into summary totals.
     *
     * @param  array<int, array> $data  Parsed settlement rows
     * @return array  Summary with counts and amounts
     */
    public function getSettlementSummary(array $data): array
    {
        $totalOrders       = count($data);
        $totalSellingPrice = 0;
        $totalShippingFee  = 0;
        $totalCommission   = 0;
        $totalTcs          = 0;
        $totalTds          = 0;
        $totalPenalty      = 0;
        $totalOther        = 0;
        $totalNet          = 0;
        $codCount          = 0;
        $prepaidCount      = 0;

        foreach ($data as $row) {
            $totalSellingPrice += (float) ($row['selling_price'] ?? 0);
            $totalShippingFee  += (float) ($row['shipping_fee'] ?? 0);
            $totalCommission   += (float) ($row['marketplace_commission'] ?? $row['commission'] ?? 0);
            $totalTcs          += (float) ($row['tcs_amount'] ?? 0);
            $totalTds          += (float) ($row['tds_amount'] ?? 0);
            $totalPenalty      += (float) ($row['penalty_amount'] ?? 0);
            $totalOther        += (float) ($row['other_deductions'] ?? 0);
            $totalNet          += (float) ($row['net_amount'] ?? $row['settlement_value'] ?? 0);

            $paymentType = strtolower($row['payment_type'] ?? '');
            if ($paymentType === 'cod') {
                $codCount++;
            } else {
                $prepaidCount++;
            }
        }

        return [
            'total_orders'        => $totalOrders,
            'total_selling_price' => round($totalSellingPrice, 2),
            'total_shipping_fee'  => round($totalShippingFee, 2),
            'total_commission'    => round($totalCommission, 2),
            'total_tcs'           => round($totalTcs, 2),
            'total_tds'           => round($totalTds, 2),
            'total_penalty'       => round($totalPenalty, 2),
            'total_other'         => round($totalOther, 2),
            'total_net'           => round($totalNet, 2),
            'cod_orders'          => $codCount,
            'prepaid_orders'      => $prepaidCount,
        ];
    }

    /**
     * Detect if a CSV file's headers match Flipkart settlement format.
     *
     * @param  array<string> $headers  Normalized lowercase headers
     */
    public static function isFlipkartFormat(array $headers): bool
    {
        $normalized = array_map(function ($h) {
            return strtolower(trim(str_replace([' ', '-', '.'], '_', $h)));
        }, $headers);

        $matches = 0;
        foreach (self::FLIPKART_SIGNATURES as $sig) {
            if (in_array($sig, $normalized)) {
                $matches++;
            }
        }

        // If 2+ Flipkart-specific columns found, consider it Flipkart
        return $matches >= 2;
    }

    /**
     * Normalize numeric string fields to proper float values.
     */
    private function normalizeNumericFields(array $row): array
    {
        $numericKeys = [
            'selling_price', 'shipping_fee', 'marketplace_commission',
            'collection_fee', 'fixed_fee', 'tcs_amount', 'tds_amount',
            'penalty_amount', 'other_deductions', 'net_amount',
        ];

        foreach ($numericKeys as $key) {
            if (isset($row[$key])) {
                $row[$key] = (float) str_replace(',', '', $row[$key]);
            }
        }

        if (isset($row['quantity'])) {
            $row['quantity'] = (int) $row['quantity'];
        }

        return $row;
    }
}
