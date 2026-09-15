<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Traits\ApiResponse;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        private OrderService $orderService,
    ) {}

    /**
     * Paginated orders with filters.
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $query = Order::forCompany($companyId)
            ->with(['marketplace', 'subOrders'])
            ->orderByDesc('order_date')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        if ($request->filled('marketplace_id')) {
            $query->where('marketplace_id', $request->marketplace_id);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $perPage = min((int) ($request->per_page ?? 20), 50);
        $paginator = $query->paginate($perPage);

        return $this->paginated(
            $paginator->through(fn ($order) => new OrderResource($order)),
            'Orders retrieved.'
        );
    }

    /**
     * Order detail with sub_orders, labels, shipments.
     */
    public function show(Request $request, Order $order)
    {
        if ($order->company_id !== $request->user()->company_id) {
            return $this->error('Order not found.', 404);
        }

        $order->load(['marketplace', 'subOrders', 'labels', 'shipments', 'statusHistory']);

        $resource = (new OrderResource($order))->toArray($request);

        // Add status history to the detail view
        $resource['status_history'] = $order->statusHistory->map(fn ($h) => [
            'from_status' => $h->from_status,
            'to_status'   => $h->to_status,
            'notes'       => $h->notes,
            'changed_at'  => $h->changed_at?->toIso8601String(),
        ]);

        return $this->success($resource, 'Order detail retrieved.');
    }

    /**
     * Update order status.
     */
    public function changeStatus(Request $request, Order $order)
    {
        if ($order->company_id !== $request->user()->company_id) {
            return $this->error('Order not found.', 404);
        }

        $request->validate([
            'status' => 'required|string|in:' . implode(',', Order::STATUSES),
            'notes'  => 'nullable|string|max:500',
        ]);

        if (! $order->canTransitionTo($request->status)) {
            return $this->error(
                "Cannot transition from '{$order->status}' to '{$request->status}'.",
                422
            );
        }

        try {
            $this->orderService->changeOrderStatus(
                $order,
                $request->status,
                $request->notes,
                $request->user()->id
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(
            new OrderResource($order->fresh()->load(['marketplace', 'subOrders'])),
            'Order status updated.'
        );
    }

    /**
     * Order KPIs.
     */
    public function stats(Request $request)
    {
        $companyId = $request->user()->company_id;
        $today = now()->toDateString();

        $total = Order::forCompany($companyId)->count();
        $todayCount = Order::forCompany($companyId)->whereDate('order_date', $today)->count();

        $byStatus = [];
        foreach (Order::STATUSES as $status) {
            $byStatus[$status] = Order::forCompany($companyId)->where('status', $status)->count();
        }

        $slaRisk = Order::forCompany($companyId)->slaRisk()->count();

        return $this->success([
            'total'     => $total,
            'today'     => $todayCount,
            'sla_risk'  => $slaRisk,
            'by_status' => $byStatus,
        ], 'Order stats retrieved.');
    }
}
