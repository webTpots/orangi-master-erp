<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Label;
use App\Models\Order;
use App\Models\ScanLog;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function __construct(
        private ShipmentService $shipmentService,
    ) {}

    /**
     * Scanning interface page.
     */
    public function index()
    {
        $companyId = auth()->user()->company_id;

        $recentScans = ScanLog::where('company_id', $companyId)
            ->with(['user', 'scannable'])
            ->orderByDesc('scanned_at')
            ->limit(20)
            ->get();

        return view('admin.scan.index', compact('recentScans'));
    }

    /**
     * Process a barcode/QR scan — identify item, perform action.
     */
    public function processScan(Request $request)
    {
        $request->validate([
            'barcode_data' => 'required|string|max:255',
            'scan_type'    => 'required|string|in:' . implode(',', ScanLog::SCAN_TYPES),
            'scan_method'  => 'nullable|string|in:' . implode(',', ScanLog::SCAN_METHODS),
            'location'     => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:500',
        ]);

        $companyId = auth()->user()->company_id;
        $barcodeData = trim($request->barcode_data);
        $scanType = $request->scan_type;
        $scanMethod = $request->scan_method ?? 'barcode';

        // Try to identify the scanned item
        $identified = $this->identifyBarcode($barcodeData, $companyId);

        if (! $identified) {
            // Log the scan even if unidentified
            $scanLog = $this->shipmentService->recordScan(
                $scanType,
                $barcodeData,
                null,
                null,
                $scanMethod,
                $request->location,
                'Unidentified barcode',
            );

            return redirect()->route('admin.scan.index')
                ->with('warning', "Barcode '{$barcodeData}' could not be identified. Scan logged.");
        }

        // Record the scan for the identified item
        $scanLog = $this->shipmentService->recordScan(
            $scanType,
            $barcodeData,
            $identified['type'],
            $identified['id'],
            $scanMethod,
            $request->location,
            $request->notes,
        );

        $itemLabel = $identified['label'];
        $message = "Scanned: {$itemLabel} — Action: " . (ScanLog::SCAN_TYPE_LABELS[$scanType] ?? $scanType);

        return redirect()->route('admin.scan.index')
            ->with('success', $message);
    }

    /**
     * List recent scan activity.
     */
    public function recentScans(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $scans = ScanLog::where('company_id', $companyId)
            ->with(['user', 'scannable'])
            ->orderByDesc('scanned_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.scan.recent', compact('scans'));
    }

    /**
     * Try to identify a barcode against shipments, orders, and labels.
     *
     * @return array{type: string, id: int, label: string}|null
     */
    private function identifyBarcode(string $barcodeData, int $companyId): ?array
    {
        // 1. Check Shipments (by tracking number or AWB)
        $shipment = Shipment::where('company_id', $companyId)
            ->where(function ($q) use ($barcodeData) {
                $q->where('tracking_number', $barcodeData)
                  ->orWhere('awb_number', $barcodeData);
            })
            ->first();

        if ($shipment) {
            return [
                'type'  => Shipment::class,
                'id'    => $shipment->id,
                'label' => "Shipment #{$shipment->tracking_number}",
            ];
        }

        // 2. Check Orders (by AWB or marketplace order ID)
        $order = Order::where('company_id', $companyId)
            ->where(function ($q) use ($barcodeData) {
                $q->where('awb_number', $barcodeData)
                  ->orWhere('marketplace_order_id', $barcodeData);
            })
            ->first();

        if ($order) {
            return [
                'type'  => Order::class,
                'id'    => $order->id,
                'label' => "Order #{$order->marketplace_order_id}",
            ];
        }

        // 3. Check Labels (by AWB or tracking number)
        $label = Label::where('company_id', $companyId)
            ->where(function ($q) use ($barcodeData) {
                $q->where('awb_number', $barcodeData)
                  ->orWhere('tracking_number', $barcodeData);
            })
            ->first();

        if ($label) {
            return [
                'type'  => Label::class,
                'id'    => $label->id,
                'label' => "Label AWB#{$label->awb_number}",
            ];
        }

        return null;
    }
}
