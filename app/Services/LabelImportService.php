<?php

namespace App\Services;

use App\Models\ImportBatch;
use App\Models\Label;
use App\Models\LabelFile;
use App\Models\Order;
use App\Models\SubOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LabelImportService
{
    public function __construct(
        private MeeshoPdfParser $meeshoPdfParser,
        private FlipkartPdfParser $flipkartPdfParser,
        private OrderService $orderService,
    ) {}

    /**
     * Import a label PDF file, parse labels, create/link orders.
     *
     * @param  string $filePath       Absolute or storage-relative path to the PDF
     * @param  int    $companyId
     * @param  int    $marketplaceId
     * @return ImportBatch
     */
    public function importPdf(string $filePath, int $companyId, int $marketplaceId): ImportBatch
    {
        return DB::transaction(function () use ($filePath, $companyId, $marketplaceId) {
            $absolutePath = $this->resolveFilePath($filePath);
            $fileName = basename($filePath);
            $fileHash = md5_file($absolutePath);

            // Check for duplicate file import
            $existingFile = LabelFile::where('company_id', $companyId)
                ->where('file_hash', $fileHash)
                ->first();

            if ($existingFile) {
                throw new \RuntimeException(
                    "This file has already been imported (Label File #{$existingFile->id})."
                );
            }

            // Create ImportBatch
            $batch = ImportBatch::create([
                'company_id'        => $companyId,
                'uploaded_by'       => auth()->id(),
                'type'              => 'label_pdf',
                'file_path'         => $filePath,
                'original_filename' => $fileName,
                'status'            => 'processing',
                'started_at'        => now(),
            ]);

            // Create LabelFile record
            $labelFile = LabelFile::create([
                'company_id'      => $companyId,
                'marketplace_id'  => $marketplaceId,
                'import_batch_id' => $batch->id,
                'file_name'       => $fileName,
                'file_path'       => $filePath,
                'file_hash'       => $fileHash,
                'status'          => 'processing',
            ]);

            // Detect marketplace and parse with appropriate parser
            $result = $this->parseWithDetection($absolutePath, $marketplaceId);

            $labelFile->update(['total_pages' => $result['total_pages']]);

            $parsedCount = 0;
            $linkedCount = 0;
            $errorCount = 0;

            foreach ($result['orders'] as $parsed) {
                try {
                    // Create label record
                    $label = Label::create([
                        'company_id'       => $companyId,
                        'marketplace_id'   => $marketplaceId,
                        'label_file_id'    => $labelFile->id,
                        'awb_number'       => $parsed['awb_number'] ?? null,
                        'courier_partner'  => $parsed['courier_partner'] ?? null,
                        'tracking_number'  => $parsed['tracking_number'] ?? null,
                        'customer_name'    => $parsed['customer_name'] ?? null,
                        'customer_city'    => $parsed['customer_city'] ?? null,
                        'customer_state'   => $parsed['customer_state'] ?? null,
                        'customer_pincode' => $parsed['customer_pincode'] ?? null,
                        'payment_type'     => $parsed['payment_type'] ?? null,
                        'sku'              => $parsed['sku'] ?? null,
                        'size'             => $parsed['size'] ?? null,
                        'quantity'         => $parsed['quantity'] ?? 1,
                        'color'            => $parsed['color'] ?? null,
                        'sub_order_number' => $parsed['sub_order_number'] ?? null,
                        'invoice_number'   => $parsed['invoice_number'] ?? null,
                        'invoice_date'     => $parsed['invoice_date'] ?? null,
                        'invoice_amount'   => $parsed['invoice_amount'] ?? null,
                        'taxable_value'    => $parsed['taxable_value'] ?? null,
                        'tax_amount'       => $parsed['tax_amount'] ?? null,
                        'raw_text'         => $parsed['raw_text'] ?? null,
                        'page_number'      => $parsed['page_number'] ?? null,
                        'status'           => 'parsed',
                    ]);

                    $parsedCount++;

                    // Attempt to create/link order
                    $order = $this->orderService->createOrderFromLabel($parsed, $companyId, $marketplaceId);

                    if ($order) {
                        $label->update(['order_id' => $order->id, 'status' => 'linked']);

                        // Link to sub-order if matching
                        $subOrderNumber = $parsed['sub_order_number'] ?? null;
                        if ($subOrderNumber) {
                            $subOrder = SubOrder::where('company_id', $companyId)
                                ->where('sub_order_number', $subOrderNumber)
                                ->first();
                            if ($subOrder) {
                                $label->update(['sub_order_id' => $subOrder->id]);
                            }
                        }

                        // If order had an AWB, transition to label_ready
                        if (($parsed['awb_number'] ?? null) && $order->status === Order::STATUS_NEW) {
                            try {
                                $order->update([
                                    'courier_partner' => $parsed['courier_partner'] ?? $order->courier_partner,
                                    'awb_number'      => $parsed['awb_number'],
                                ]);
                                // Only transition if valid
                                if ($order->canTransitionTo(Order::STATUS_ACCEPTED)) {
                                    $order->changeStatus(Order::STATUS_ACCEPTED, 'Label imported with AWB', auth()->id());
                                }
                                if ($order->canTransitionTo(Order::STATUS_LABEL_READY)) {
                                    $order->changeStatus(Order::STATUS_LABEL_READY, 'Label imported with AWB', auth()->id());
                                }
                            } catch (\RuntimeException $e) {
                                // Transition not valid, skip gracefully
                            }
                        }

                        $linkedCount++;
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    // Create label with error status
                    Label::create([
                        'company_id'       => $companyId,
                        'marketplace_id'   => $marketplaceId,
                        'label_file_id'    => $labelFile->id,
                        'raw_text'         => $parsed['raw_text'] ?? null,
                        'page_number'      => $parsed['page_number'] ?? null,
                        'status'           => 'error',
                    ]);
                }
            }

            // Handle parse errors as error labels
            $errorCount += count($result['errors']);

            // Update LabelFile stats
            $labelFile->update([
                'parsed_labels' => $parsedCount,
                'linked_labels' => $linkedCount,
                'error_labels'  => $errorCount,
                'status'        => $errorCount > 0 && $parsedCount === 0 ? 'failed' : 'completed',
            ]);

            // Update ImportBatch
            $batch->update([
                'total_records'      => $result['total_pages'],
                'processed_records'  => $parsedCount + $errorCount,
                'successful_records' => $linkedCount,
                'failed_records'     => $errorCount,
                'status'             => $errorCount > 0 && $parsedCount === 0 ? 'failed' : 'completed',
                'completed_at'       => now(),
                'error_summary'      => ! empty($result['errors']) ? implode('; ', $result['errors']) : null,
            ]);

            return $batch->fresh();
        });
    }

    /**
     * Detect marketplace from PDF content and parse with the appropriate parser.
     *
     * @return array{orders: array, errors: array, total_pages: int}
     */
    private function parseWithDetection(string $absolutePath, int $marketplaceId): array
    {
        // Check if the marketplace is Flipkart
        $marketplace = \App\Models\Marketplace::find($marketplaceId);
        $isFlipkart = $marketplace && strtolower($marketplace->code) === 'flipkart';

        if ($isFlipkart) {
            return $this->flipkartPdfParser->parse($absolutePath);
        }

        // Auto-detect from PDF content if marketplace is not explicitly set
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($absolutePath);
            $firstPage = $pdf->getPages()[0] ?? null;

            if ($firstPage) {
                $text = $firstPage->getText();

                // Flipkart indicators
                $flipkartIndicators = [
                    '/\bFlipkart\b/i',
                    '/\bOD\d{10,}\b/',
                    '/\bFSN[A-Z0-9]{8,}\b/',
                    '/\bE-?Kart\s*Logistics\b/i',
                    '/\bFMPC\d{10,}\b/i',
                ];

                $flipkartScore = 0;
                foreach ($flipkartIndicators as $pattern) {
                    if (preg_match($pattern, $text)) {
                        $flipkartScore++;
                    }
                }

                if ($flipkartScore >= 2) {
                    return $this->flipkartPdfParser->parse($absolutePath);
                }
            }
        } catch (\Exception $e) {
            // Fall through to default parser
        }

        // Default to Meesho parser
        return $this->meeshoPdfParser->parse($absolutePath);
    }

    /**
     * Resolve a file path to an absolute path.
     */
    private function resolveFilePath(string $filePath): string
    {
        // If already absolute
        if (str_starts_with($filePath, '/')) {
            return $filePath;
        }

        // Try storage path
        $storagePath = Storage::disk('local')->path($filePath);
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        throw new \RuntimeException("File not found: {$filePath}");
    }
}
