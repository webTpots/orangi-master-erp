<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShipmentResource;
use App\Http\Traits\ApiResponse;
use App\Models\ScanLog;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Http\Request;

class ShipmentApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ShipmentService $shipmentService,
    ) {}

    /**
     * Paginated shipments with filters.
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $query = Shipment::forCompany($companyId)
            ->with(['order'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('courier_name')) {
            $query->where('courier_name', 'like', "%{$request->courier_name}%");
        }

        $perPage = min((int) ($request->per_page ?? 20), 50);
        $paginator = $query->paginate($perPage);

        return $this->paginated(
            $paginator->through(fn ($shipment) => new ShipmentResource($shipment)),
            'Shipments retrieved.'
        );
    }

    /**
     * Shipment detail with scan timeline.
     */
    public function show(Request $request, Shipment $shipment)
    {
        if ($shipment->company_id !== $request->user()->company_id) {
            return $this->error('Shipment not found.', 404);
        }

        $shipment->load(['order', 'scans']);

        return $this->success(
            new ShipmentResource($shipment),
            'Shipment detail retrieved.'
        );
    }

    /**
     * Mark shipment as dispatched (picked up).
     */
    public function dispatch(Request $request, Shipment $shipment)
    {
        if ($shipment->company_id !== $request->user()->company_id) {
            return $this->error('Shipment not found.', 404);
        }

        if (! $shipment->canTransitionTo(Shipment::STATUS_PICKED_UP)) {
            return $this->error(
                "Cannot dispatch shipment from status '{$shipment->status}'.",
                422
            );
        }

        try {
            $this->shipmentService->updateShipmentStatus(
                $shipment,
                Shipment::STATUS_PICKED_UP,
                [
                    'scan_type'          => 'pickup',
                    'status_description' => 'Dispatched via mobile app',
                ]
            );

            // Record dispatch scan
            $this->shipmentService->recordScan(
                ScanLog::TYPE_DISPATCH,
                $shipment->tracking_number,
                Shipment::class,
                $shipment->id,
            );

            return $this->success(
                new ShipmentResource($shipment->fresh()->load(['order', 'scans'])),
                'Shipment dispatched.'
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
