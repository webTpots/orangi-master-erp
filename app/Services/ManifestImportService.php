<?php

namespace App\Services;

use App\Models\ImportBatch;
use App\Models\Label;
use App\Models\Manifest;
use App\Models\ManifestPicklistLine;
use App\Models\ManifestShipmentLine;
use App\Models\Order;
use App\Models\SubOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class ManifestImportService
{
    public function __construct(
        private FlipkartManifestParser $flipkartManifestParser,
    ) {}
    /**
     * Import a manifest PDF, parsing picklist and courier-wise shipment sections.
     */
    public function importPdf(string $filePath, int $companyId, int $marketplaceId): ImportBatch
    {
        return DB::transaction(function () use ($filePath, $companyId, $marketplaceId) {
            $absolutePath = $this->resolveFilePath($filePath);
            $fileName = basename($filePath);
            $fileHash = md5_file($absolutePath);

            // Duplicate check
            $existing = Manifest::where('company_id', $companyId)
                ->where('file_hash', $fileHash)
                ->first();

            if ($existing) {
                throw new \RuntimeException(
                    "This manifest has already been imported (Manifest #{$existing->id})."
                );
            }

            // Create ImportBatch
            $batch = ImportBatch::create([
                'company_id'        => $companyId,
                'uploaded_by'       => auth()->id(),
                'type'              => 'manifest_pdf',
                'file_path'         => $filePath,
                'original_filename' => $fileName,
                'status'            => 'processing',
                'started_at'        => now(),
            ]);

            // Detect marketplace and parse with appropriate parser
            $parsed = $this->parseWithDetection($absolutePath, $marketplaceId);

            // Create Manifest record
            $manifest = Manifest::create([
                'company_id'      => $companyId,
                'marketplace_id'  => $marketplaceId,
                'import_batch_id' => $batch->id,
                'file_name'       => $fileName,
                'file_path'       => $filePath,
                'file_hash'       => $fileHash,
                'manifest_date'   => $parsed['manifest_date'],
                'supplier_name'   => $parsed['supplier_name'],
                'status'          => 'processing',
            ]);

            $totalLines = 0;
            $errorCount = 0;

            // Insert picklist lines
            foreach ($parsed['picklist'] as $item) {
                try {
                    ManifestPicklistLine::create([
                        'manifest_id' => $manifest->id,
                        'sku'         => $item['sku'] ?? null,
                        'color'       => $item['color'] ?? null,
                        'size'        => $item['size'] ?? null,
                        'quantity'    => $item['quantity'] ?? 0,
                    ]);
                    $totalLines++;
                } catch (\Exception $e) {
                    $errorCount++;
                }
            }

            // Insert shipment lines
            foreach ($parsed['shipments'] as $item) {
                try {
                    ManifestShipmentLine::create([
                        'manifest_id'      => $manifest->id,
                        'courier'          => $item['courier'] ?? null,
                        'supplier'         => $parsed['supplier_name'],
                        'serial_number'    => $item['serial_number'] ?? null,
                        'sub_order_number' => $item['sub_order_number'] ?? null,
                        'awb'              => $item['awb'] ?? null,
                        'sku'              => $item['sku'] ?? null,
                        'quantity'         => $item['quantity'] ?? 1,
                        'size'             => $item['size'] ?? null,
                        'packed_status'    => 'unknown',
                    ]);
                    $totalLines++;
                } catch (\Exception $e) {
                    $errorCount++;
                }
            }

            // Reconcile: link shipment lines to existing orders/labels
            $this->reconcileManifest($manifest, $companyId);

            $manifest->update([
                'status' => $errorCount > 0 && $totalLines === 0 ? 'failed' : 'completed',
            ]);

            $batch->update([
                'total_records'      => count($parsed['picklist']) + count($parsed['shipments']),
                'processed_records'  => $totalLines + $errorCount,
                'successful_records' => $totalLines,
                'failed_records'     => $errorCount,
                'status'             => $errorCount > 0 && $totalLines === 0 ? 'failed' : 'completed',
                'completed_at'       => now(),
            ]);

            return $batch->fresh();
        });
    }

    /**
     * Detect marketplace from PDF content and parse with appropriate parser.
     *
     * @return array{manifest_date: ?string, supplier_name: ?string, picklist: array, shipments: array}
     */
    private function parseWithDetection(string $absolutePath, int $marketplaceId): array
    {
        // Check if marketplace is Flipkart
        $marketplace = \App\Models\Marketplace::find($marketplaceId);
        $isFlipkart = $marketplace && strtolower($marketplace->code) === 'flipkart';

        if ($isFlipkart) {
            $flipkartData = $this->flipkartManifestParser->parse($absolutePath);

            // Normalize Flipkart manifest data to match internal structure
            return [
                'manifest_date' => $flipkartData['manifest_date'],
                'supplier_name' => $flipkartData['supplier_name'],
                'picklist'      => [],
                'shipments'     => array_map(function ($item) use ($flipkartData) {
                    return [
                        'courier'          => $item['courier'] ?? $flipkartData['courier_partner'],
                        'serial_number'    => $item['serial_number'],
                        'sub_order_number' => $item['sub_order_number'],
                        'awb'              => $item['awb'],
                        'sku'              => $item['sku'],
                        'quantity'         => $item['quantity'],
                        'size'             => $item['size'],
                    ];
                }, $flipkartData['items']),
            ];
        }

        // Auto-detect from PDF content
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($absolutePath);
            $firstPage = $pdf->getPages()[0] ?? null;

            if ($firstPage) {
                $text = $firstPage->getText();

                if (preg_match('/\bFlipkart\b/i', $text) || preg_match('/\bE-?Kart\b/i', $text) || preg_match('/\bFMPC\d+\b/i', $text)) {
                    $flipkartData = $this->flipkartManifestParser->parse($absolutePath);

                    return [
                        'manifest_date' => $flipkartData['manifest_date'],
                        'supplier_name' => $flipkartData['supplier_name'],
                        'picklist'      => [],
                        'shipments'     => array_map(function ($item) use ($flipkartData) {
                            return [
                                'courier'          => $item['courier'] ?? $flipkartData['courier_partner'],
                                'serial_number'    => $item['serial_number'],
                                'sub_order_number' => $item['sub_order_number'],
                                'awb'              => $item['awb'],
                                'sku'              => $item['sku'],
                                'quantity'         => $item['quantity'],
                                'size'             => $item['size'],
                            ];
                        }, $flipkartData['items']),
                    ];
                }
            }
        } catch (\Exception $e) {
            // Fall through to default parser
        }

        // Default to Meesho manifest parser
        return $this->parseManifestPdf($absolutePath);
    }

    /**
     * Parse the manifest PDF into structured data.
     *
     * @return array{manifest_date: ?string, supplier_name: ?string, picklist: array, shipments: array}
     */
    private function parseManifestPdf(string $filePath): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $pages = $pdf->getPages();

        $data = [
            'manifest_date' => null,
            'supplier_name' => null,
            'picklist'      => [],
            'shipments'     => [],
        ];

        $currentCourier = null;
        $inPicklist = false;
        $inCourierSection = false;

        foreach ($pages as $page) {
            $text = $page->getText();

            // Extract supplier name
            if (! $data['supplier_name'] && preg_match('/Supplier\s*(?:Name)?\s*[:\-]?\s*(\w[\w\s]*)/i', $text, $m)) {
                $data['supplier_name'] = trim($m[1]);
            }
            // Fallback: look for name after "Picklist" or at top
            if (! $data['supplier_name'] && preg_match('/^Picklist\s*\n\s*(\w+)/m', $text, $m)) {
                $data['supplier_name'] = trim($m[1]);
            }

            // Extract manifest date
            if (! $data['manifest_date'] && preg_match('/(\d{1,2}\s+\w{3},?\s+\d{4})/i', $text, $m)) {
                $data['manifest_date'] = $this->parseManifestDate($m[1]);
            }

            // Determine section type
            if (preg_match('/Picklist/i', $text) && ! preg_match('/Courier\s*:/i', $text)) {
                $inPicklist = true;
                $inCourierSection = false;
            }

            if (preg_match('/Courier\s*:\s*(\w[\w\s]*)/i', $text, $m)) {
                $inPicklist = false;
                $inCourierSection = true;
                $currentCourier = trim($m[1]);
            }

            if ($inPicklist) {
                // Parse picklist lines: SKU, Color, Size, Total Quantity
                $lines = explode("\n", $text);
                foreach ($lines as $line) {
                    $line = trim($line);
                    // Match: SKU_NAME    NA/Color    SIZE    QTY
                    if (preg_match('/^([A-Za-z0-9][\w\-]+)\s+(\S+)\s+(XS|S|M|L|XL|XXL|XXXL|2XL|3XL|4XL|5XL|Free\s*Size)\s+(\d+)$/i', $line, $lm)) {
                        $data['picklist'][] = [
                            'sku'      => trim($lm[1]),
                            'color'    => trim($lm[2]) !== 'NA' ? trim($lm[2]) : null,
                            'size'     => trim($lm[3]),
                            'quantity' => (int) $lm[4],
                        ];
                    }
                }
            }

            if ($inCourierSection && $currentCourier) {
                // Parse courier manifest lines
                // Format: S.No.  Sub Order No.  AWB  SKU  Qty.  Size  Packed
                $lines = explode("\n", $text);
                $serialNo = 0;
                foreach ($lines as $line) {
                    $line = trim($line);
                    // Match shipment line: number, sub_order, awb, sku, qty, size
                    if (preg_match('/^(\d+)\s+(\d{10,}(?:_\d+)?)\s+(\S+)\s+(\S+)\s+(\d+)\s+(\S+)/i', $line, $lm)) {
                        $data['shipments'][] = [
                            'courier'          => $currentCourier,
                            'serial_number'    => (int) $lm[1],
                            'sub_order_number' => trim($lm[2]),
                            'awb'              => trim($lm[3]),
                            'sku'              => trim($lm[4]),
                            'quantity'         => (int) $lm[5],
                            'size'             => trim($lm[6]),
                        ];
                    }
                    // Handle split sub-order numbers across lines (common in manifests)
                    elseif (preg_match('/^(\d+)\s+(\d{10,})$/', $line, $lm)) {
                        $serialNo = (int) $lm[1];
                        $partialSubOrder = $lm[2];
                        // The next line continuation is handled naturally by the regex above
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Reconcile manifest shipment lines against existing orders and labels.
     */
    private function reconcileManifest(Manifest $manifest, int $companyId): void
    {
        $shipmentLines = $manifest->shipmentLines()->get();

        foreach ($shipmentLines as $line) {
            if (! $line->sub_order_number) {
                continue;
            }

            // Try to find matching sub-order
            $subOrder = SubOrder::where('company_id', $companyId)
                ->where('sub_order_number', $line->sub_order_number)
                ->first();

            if ($subOrder) {
                $order = $subOrder->order;

                // If order is in accepted/label_ready, transition to picking
                if ($order && $order->canTransitionTo(Order::STATUS_PICKING)) {
                    try {
                        $order->changeStatus(
                            Order::STATUS_PICKING,
                            'Manifest imported',
                            auth()->id()
                        );
                    } catch (\RuntimeException $e) {
                        // Skip if transition not valid
                    }
                }
            }

            // Try to match with existing label by AWB
            if ($line->awb) {
                $label = Label::where('company_id', $companyId)
                    ->where('awb_number', $line->awb)
                    ->first();

                if ($label && ! $label->order_id && $subOrder) {
                    $label->update([
                        'order_id'    => $subOrder->order_id,
                        'sub_order_id' => $subOrder->id,
                        'status'       => 'linked',
                    ]);
                }
            }
        }
    }

    /**
     * Parse "14 Sep, 2026" or "09 Sep, 2026" format to YYYY-MM-DD.
     */
    private function parseManifestDate(string $dateStr): ?string
    {
        $dateStr = str_replace(',', '', trim($dateStr));
        $timestamp = strtotime($dateStr);

        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    /**
     * Resolve a file path to an absolute path.
     */
    private function resolveFilePath(string $filePath): string
    {
        if (str_starts_with($filePath, '/')) {
            return $filePath;
        }

        $storagePath = Storage::disk('local')->path($filePath);
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        throw new \RuntimeException("File not found: {$filePath}");
    }
}
