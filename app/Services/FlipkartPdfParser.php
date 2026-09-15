<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class FlipkartPdfParser
{
    /**
     * Parse Flipkart order label PDF -- each page = one label.
     *
     * Extracts: order ID, SKU, product name, size, color, quantity,
     * MRP, selling price, customer details, AWB, courier, invoice,
     * payment type, and tracking number.
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
                    $extracted = $this->extractLabelData($text, $pageNum + 1);
                    if ($extracted) {
                        $orders[] = $this->normalizeFlipkartData($extracted);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Page " . ($pageNum + 1) . ": " . $e->getMessage();
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'PDF parse error: ' . $e->getMessage();
        }

        // Deduplicate by order_item_id or marketplace_order_id
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
     * Extract all structured data from a single Flipkart label page.
     */
    public function extractLabelData(string $text, int $pageNum = 1): ?array
    {
        $data = [
            'marketplace_order_id' => null,
            'order_item_id'        => null,
            'sub_order_number'     => null,
            'sku'                  => null,
            'seller_sku'           => null,
            'product_name'         => null,
            'size'                 => null,
            'quantity'             => 1,
            'color'                => null,
            'mrp'                  => null,
            'selling_price'        => null,
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
            'order_date'           => null,
            'page_number'          => $pageNum,
            'raw_text'             => $text,
        ];

        // ── Order ID (Flipkart formats: OD + digits, or long numeric) ──
        if (preg_match('/Order\s*(?:Id|ID|No\.?)\s*[:\-]?\s*(OD\d{10,})/i', $text, $m)) {
            $data['marketplace_order_id'] = trim($m[1]);
        } elseif (preg_match('/\b(OD\d{10,})\b/', $text, $m)) {
            $data['marketplace_order_id'] = trim($m[1]);
        } elseif (preg_match('/Order\s*(?:Id|ID|No\.?)\s*[:\-]?\s*(\d{12,})/i', $text, $m)) {
            $data['marketplace_order_id'] = trim($m[1]);
        }

        // ── Order Item ID ──
        if (preg_match('/Order\s*Item\s*(?:Id|ID)\s*[:\-]?\s*(\d{10,})/i', $text, $m)) {
            $data['order_item_id'] = trim($m[1]);
            $data['sub_order_number'] = trim($m[1]);
        }

        // Derive sub_order_number from order_item_id or order_id
        if (! $data['sub_order_number'] && $data['marketplace_order_id']) {
            $data['sub_order_number'] = $data['marketplace_order_id'];
        }

        // ── Seller SKU / SKU ──
        if (preg_match('/Seller\s*SKU\s*[:\-]?\s*(\S+)/i', $text, $m)) {
            $data['seller_sku'] = trim($m[1]);
            $data['sku'] = $data['seller_sku'];
        } elseif (preg_match('/SKU\s*[:\-]?\s*(\S+)/i', $text, $m)) {
            $data['sku'] = trim($m[1]);
        }

        // ── FSN (Flipkart Serial Number) ──
        if (preg_match('/\b(FSN[A-Z0-9]{8,})\b/', $text, $m)) {
            if (! $data['sku']) {
                $data['sku'] = trim($m[1]);
            }
        }

        // ── Product Name / Description ──
        if (preg_match('/Product\s*(?:Name|Title|Description)\s*[:\-]?\s*(.+?)(?:\n|Size|Color|Qty|Quantity)/is', $text, $m)) {
            $data['product_name'] = trim(preg_replace('/\s+/', ' ', $m[1]));
        } elseif (preg_match('/Description\s*[:\-]?\s*(.+?)(?:\n|HSN)/is', $text, $m)) {
            $data['product_name'] = trim(preg_replace('/\s+/', ' ', $m[1]));
        }

        // ── Size ──
        if (preg_match('/Size\s*[:\-]?\s*(XS|S|M|L|XL|XXL|XXXL|2XL|3XL|4XL|5XL|Free\s*Size|\d+)/i', $text, $m)) {
            $data['size'] = trim($m[1]);
        }

        // ── Color ──
        if (preg_match('/Color\s*[:\-]?\s*(\w[\w\s]*?)(?:\n|Size|Qty|Quantity|SKU)/i', $text, $m)) {
            $color = trim($m[1]);
            $data['color'] = $color !== 'NA' ? $color : null;
        }

        // ── Quantity ──
        if (preg_match('/Qty\.?\s*[:\-]?\s*(\d+)/i', $text, $m)) {
            $data['quantity'] = (int) $m[1];
        } elseif (preg_match('/Quantity\s*[:\-]?\s*(\d+)/i', $text, $m)) {
            $data['quantity'] = (int) $m[1];
        }

        // ── MRP ──
        if (preg_match('/MRP\s*[:\-]?\s*(?:Rs\.?\s*|INR\s*)?(\d[\d,.]+)/i', $text, $m)) {
            $data['mrp'] = (float) str_replace(',', '', $m[1]);
        }

        // ── Selling Price ──
        if (preg_match('/Selling\s*Price\s*[:\-]?\s*(?:Rs\.?\s*|INR\s*)?(\d[\d,.]+)/i', $text, $m)) {
            $data['selling_price'] = (float) str_replace(',', '', $m[1]);
        } elseif (preg_match('/Total\s*(?:Amount|Price)\s*[:\-]?\s*(?:Rs\.?\s*|INR\s*)?(\d[\d,.]+)/i', $text, $m)) {
            $data['selling_price'] = (float) str_replace(',', '', $m[1]);
        }

        // ── Customer Name ──
        if (preg_match('/(?:Ship\s*To|Deliver\s*To|Customer\s*(?:Name)?)\s*[:\-]?\s*\n?\s*(.+)/i', $text, $m)) {
            $name = trim($m[1]);
            // Clean up name — take first line only
            $name = explode("\n", $name)[0];
            $data['customer_name'] = trim($name);
        }

        // ── Customer Address with City, State, Pincode ──
        if (preg_match('/(?:Ship\s*To|Deliver\s*To|Customer)\s*[:\-]?\s*\n?\s*.+\n([\s\S]*?)(\d{6})/i', $text, $m)) {
            $addressBlock = trim($m[1]);
            $data['customer_pincode'] = trim($m[2]);

            // Try to extract city and state from address
            if (preg_match('/(\w[\w\s]+?)\s*,\s*([A-Za-z\s]+?)\s*,?\s*\d{6}/i', $addressBlock . $m[2], $am)) {
                $data['customer_city'] = trim($am[1]);
                $data['customer_state'] = trim($am[2]);
            }

            $data['customer_address'] = trim(preg_replace('/\s+/', ' ', $addressBlock));
        } elseif (preg_match('/(\w[\w\s]+?)\s*,\s*([A-Za-z\s]+?)\s*,?\s*(\d{6})/', $text, $m)) {
            $data['customer_city'] = trim($m[1]);
            $data['customer_state'] = trim($m[2]);
            $data['customer_pincode'] = trim($m[3]);
        }

        // ── Payment Type ──
        if (preg_match('/Payment\s*(?:Mode|Type|Method)\s*[:\-]?\s*(COD|Prepaid|Online|NEFT)/i', $text, $m)) {
            $data['payment_type'] = strtolower(trim($m[1])) === 'cod' ? 'cod' : 'prepaid';
        } elseif (stripos($text, 'Cash on Delivery') !== false || stripos($text, 'COD') !== false) {
            $data['payment_type'] = 'cod';
        } elseif (stripos($text, 'Prepaid') !== false || stripos($text, 'Already Paid') !== false) {
            $data['payment_type'] = 'prepaid';
        }

        // ── Courier Partner ──
        $courierPatterns = [
            'E-Kart'        => '/\bE-?Kart\s*Logistics\b/i',
            'Delhivery'     => '/\bDelhivery\b/i',
            'Shadowfax'     => '/\bShadowfax\b/i',
            'Xpress Bees'   => '/\bXpress\s*Bees\b/i',
            'Ecom Express'  => '/\bEcom\s*Express\b/i',
            'DTDC'          => '/\bDTDC\b/i',
            'BlueDart'      => '/\bBlueDart\b/i',
            'India Post'    => '/\bIndia\s*Post\b/i',
            'Valmo'         => '/\bValmo\w*/i',
        ];

        foreach ($courierPatterns as $name => $pattern) {
            if (preg_match($pattern, $text)) {
                $data['courier_partner'] = $name;
                break;
            }
        }

        // ── AWB / Tracking Number ──
        if (preg_match('/AWB\s*(?:No\.?|Number)?\s*[:\-]?\s*(\S+)/i', $text, $m)) {
            $data['awb_number'] = trim($m[1]);
            $data['tracking_number'] = $data['awb_number'];
        } elseif (preg_match('/Tracking\s*(?:No\.?|Number|ID)?\s*[:\-]?\s*(\S+)/i', $text, $m)) {
            $data['awb_number'] = trim($m[1]);
            $data['tracking_number'] = $data['awb_number'];
        }
        // Flipkart E-Kart AWB format: FMPC + digits
        if (! $data['awb_number'] && preg_match('/FMPC\d{10,}/i', $text, $m)) {
            $data['awb_number'] = trim($m[0]);
            $data['tracking_number'] = $data['awb_number'];
            if (! $data['courier_partner']) {
                $data['courier_partner'] = 'E-Kart';
            }
        }

        // ── Invoice Number ──
        if (preg_match('/Invoice\s*(?:No\.?|Number)\s*[:\-]?\s*([A-Za-z0-9\-\/]+)/i', $text, $m)) {
            $data['invoice_number'] = trim($m[1]);
        }

        // ── Invoice Date ──
        if (preg_match('/Invoice\s*Date\s*[:\-]?\s*(\d{2}[\.\-\/]\d{2}[\.\-\/]\d{4})/i', $text, $m)) {
            $data['invoice_date'] = $this->parseDateFlexible($m[1]);
        }

        // ── Order Date ──
        if (preg_match('/Order\s*Date\s*[:\-]?\s*(\d{2}[\.\-\/]\d{2}[\.\-\/]\d{4})/i', $text, $m)) {
            $data['order_date'] = $this->parseDateFlexible($m[1]);
        } elseif (preg_match('/Order\s*Date\s*[:\-]?\s*(\d{1,2}\s+\w{3},?\s+\d{4})/i', $text, $m)) {
            $ts = strtotime(str_replace(',', '', trim($m[1])));
            $data['order_date'] = $ts ? date('Y-m-d', $ts) : null;
        }

        // ── HSN Code ──
        if (preg_match('/HSN\s*(?:Code)?\s*[:\-]?\s*(\d{4,8})/i', $text, $m)) {
            $data['hsn_code'] = trim($m[1]);
        } elseif (preg_match('/\b(6\d{3,5})\b/', $text, $m)) {
            $data['hsn_code'] = trim($m[1]);
        }

        // ── Taxable Value ──
        if (preg_match('/Taxable\s*Value\s*[:\-]?\s*(?:Rs\.?\s*)?(\d[\d,.]+)/i', $text, $m)) {
            $data['taxable_value'] = (float) str_replace(',', '', $m[1]);
        }

        // ── IGST ──
        if (preg_match('/IGST.*?(?:Rs\.?\s*)?(\d[\d,.]+)/i', $text, $m)) {
            $data['igst'] = (float) str_replace(',', '', $m[1]);
            $data['tax_amount'] = $data['igst'];
        }

        // ── SGST + CGST ──
        if (preg_match('/SGST.*?(?:Rs\.?\s*)?(\d[\d,.]+)/i', $text, $m)) {
            $data['sgst'] = (float) str_replace(',', '', $m[1]);
        }
        if (preg_match('/CGST.*?(?:Rs\.?\s*)?(\d[\d,.]+)/i', $text, $m)) {
            $data['cgst'] = (float) str_replace(',', '', $m[1]);
        }
        if ($data['sgst'] && $data['cgst']) {
            $data['tax_amount'] = $data['sgst'] + $data['cgst'];
        }

        // ── Invoice Amount (Grand Total) ──
        if (preg_match('/Grand\s*Total\s*[:\-]?\s*(?:Rs\.?\s*|INR\s*)?(\d[\d,.]+)/i', $text, $m)) {
            $data['invoice_amount'] = (float) str_replace(',', '', $m[1]);
        } elseif (preg_match('/Total\s*(?:Invoice)?\s*(?:Amount)?\s*[:\-]?\s*(?:Rs\.?\s*)?(\d[\d,.]+)/i', $text, $m)) {
            $data['invoice_amount'] = (float) str_replace(',', '', $m[1]);
        }

        // Only return if we extracted meaningful data
        if ($data['marketplace_order_id'] || $data['sub_order_number'] || $data['sku'] || $data['awb_number']) {
            return $data;
        }

        return null;
    }

    /**
     * Normalize Flipkart field names to match internal order schema.
     */
    public function normalizeFlipkartData(array $raw): array
    {
        return [
            'marketplace_order_id' => $raw['marketplace_order_id'],
            'sub_order_number'     => $raw['sub_order_number'] ?? $raw['order_item_id'] ?? $raw['marketplace_order_id'],
            'sku'                  => $raw['seller_sku'] ?? $raw['sku'],
            'product_name'         => $raw['product_name'],
            'size'                 => $raw['size'],
            'quantity'             => $raw['quantity'] ?? 1,
            'color'                => $raw['color'],
            'customer_name'        => $raw['customer_name'],
            'customer_address'     => $raw['customer_address'],
            'customer_city'        => $raw['customer_city'],
            'customer_state'       => $raw['customer_state'],
            'customer_pincode'     => $raw['customer_pincode'],
            'payment_type'         => $raw['payment_type'],
            'courier_partner'      => $raw['courier_partner'],
            'awb_number'           => $raw['awb_number'],
            'tracking_number'      => $raw['tracking_number'] ?? $raw['awb_number'],
            'invoice_number'       => $raw['invoice_number'],
            'invoice_date'         => $raw['invoice_date'],
            'invoice_amount'       => $raw['invoice_amount'],
            'hsn_code'             => $raw['hsn_code'],
            'taxable_value'        => $raw['taxable_value'],
            'igst'                 => $raw['igst'],
            'sgst'                 => $raw['sgst'],
            'cgst'                 => $raw['cgst'],
            'tax_amount'           => $raw['tax_amount'],
            'other_charges'        => null,
            'order_date'           => $raw['order_date'],
            'page_number'          => $raw['page_number'],
            'raw_text'             => $raw['raw_text'],
        ];
    }

    /**
     * Convert DD.MM.YYYY, DD-MM-YYYY, or DD/MM/YYYY to YYYY-MM-DD.
     */
    private function parseDateFlexible(string $date): string
    {
        $parts = preg_split('/[\.\-\/]/', $date);
        if (count($parts) === 3) {
            return "{$parts[2]}-{$parts[1]}-{$parts[0]}";
        }

        return $date;
    }
}
