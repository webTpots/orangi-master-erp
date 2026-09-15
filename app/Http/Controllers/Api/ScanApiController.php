<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\ScanLog;
use App\Models\Shipment;
use App\Models\Sku;
use App\Services\ShipmentService;
use Illuminate\Http\Request;

class ScanApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ShipmentService $shipmentService,
    ) {}

    /**
     * Process barcode/QR scan, return identified item and action result.
     */
    public function processScan(Request $request)
    {
        $request->validate([
            'barcode_data' => 'required|string',
            'scan_type'    => 'required|string|in:' . implode(',', ScanLog::SCAN_TYPES),
            'scan_method'  => 'nullable|string|in:' . implode(',', ScanLog::SCAN_METHODS),
            'location'     => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:500',
        ]);

        $companyId = $request->user()->company_id;
        $barcodeData = $request->barcode_data;
        $identified = null;
        $scannableType = null;
        $scannableId = null;

        // Try to identify what was scanned
        // 1. Check if it's an AWB / tracking number (shipment)
        $shipment = Shipment::forCompany($companyId)
            ->where(function ($q) use ($barcodeData) {
                $q->where('tracking_number', $barcodeData)
                  ->orWhere('awb_number', $barcodeData);
            })
            ->first();

        if ($shipment) {
            $scannableType = Shipment::class;
            $scannableId = $shipment->id;
            $identified = [
                'type'     => 'shipment',
                'id'       => $shipment->id,
                'tracking' => $shipment->tracking_number,
                'status'   => $shipment->status,
                'order_id' => $shipment->order_id,
            ];
        }

        // 2. Check if it's an order ID / marketplace order ID
        if (! $identified) {
            $order = Order::forCompany($companyId)
                ->where(function ($q) use ($barcodeData) {
                    $q->where('marketplace_order_id', $barcodeData)
                      ->orWhere('awb_number', $barcodeData);
                })
                ->first();

            if ($order) {
                $scannableType = Order::class;
                $scannableId = $order->id;
                $identified = [
                    'type'     => 'order',
                    'id'       => $order->id,
                    'order_id' => $order->marketplace_order_id,
                    'status'   => $order->status,
                    'customer' => $order->customer_name,
                ];
            }
        }

        // 3. Check if it's a SKU code (inventory)
        if (! $identified) {
            $sku = Sku::where('company_id', $companyId)
                ->where('sku_code', $barcodeData)
                ->first();

            if ($sku) {
                $inventory = InventoryItem::where('sku_id', $sku->id)
                    ->where('company_id', $companyId)
                    ->first();

                $scannableType = Sku::class;
                $scannableId = $sku->id;
                $identified = [
                    'type'            => 'sku',
                    'id'              => $sku->id,
                    'sku_code'        => $sku->sku_code,
                    'name'            => $sku->sku_code,
                    'available_stock' => $inventory?->available_stock ?? 0,
                ];
            }
        }

        // Record the scan
        $scanLog = $this->shipmentService->recordScan(
            $request->scan_type,
            $barcodeData,
            $scannableType,
            $scannableId,
            $request->scan_method ?? 'barcode',
            $request->location,
            $request->notes,
        );

        return $this->success([
            'scan_id'    => $scanLog->id,
            'identified' => $identified,
            'matched'    => $identified !== null,
        ], $identified ? 'Item identified.' : 'Scan recorded, but item not recognized.');
    }

    /**
     * User's recent scan history.
     */
    public function recentScans(Request $request)
    {
        $companyId = $request->user()->company_id;
        $limit = min((int) ($request->limit ?? 20), 50);

        $scans = ScanLog::forCompany($companyId)
            ->where('user_id', $request->user()->id)
            ->orderByDesc('scanned_at')
            ->limit($limit)
            ->get()
            ->map(fn ($scan) => [
                'id'           => $scan->id,
                'scan_type'    => $scan->scan_type,
                'scan_label'   => $scan->scan_type_label,
                'barcode_data' => $scan->barcode_data,
                'scan_method'  => $scan->scan_method,
                'location'     => $scan->location,
                'notes'        => $scan->notes,
                'scanned_at'   => $scan->scanned_at?->toIso8601String(),
            ]);

        return $this->success($scans, 'Recent scans retrieved.');
    }
}
