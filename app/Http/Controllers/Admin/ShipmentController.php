<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function __construct(
        private ShipmentService $shipmentService,
    ) {}

    /**
     * Shipment list with filters, KPIs, search, pagination.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = Shipment::where('company_id', $companyId)
            ->with(['order']);

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Courier filter
        if ($courier = $request->get('courier')) {
            $query->where('courier_name', $courier);
        }

        // Date range
        if ($fromDate = $request->get('from_date')) {
            $query->where('created_at', '>=', $fromDate);
        }
        if ($toDate = $request->get('to_date')) {
            $query->where('created_at', '<=', $toDate . ' 23:59:59');
        }

        // Search
        if ($search = $request->get('search')) {
            $query->search($search);
        }

        $shipments = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        // KPIs
        $kpis = [
            'total'          => Shipment::where('company_id', $companyId)->count(),
            'in_transit'     => Shipment::where('company_id', $companyId)->whereIn('status', ['in_transit', 'out_for_delivery'])->count(),
            'delivered_today' => Shipment::where('company_id', $companyId)->where('status', 'delivered')
                ->whereDate('delivered_at', today())->count(),
            'rto_rate'       => $this->calculateRtoRate($companyId),
        ];

        // Distinct couriers for filter chips
        $couriers = Shipment::where('company_id', $companyId)
            ->distinct()
            ->pluck('courier_name')
            ->sort()
            ->values();

        return view('admin.shipments.index', compact('shipments', 'kpis', 'couriers'));
    }

    /**
     * Shipment detail with scan timeline.
     */
    public function show(Shipment $shipment)
    {
        $this->authorizeCompany($shipment);

        $shipment->load(['order.subOrders', 'scans', 'scanLogs.user']);

        $timeline = $this->shipmentService->getShipmentTimeline($shipment);

        return view('admin.shipments.show', compact('shipment', 'timeline'));
    }

    /**
     * Create shipment from an order.
     */
    public function createFromOrder(Request $request, Order $order)
    {
        if ($order->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        try {
            $shipment = $this->shipmentService->createShipment($order, $request->all());

            return redirect()->route('admin.shipments.show', $shipment)
                ->with('success', 'Shipment created successfully for order ' . $order->marketplace_order_id . '.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark shipment as dispatched (picked up).
     */
    public function dispatch(Request $request, Shipment $shipment)
    {
        $this->authorizeCompany($shipment);

        try {
            $this->shipmentService->updateShipmentStatus($shipment, Shipment::STATUS_PICKED_UP, [
                'scan_type'          => 'pickup',
                'location'           => $request->get('location'),
                'status_description' => 'Dispatched from warehouse',
            ]);

            // Record scan log
            $this->shipmentService->recordScan(
                'dispatch',
                $shipment->tracking_number,
                Shipment::class,
                $shipment->id,
                'manual',
                $request->get('location'),
            );

            return redirect()->route('admin.shipments.show', $shipment)
                ->with('success', 'Shipment dispatched successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk dispatch shipments.
     */
    public function bulkDispatch(Request $request)
    {
        $request->validate([
            'shipment_ids'   => 'required|array|min:1',
            'shipment_ids.*' => 'integer|exists:shipments,id',
        ]);

        $result = $this->shipmentService->bulkDispatch($request->shipment_ids);

        $message = "{$result['succeeded']} shipment(s) dispatched.";
        if ($result['failed'] > 0) {
            $message .= " {$result['failed']} shipment(s) could not be dispatched.";
        }

        return redirect()->route('admin.shipments.index')
            ->with($result['failed'] > 0 ? 'warning' : 'success', $message);
    }

    private function authorizeCompany(Shipment $shipment): void
    {
        if ($shipment->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }

    private function calculateRtoRate(int $companyId): string
    {
        $total = Shipment::where('company_id', $companyId)
            ->whereIn('status', ['delivered', 'rto_initiated', 'rto_in_transit', 'rto_delivered'])
            ->count();

        if ($total === 0) {
            return '0%';
        }

        $rto = Shipment::where('company_id', $companyId)
            ->whereIn('status', ['rto_initiated', 'rto_in_transit', 'rto_delivered'])
            ->count();

        return round(($rto / $total) * 100, 1) . '%';
    }
}
