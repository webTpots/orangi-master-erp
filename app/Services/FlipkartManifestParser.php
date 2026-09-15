<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class FlipkartManifestParser
{
    /**
     * Parse a Flipkart manifest/dispatch PDF.
     *
     * Extracts manifest ID, date, courier partner, pickup slot,
     * dispatch-by date, and individual order-AWB mappings.
     *
     * @return array{manifest_id: ?string, manifest_date: ?string, courier_partner: ?string, pickup_slot: ?string, dispatch_by: ?string, supplier_name: ?string, items: array, errors: array}
     */
    public function parse(string $filePath): array
    {
        $data = [
            'manifest_id'     => null,
            'manifest_date'   => null,
            'courier_partner' => null,
            'pickup_slot'     => null,
            'dispatch_by'     => null,
            'supplier_name'   => null,
            'items'           => [],
            'errors'          => [],
        ];

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $pages = $pdf->getPages();

            $fullText = '';
            foreach ($pages as $page) {
                $text = $page->getText();
                $fullText .= $text . "\n";

                // Extract manifest-level data from first occurrence
                if (! $data['manifest_id'] && preg_match('/Manifest\s*(?:Id|ID|No\.?)\s*[:\-]?\s*(\S+)/i', $text, $m)) {
                    $data['manifest_id'] = trim($m[1]);
                }

                if (! $data['manifest_date'] && preg_match('/Manifest\s*Date\s*[:\-]?\s*(\d{1,2}[\s\-\.\/]\w{3,9}[\s\-\.\/,]?\s*\d{4})/i', $text, $m)) {
                    $data['manifest_date'] = $this->parseManifestDate($m[1]);
                }
                if (! $data['manifest_date'] && preg_match('/Date\s*[:\-]?\s*(\d{2}[\.\-\/]\d{2}[\.\-\/]\d{4})/i', $text, $m)) {
                    $data['manifest_date'] = $this->parseDateFlexible($m[1]);
                }

                // Courier Partner
                if (! $data['courier_partner']) {
                    $courierPatterns = [
                        'E-Kart'       => '/\bE-?Kart\s*(?:Logistics)?\b/i',
                        'Delhivery'    => '/\bDelhivery\b/i',
                        'Shadowfax'    => '/\bShadowfax\b/i',
                        'Xpress Bees'  => '/\bXpress\s*Bees\b/i',
                        'Ecom Express' => '/\bEcom\s*Express\b/i',
                        'DTDC'         => '/\bDTDC\b/i',
                        'BlueDart'     => '/\bBlueDart\b/i',
                    ];
                    foreach ($courierPatterns as $name => $pattern) {
                        if (preg_match($pattern, $text)) {
                            $data['courier_partner'] = $name;
                            break;
                        }
                    }
                }

                if (preg_match('/Courier\s*(?:Partner)?\s*[:\-]?\s*(\w[\w\s\-]*)/i', $text, $m)) {
                    $data['courier_partner'] = $data['courier_partner'] ?? trim($m[1]);
                }

                // Pickup Slot
                if (! $data['pickup_slot'] && preg_match('/Pickup\s*(?:Slot|Time|Window)\s*[:\-]?\s*(.+)/i', $text, $m)) {
                    $data['pickup_slot'] = trim($m[1]);
                }

                // Dispatch By
                if (! $data['dispatch_by'] && preg_match('/Dispatch\s*(?:By|Before)\s*[:\-]?\s*(\d{1,2}[\s\-\.\/]\w{3,9}[\s\-\.\/,]?\s*\d{4})/i', $text, $m)) {
                    $data['dispatch_by'] = $this->parseManifestDate($m[1]);
                }
                if (! $data['dispatch_by'] && preg_match('/Dispatch\s*(?:By|Before)\s*[:\-]?\s*(\d{2}[\.\-\/]\d{2}[\.\-\/]\d{4})/i', $text, $m)) {
                    $data['dispatch_by'] = $this->parseDateFlexible($m[1]);
                }

                // Supplier Name
                if (! $data['supplier_name'] && preg_match('/Seller\s*(?:Name)?\s*[:\-]?\s*(\w[\w\s]*)/i', $text, $m)) {
                    $data['supplier_name'] = trim($m[1]);
                }

                // Extract dispatch items from this page
                $items = $this->extractDispatchItems($text);
                foreach ($items as $item) {
                    $item['courier'] = $item['courier'] ?? $data['courier_partner'];
                    $data['items'][] = $item;
                }
            }
        } catch (\Exception $e) {
            $data['errors'][] = 'PDF parse error: ' . $e->getMessage();
        }

        return $data;
    }

    /**
     * Extract individual dispatch items (order + AWB pairs) from page text.
     *
     * Flipkart manifests typically have tabular data with:
     * S.No, Order ID, AWB, SKU, Qty, etc.
     *
     * @return array<int, array>
     */
    public function extractDispatchItems(string $text): array
    {
        $items = [];
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            $line = trim($line);

            // Format: S.No  OrderID  AWB  SKU  Qty  Size
            if (preg_match('/^(\d+)\s+(OD\d{10,}|\d{12,})\s+(\S+)\s+(\S+)\s+(\d+)\s*(\S+)?/i', $line, $m)) {
                $items[] = [
                    'serial_number'    => (int) $m[1],
                    'sub_order_number' => trim($m[2]),
                    'awb'              => trim($m[3]),
                    'sku'              => trim($m[4]),
                    'quantity'         => (int) $m[5],
                    'size'             => isset($m[6]) ? trim($m[6]) : null,
                    'courier'          => null,
                ];
            }
            // Format: S.No  OrderItemID  AWB  SKU  Qty
            elseif (preg_match('/^(\d+)\s+(\d{10,})\s+(FMPC\S+|\S{10,})\s+(\S+)\s+(\d+)/i', $line, $m)) {
                $items[] = [
                    'serial_number'    => (int) $m[1],
                    'sub_order_number' => trim($m[2]),
                    'awb'              => trim($m[3]),
                    'sku'              => trim($m[4]),
                    'quantity'         => (int) $m[5],
                    'size'             => null,
                    'courier'          => null,
                ];
            }
            // Simpler: OrderID + AWB pair
            elseif (preg_match('/\b(OD\d{10,}|\d{12,})\s+(FMPC\S+|\S{12,})\b/', $line, $m)) {
                $items[] = [
                    'serial_number'    => null,
                    'sub_order_number' => trim($m[1]),
                    'awb'              => trim($m[2]),
                    'sku'              => null,
                    'quantity'         => 1,
                    'size'             => null,
                    'courier'          => null,
                ];
            }
        }

        return $items;
    }

    /**
     * Parse "14 Sep, 2026" or "09 Sep 2026" format to YYYY-MM-DD.
     */
    private function parseManifestDate(string $dateStr): ?string
    {
        $dateStr = str_replace(',', '', trim($dateStr));
        $timestamp = strtotime($dateStr);

        return $timestamp ? date('Y-m-d', $timestamp) : null;
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
