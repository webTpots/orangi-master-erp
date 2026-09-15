<?php

namespace App\Services;

use App\Models\Label;
use App\Models\Order;
use App\Models\ScanLog;
use App\Models\Shipment;
use App\Models\ShipmentScan;
use Illuminate\Support\Facades\DB;

class ShipmentService
{
    /**
     * Create a shipment from an order with provided data.
     */
    public function createShipment(Order $order, array $data): Shipment
    {
        return DB::transaction(function () use ($order, $data) {
            $shipment = Shipment::create([
                'company_id'           => $order->company_id,
                'order_id'             => $order->id,
                'sub_order_id'         => $data['sub_order_id'] ?? null,
                'tracking_number'      => $data['tracking_number'] ?? $order->awb_number ?? uniqid('SHP'),
                'awb_number'           => $data['awb_number'] ?? $order->awb_number,
                'courier_name'         => $data['courier_name'] ?? $order->courier_partner ?? 'Unknown',
                'courier_code'         => $data['courier_code'] ?? null,
                'status'               => Shipment::STATUS_CREATED,
                'weight_grams'         => $data['weight_grams'] ?? null,
                'dimensions_json'      => $data['dimensions_json'] ?? null,
                'pickup_address_json'  => $data['pickup_address_json'] ?? null,
                'delivery_address_json' => $data['delivery_address_json'] ?? [
                    'name'    => $order->customer_name,
                    'address' => $order->customer_address,
                    'city'    => $order->customer_city,
                    'state'   => $order->customer_state,
                    'pincode' => $order->customer_pincode,
                ],
                'estimated_delivery_at' => $data['estimated_delivery_at'] ?? null,
                'notes'                 => $data['notes'] ?? null,
            ]);

            // Create initial scan entry
            ShipmentScan::create([
                'shipment_id'        => $shipment->id,
                'scan_type'          => 'pickup',
                'scanned_at'         => now(),
                'status_code'        => 'CREATED',
                'status_description' => 'Shipment created',
            ]);

            return $shipment;
        });
    }

    /**
     * Update shipment status with an optional scan record.
     *
     * @throws \RuntimeException
     */
    public function updateShipmentStatus(Shipment $shipment, string $status, array $scanData = []): Shipment
    {
        return DB::transaction(function () use ($shipment, $status, $scanData) {
            $shipment = Shipment::lockForUpdate()->find($shipment->id);
            $shipment->changeStatus($status);

            // Record the scan
            ShipmentScan::create([
                'shipment_id'        => $shipment->id,
                'scan_type'          => $scanData['scan_type'] ?? $this->mapStatusToScanType($status),
                'scanned_at'         => $scanData['scanned_at'] ?? now(),
                'location'           => $scanData['location'] ?? null,
                'city'               => $scanData['city'] ?? null,
                'state'              => $scanData['state'] ?? null,
                'status_code'        => $scanData['status_code'] ?? strtoupper($status),
                'status_description' => $scanData['status_description'] ?? Shipment::STATUS_LABELS[$status] ?? $status,
                'raw_data'           => $scanData['raw_data'] ?? null,
            ]);

            // Update last scan info
            $shipment->update([
                'last_scan_at'       => $scanData['scanned_at'] ?? now(),
                'last_scan_location' => $scanData['location'] ?? $scanData['city'] ?? null,
                'last_scan_status'   => Shipment::STATUS_LABELS[$status] ?? $status,
            ]);

            return $shipment->fresh();
        });
    }

    /**
     * Record a warehouse barcode/QR scan.
     */
    public function recordScan(
        string $scanType,
        string $barcodeData,
        ?string $scannableType = null,
        ?int $scannableId = null,
        ?string $scanMethod = 'barcode',
        ?string $location = null,
        ?string $notes = null,
    ): ScanLog {
        return ScanLog::create([
            'company_id'     => auth()->user()->company_id,
            'user_id'        => auth()->id(),
            'scannable_type' => $scannableType,
            'scannable_id'   => $scannableId,
            'scan_type'      => $scanType,
            'barcode_data'   => $barcodeData,
            'scan_method'    => $scanMethod ?? 'barcode',
            'scanned_at'     => now(),
            'location'       => $location,
            'notes'          => $notes,
        ]);
    }

    /**
     * Get ordered scan timeline for a shipment.
     */
    public function getShipmentTimeline(Shipment $shipment): \Illuminate\Database\Eloquent\Collection
    {
        return $shipment->scans()->orderBy('scanned_at', 'asc')->get();
    }

    /**
     * Auto-create a shipment from a label record.
     */
    public function createShipmentFromLabel(Label $label): Shipment
    {
        $order = $label->order;

        if (! $order) {
            throw new \RuntimeException('Label is not linked to an order.');
        }

        return $this->createShipment($order, [
            'tracking_number' => $label->tracking_number ?? $label->awb_number ?? uniqid('SHP'),
            'awb_number'      => $label->awb_number,
            'courier_name'    => $label->courier_partner ?? 'Unknown',
            'sub_order_id'    => $label->sub_order_id,
        ]);
    }

    /**
     * Bulk dispatch: mark multiple shipments as picked up.
     *
     * @return array{succeeded: int, failed: int}
     */
    public function bulkDispatch(array $shipmentIds): array
    {
        $succeeded = 0;
        $failed = 0;

        foreach ($shipmentIds as $id) {
            $shipment = Shipment::where('id', $id)
                ->where('company_id', auth()->user()->company_id)
                ->first();

            if (! $shipment) {
                $failed++;
                continue;
            }

            try {
                $this->updateShipmentStatus($shipment, Shipment::STATUS_PICKED_UP, [
                    'scan_type'          => 'pickup',
                    'status_description' => 'Bulk dispatch — picked up',
                ]);

                // Also log the dispatch scan
                $this->recordScan(
                    ScanLog::TYPE_DISPATCH,
                    $shipment->tracking_number,
                    Shipment::class,
                    $shipment->id,
                );

                $succeeded++;
            } catch (\RuntimeException $e) {
                $failed++;
            }
        }

        return compact('succeeded', 'failed');
    }

    /**
     * Map a shipment status to its corresponding scan type.
     */
    private function mapStatusToScanType(string $status): string
    {
        return match ($status) {
            Shipment::STATUS_PICKED_UP       => ShipmentScan::TYPE_PICKUP,
            Shipment::STATUS_IN_TRANSIT      => ShipmentScan::TYPE_IN_TRANSIT,
            Shipment::STATUS_OUT_FOR_DELIVERY => ShipmentScan::TYPE_OUT_FOR_DELIVERY,
            Shipment::STATUS_DELIVERED       => ShipmentScan::TYPE_DELIVERED,
            Shipment::STATUS_RTO_INITIATED   => ShipmentScan::TYPE_RTO_INITIATED,
            Shipment::STATUS_RTO_IN_TRANSIT  => ShipmentScan::TYPE_RTO_IN_TRANSIT,
            Shipment::STATUS_RTO_DELIVERED   => ShipmentScan::TYPE_RTO_DELIVERED,
            Shipment::STATUS_LOST            => ShipmentScan::TYPE_LOST,
            Shipment::STATUS_DAMAGED         => ShipmentScan::TYPE_DAMAGED,
            default                          => ShipmentScan::TYPE_IN_TRANSIT,
        };
    }
}
