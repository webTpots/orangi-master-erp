<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
    ) {}

    /**
     * Order list with filters, KPIs, search, pagination.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = Order::where('company_id', $companyId)
            ->with(['marketplace', 'subOrders']);

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Marketplace filter
        if ($marketplaceId = $request->get('marketplace_id')) {
            $query->where('marketplace_id', $marketplaceId);
        }

        // Date range
        if ($fromDate = $request->get('from_date')) {
            $query->where('order_date', '>=', $fromDate);
        }
        if ($toDate = $request->get('to_date')) {
            $query->where('order_date', '<=', $toDate);
        }

        // Courier filter
        if ($courier = $request->get('courier')) {
            $query->where('courier_partner', $courier);
        }

        // Payment type filter
        if ($paymentType = $request->get('payment_type')) {
            $query->where('payment_type', $paymentType);
        }

        // Stock status filter (via sub-orders)
        if ($stockStatus = $request->get('stock_status')) {
            $query->whereHas('subOrders', function ($q) use ($stockStatus) {
                $q->where('stock_status', $stockStatus);
            });
        }

        // Search
        if ($search = $request->get('search')) {
            $query->search($search);
        }

        $orders = $query->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        // KPIs
        $kpis = [
            'total'          => Order::where('company_id', $companyId)->count(),
            'new'            => Order::where('company_id', $companyId)->where('status', 'new')->count(),
            'ready_to_ship'  => Order::where('company_id', $companyId)->whereIn('status', ['ready_to_ship', 'scanned'])->count(),
            'shipped'        => Order::where('company_id', $companyId)->whereIn('status', ['handed_over', 'in_transit'])->count(),
            'delivered'      => Order::where('company_id', $companyId)->where('status', 'delivered')->count(),
            'returns'        => Order::where('company_id', $companyId)->whereIn('status', ['return', 'rto'])->count(),
            'sla_risk'       => Order::where('company_id', $companyId)->where('is_sla_risk', true)
                ->whereNotIn('status', ['delivered', 'cancelled', 'return', 'rto'])->count(),
        ];

        // Distinct courier partners for filter chips
        $couriers = Order::where('company_id', $companyId)
            ->whereNotNull('courier_partner')
            ->distinct()
            ->pluck('courier_partner')
            ->sort()
            ->values();

        $marketplaces = Marketplace::active()->orderBy('name')->get();

        return view('admin.orders.index', compact('orders', 'kpis', 'couriers', 'marketplaces'));
    }

    /**
     * Order detail with sub-orders, labels, status timeline, action buttons.
     */
    public function show(Order $order)
    {
        $this->authorizeCompany($order);

        $order->load([
            'marketplace',
            'subOrders.sku.variant.product.design',
            'labels.labelFile',
            'statusHistory.user',
        ]);

        // Determine available status transitions
        $availableTransitions = Order::TRANSITIONS[$order->status] ?? [];

        return view('admin.orders.show', compact('order', 'availableTransitions'));
    }

    /**
     * Change order status.
     */
    public function changeStatus(Request $request, Order $order)
    {
        $this->authorizeCompany($order);

        $request->validate([
            'status' => 'required|string|in:' . implode(',', Order::STATUSES),
            'notes'  => 'nullable|string|max:500',
        ]);

        try {
            $this->orderService->changeOrderStatus(
                $order,
                $request->status,
                $request->notes,
                auth()->id(),
            );

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Order status changed to ' . Order::STATUS_LABELS[$request->status] . '.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk change status for multiple orders.
     */
    public function bulkChangeStatus(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
            'status'    => 'required|string|in:' . implode(',', Order::STATUSES),
            'notes'     => 'nullable|string|max:500',
        ]);

        $companyId = auth()->user()->company_id;
        $succeeded = 0;
        $failed = 0;

        foreach ($request->order_ids as $orderId) {
            $order = Order::where('id', $orderId)
                ->where('company_id', $companyId)
                ->first();

            if (! $order) {
                $failed++;
                continue;
            }

            try {
                $this->orderService->changeOrderStatus(
                    $order,
                    $request->status,
                    $request->notes,
                    auth()->id(),
                );
                $succeeded++;
            } catch (\RuntimeException $e) {
                $failed++;
            }
        }

        $message = "{$succeeded} order(s) updated.";
        if ($failed > 0) {
            $message .= " {$failed} order(s) could not be transitioned.";
        }

        return redirect()->route('admin.orders.index')
            ->with($failed > 0 ? 'warning' : 'success', $message);
    }

    private function authorizeCompany(Order $order): void
    {
        if ($order->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }
}
