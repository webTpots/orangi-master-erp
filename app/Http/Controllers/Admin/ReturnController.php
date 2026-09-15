<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnOrder;
use App\Services\ReturnService;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function __construct(
        private ReturnService $returnService,
    ) {}

    /**
     * List returns with filters and KPIs.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = ReturnOrder::where('company_id', $companyId)
            ->with(['order', 'items']);

        // Return type filter
        if ($type = $request->get('return_type')) {
            $query->where('return_type', $type);
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Date range
        if ($fromDate = $request->get('from_date')) {
            $query->where('initiated_at', '>=', $fromDate);
        }
        if ($toDate = $request->get('to_date')) {
            $query->where('initiated_at', '<=', $toDate . ' 23:59:59');
        }

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('marketplace_return_id', 'like', "%{$search}%")
                  ->orWhere('tracking_number', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($oq) use ($search) {
                      $oq->where('marketplace_order_id', 'like', "%{$search}%");
                  });
            });
        }

        $returns = $query->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        // KPIs
        $kpis = [
            'total'              => ReturnOrder::where('company_id', $companyId)->count(),
            'pending_inspection' => ReturnOrder::where('company_id', $companyId)->whereIn('status', ['initiated', 'in_transit', 'received'])->count(),
            'restocked'          => ReturnOrder::where('company_id', $companyId)->where('status', 'restocked')->count(),
            'rto_rate'           => $this->calculateRtoRate($companyId),
        ];

        return view('admin.returns.index', compact('returns', 'kpis'));
    }

    /**
     * Return detail.
     */
    public function show(ReturnOrder $returnOrder)
    {
        $this->authorizeCompany($returnOrder);

        $returnOrder->load([
            'order.marketplace',
            'subOrder',
            'items.sku',
            'items.variant',
            'inspections.inspector',
            'claims',
        ]);

        $availableTransitions = ReturnOrder::TRANSITIONS[$returnOrder->status] ?? [];

        return view('admin.returns.show', ['return' => $returnOrder, 'availableTransitions' => $availableTransitions]);
    }

    /**
     * Show create return form.
     */
    public function create(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $order = null;
        if ($orderId = $request->get('order_id')) {
            $order = Order::where('id', $orderId)
                ->where('company_id', $companyId)
                ->with('subOrders.sku')
                ->first();
        }

        // Recent orders for selection
        $orders = Order::where('company_id', $companyId)
            ->whereIn('status', ['delivered', 'in_transit', 'return', 'rto'])
            ->orderByDesc('order_date')
            ->limit(50)
            ->get();

        return view('admin.returns.create', compact('order', 'orders'));
    }

    /**
     * Store a new return.
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id'          => 'required|exists:orders,id',
            'return_type'       => 'required|in:' . implode(',', ReturnOrder::TYPES),
            'reason_category'   => 'required|in:' . implode(',', ReturnOrder::REASON_CATEGORIES),
            'reason_detail'     => 'nullable|string|max:1000',
            'marketplace_return_id' => 'nullable|string|max:100',
            'tracking_number'   => 'nullable|string|max:100',
            'courier_name'      => 'nullable|string|max:50',
            'items'             => 'nullable|array',
            'items.*.sku_id'    => 'required_with:items|exists:skus,id',
            'items.*.quantity'  => 'nullable|integer|min:1',
        ]);

        $order = Order::where('id', $request->order_id)
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();

        try {
            $returnOrder = $this->returnService->initiateReturn($order, $request->all());

            return redirect()->route('admin.returns.show', $returnOrder)
                ->with('success', 'Return initiated successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark return as received.
     */
    public function receive(ReturnOrder $returnOrder)
    {
        $this->authorizeCompany($returnOrder);

        try {
            $this->returnService->receiveReturn($returnOrder);

            return redirect()->route('admin.returns.show', $returnOrder)
                ->with('success', 'Return marked as received.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show inspection form.
     */
    public function inspect(ReturnOrder $returnOrder)
    {
        $this->authorizeCompany($returnOrder);

        $returnOrder->load(['items.sku', 'items.variant', 'order']);

        return view('admin.returns.inspect', ['return' => $returnOrder]);
    }

    /**
     * Store inspection.
     */
    public function storeInspection(Request $request, ReturnOrder $returnOrder)
    {
        $this->authorizeCompany($returnOrder);

        $request->validate([
            'condition'        => 'required|in:' . implode(',', \App\Models\ReturnInspection::CONDITIONS),
            'is_resellable'    => 'nullable|boolean',
            'inspection_notes' => 'nullable|string|max:2000',
            'items'            => 'nullable|array',
            'items.*.id'       => 'required_with:items|exists:return_items,id',
            'items.*.condition' => 'required_with:items|in:' . implode(',', \App\Models\ReturnInspection::CONDITIONS),
            'items.*.restock_quantity' => 'nullable|integer|min:0',
            'items.*.dispose_quantity' => 'nullable|integer|min:0',
        ]);

        try {
            $this->returnService->performInspection($returnOrder, $request->all());

            return redirect()->route('admin.returns.show', $returnOrder)
                ->with('success', 'Inspection recorded successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Restock inspected items.
     */
    public function restock(ReturnOrder $returnOrder)
    {
        $this->authorizeCompany($returnOrder);

        try {
            $this->returnService->restockItems($returnOrder);

            return redirect()->route('admin.returns.show', $returnOrder)
                ->with('success', 'Items restocked successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Close a return.
     */
    public function close(ReturnOrder $returnOrder)
    {
        $this->authorizeCompany($returnOrder);

        try {
            $this->returnService->closeReturn($returnOrder);

            return redirect()->route('admin.returns.show', $returnOrder)
                ->with('success', 'Return closed successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function authorizeCompany(ReturnOrder $returnOrder): void
    {
        if ($returnOrder->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }

    private function calculateRtoRate(int $companyId): float
    {
        $totalOrders = Order::where('company_id', $companyId)
            ->whereIn('status', ['delivered', 'return', 'rto'])
            ->count();

        if ($totalOrders === 0) {
            return 0;
        }

        $rtoCount = ReturnOrder::where('company_id', $companyId)
            ->where('return_type', 'rto')
            ->count();

        return round(($rtoCount / $totalOrders) * 100, 1);
    }
}
