<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class MeeshoPdfParser
{
    /**
     * Parse Meesho order label PDF -- each page = one sub-order label + tax invoice.
     *
     * Extracts ALL fields: customer details, address, courier, AWB, SKU, size, qty,
     * order number, invoice details (number, date, amount), tax details (HSN, taxable
     * value, IGST/SGST/CGST), other charges, payment type, and tracking number.
     *
     * @return array{orders: array, errors: array, total_pages: int}
     */
    public function parse(string $filePath): array
    {
        $errors = [];
        $orders = [];
        $totalPages = 0;

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $pages = $pdf->getPages();
            $totalPages = count($pages);

            foreach ($pages as $pageNum => $page) {
                try {
                    $text = $page->getText();
                    $extracted = $this->extractFromMeeshoLabel($text, $pageNum + 1);
                    if ($extracted) {
                        $orders[] = $extracted;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Page " . ($pageNum + 1) . ": " . $e->getMessage();
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'PDF parse error: ' . $e->getMessage();
        }

        // Deduplicate by sub_order_number
        $seen = [];
        $unique = [];
        foreach ($orders as $order) {
            $key = $order['sub_order_number'] ?? $order['marketplace_order_id'] ?? '';
            if ($key && ! isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $order;
            } elseif (! $key) {
                $unique[] = $order;
            }
        }

        return [
            'orders'      => $unique,
            'errors'      => $errors,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Extract all structured data from a single Meesho label page.
     */
    private function extractFromMeeshoLabel(string $text, int $pageNum): ?array
    {
        $data = [
            'marketplace_order_id' => null,
            'sub_order_number'     => null,
            'sku'                  => null,
            'product_name'         => null,
            'size'                 => null,
            'quantity'             => 1,
            'color'                => null,
            'customer_name'        => null,
            'customer_address'     => null,
            'customer_city'        => null,
            'customer_state'       => null,
            'customer_pincode'     => null,
            'payment_type'         => null,
            'courier_partner'      => null,
            'awb_number'           => null,
            'tracking_number'      => null,
            'invoice_number'       => null,
            'invoice_date'         => null,
            'invoice_amount'       => null,
            'hsn_code'             => null,
            'taxable_value'        => null,
            'igst'                 => null,
            'sgst'                 => null,
            'cgst'                 => null,
            'tax_amount'           => null,
            'other_charges'        => null,
            'order_date'           => null,
            'page_number'          => $pageNum,
            'raw_text'             => $text,
        ];

        // ── SKU, Size, Qty, Color, Order No from product details section ──
        if (preg_match('/SKU\s*Size\s*Qty\s*Color\s*Order\s*No\.?\s*\n(.+)/s', $text, $m)) {
            $detailLine = trim($m[1]);
            if (preg_match('/^([^\s]+)\s*(XS|S|M|L|XL|XXL|XXXL|2XL|3XL|4XL|5XL|Free\s*Size)\s*(\d+)\s*(\S+)\s*(\d+(?:_\d+)?)/i', $detailLine, $dm)) {
                $data['sku'] = trim($dm[1]);
                $data['size'] = trim($dm[2]);
                $data['quantity'] = (int) $dm[3];
                $color = trim($dm[4]);
                $data['color'] = $color !== 'NA' ? $color : null;
                $data['sub_order_number'] = trim($dm[5]);
            } elseif (preg_match('/(\S+)\s+(\S+)\s+(\d+)\s+(\S+)\s+(\d+(?:_\d+)?)/i', $detailLine, $dm)) {
                $data['sku'] = trim($dm[1]);
                $data['size'] = trim($dm[2]);
                $data['quantity'] = (int) $dm[3];
                $color = trim($dm[4]);
                $data['color'] = $color !== 'NA' ? $color : null;
                $data['sub_order_number'] = trim($dm[5]);
            }
        }

        // ── Purchase Order No (main Meesho order ID) ──
        if (preg_match('/Purchase\s*Order\s*No\.?\s*\n?\s*(\d+)/i', $text, $m)) {
            $data['marketplace_order_id'] = trim($m[1]);
        }

        // Derive marketplace_order_id from sub_order_number if missing
        if (! $data['marketplace_order_id'] && $data['sub_order_number']) {
            $data['marketplace_order_id'] = preg_replace('/_\d+$/', '', $data['sub_order_number']);
        }

        // ── Product Description from invoice ──
        if (preg_match('/Description\s*HSN.*?\n(.+?)\s*\d{4}/s', $text, $m)) {
            $desc = trim(preg_replace('/\s+/', ' ', $m[1]));
            $desc = preg_replace('/\s*-\s*(XS|S|M|L|XL|XXL|XXXL|2XL|3XL|4XL|5XL|Free\s*Size)\s*$/i', '', $desc);
            $data['product_name'] = $desc;
        }

        // ── Customer Name ──
        if (preg_match('/Customer\s*Address\s*\n\s*(.+)/i', $text, $m)) {
            $data['customer_name'] = trim($m[1]);
        }

        // ── Customer Address (multi-line between name and city/state/pin) ──
        if (preg_match('/Customer\s*Address\s*\n\s*.+\n([\s\S]*?)(\w[\w\s]+),\s*([A-Za-z\s]+),\s*(\d{6})/i', $text, $m)) {
            $data['customer_address'] = trim(preg_replace('/\s+/', ' ', $m[1]));
            $data['customer_city'] = trim($m[2]);
            $data['customer_state'] = trim($m[3]);
            $data['customer_pincode'] = trim($m[4]);
        } elseif (preg_match('/(\w[\w\s]+),\s*([A-Za-z\s]+),\s*(\d{6})/', $text, $m)) {
            $data['customer_city'] = trim($m[1]);
            $data['customer_state'] = trim($m[2]);
            $data['customer_pincode'] = trim($m[3]);
        }

        // ── Payment Type ──
        if (stripos($text, 'Prepaid') !== false || stripos($text, 'Do not collect cash') !== false) {
            $data['payment_type'] = 'prepaid';
        } elseif (stripos($text, 'COD') !== false || stripos($text, 'Check the payable amount') !== false) {
            $data['payment_type'] = 'cod';
        }

        // ── Courier Partner ──
        $courierPatterns = [
            'Delhivery'    => '/\bDelhivery\b/i',
            'Valmo'        => '/\bValmo\w*/i',
            'Shadowfax'    => '/\bShadowfax\b/i',
            'Xpress Bees'  => '/\bXpress\s*Bees\b/i',
            'Ecom Express'  => '/\bEcom\s*Express\b/i',
            'DTDC'          => '/\bDTDC\b/i',
            'BlueDart'      => '/\bBlueDart\b/i',
            'India Post'    => '/\bIndia\s*Post\b/i',
            'E-Kart'        => '/\bE-?Kart\b/i',
        ];

        foreach ($courierPatterns as $name => $pattern) {
            if (preg_match($pattern, $text)) {
                $data['courier_partner'] = $name;
                break;
            }
        }

        // ── AWB / Tracking Number (courier-specific patterns) ──
        if (preg_match('/VL\d{10,}/i', $text, $m)) {
            $data['awb_number'] = trim($m[0]);
            $data['tracking_number'] = $data['awb_number'];
            if (! $data['courier_partner']) {
                $data['courier_partner'] = 'Valmo';
            }
        } elseif (preg_match('/SF\d{10,}\w*/i', $text, $m)) {
            $data['awb_number'] = trim($m[0]);
            $data['tracking_number'] = $data['awb_number'];
            if (! $data['courier_partner']) {
                $data['courier_partner'] = 'Shadowfax';
            }
        } elseif (preg_match('/FMPC\d{10,}/i', $text, $m)) {
            $data['awb_number'] = trim($m[0]);
            $data['tracking_number'] = $data['awb_number'];
            if (! $data['courier_partner']) {
                $data['courier_partner'] = 'E-Kart';
            }
        } elseif ($data['courier_partner'] === 'Delhivery' && preg_match('/\b(\d{16})\b/', $text, $m)) {
            $data['awb_number'] = trim($m[1]);
            $data['tracking_number'] = $data['awb_number'];
        } elseif (preg_match('/\b(\d{15})\b/', $text, $m)) {
            $data['awb_number'] = trim($m[1]);
            $data['tracking_number'] = $data['awb_number'];
            if (! $data['courier_partner']) {
                $data['courier_partner'] = 'Xpress Bees';
            }
        }

        // ── Invoice Number ──
        if (preg_match('/Invoice\s*No\.?\s*\n?\s*([a-zA-Z0-9]+)/i', $text, $m)) {
            $data['invoice_number'] = trim($m[1]);
        }

        // ── HSN Code ──
        if (preg_match('/\b(6\d{3,5})\b/', $text, $m)) {
            $data['hsn_code'] = trim($m[1]);
        }

        // ── Invoice Amount (Grand Total) ──
        if (preg_match('/Total\s*Rs\.[\d,.]+\s*Rs\.([\d,.]+)/i', $text, $m)) {
            $data['invoice_amount'] = (float) str_replace(',', '', $m[1]);
        } elseif (preg_match('/Grand\s*Total.*?Rs\.\s*([\d,.]+)/i', $text, $m)) {
            $data['invoice_amount'] = (float) str_replace(',', '', $m[1]);
        }

        // ── Taxable Value ──
        if (preg_match('/Taxable\s*Value\s*\n?\s*Rs\.\s*([\d,.]+)/i', $text, $m)) {
            $data['taxable_value'] = (float) str_replace(',', '', $m[1]);
        } elseif (preg_match('/Rs\.([\d,.]+)\s*.*?IGST/i', $text, $m)) {
            $data['taxable_value'] = (float) str_replace(',', '', $m[1]);
        }

        // ── IGST ──
        if (preg_match('/IGST.*?Rs\.\s*([\d,.]+)/i', $text, $m)) {
            $data['igst'] = (float) str_replace(',', '', $m[1]);
            $data['tax_amount'] = $data['igst'];
        }

        // ── SGST + CGST (intra-state, rare for ORANGI) ──
        if (preg_match('/SGST.*?Rs\.\s*([\d,.]+)/i', $text, $m)) {
            $data['sgst'] = (float) str_replace(',', '', $m[1]);
        }
        if (preg_match('/CGST.*?Rs\.\s*([\d,.]+)/i', $text, $m)) {
            $data['cgst'] = (float) str_replace(',', '', $m[1]);
        }
        if ($data['sgst'] && $data['cgst']) {
            $data['tax_amount'] = $data['sgst'] + $data['cgst'];
        }

        // ── Other Charges ──
        if (preg_match('/Other\s*Charges.*?Rs\.\s*([\d,.]+)/i', $text, $m)) {
            $otherCharges = (float) str_replace(',', '', $m[1]);
            $data['other_charges'] = $otherCharges > 0 ? $otherCharges : null;
        }

        // ── Order Date ──
        if (preg_match('/Order\s*Date\s*\n?\s*(\d{2}\.\d{2}\.\d{4})/i', $text, $m)) {
            $data['order_date'] = $this->parseDateDotFormat($m[1]);
        }

        // ── Invoice Date ──
        if (preg_match('/Invoice\s*Date\s*\n?\s*(\d{2}\.\d{2}\.\d{4})/i', $text, $m)) {
            $data['invoice_date'] = $this->parseDateDotFormat($m[1]);
        }

        // Only return if we extracted meaningful data
        if ($data['marketplace_order_id'] || $data['sub_order_number'] || $data['sku']) {
            return $data;
        }

        return null;
    }

    /**
     * Convert DD.MM.YYYY to YYYY-MM-DD.
     */
    private function parseDateDotFormat(string $date): string
    {
        $parts = explode('.', $date);
        if (count($parts) === 3) {
            return "{$parts[2]}-{$parts[1]}-{$parts[0]}";
        }

        return $date;
    }
}
