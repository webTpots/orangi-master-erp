<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Sku;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private PurchaseService $purchaseService
    ) {}

    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = PurchaseOrder::where('company_id', $companyId)
            ->with(['vendor', 'creator']);

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filter by vendor
        if ($vendorId = $request->get('vendor_id')) {
            $query->where('vendor_id', $vendorId);
        }

        // Filter by date range
        if ($from = $request->get('from_date')) {
            $query->where('order_date', '>=', $from);
        }
        if ($to = $request->get('to_date')) {
            $query->where('order_date', '<=', $to);
        }

        // Search by PO number
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $purchaseOrders = $query->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // KPIs
        $kpis = [
            'total'     => PurchaseOrder::where('company_id', $companyId)->count(),
            'pending'   => PurchaseOrder::where('company_id', $companyId)->pending()->count(),
            'confirmed' => PurchaseOrder::where('company_id', $companyId)->whereIn('status', ['confirmed', 'partially_confirmed'])->count(),
            'short'     => PurchaseOrder::where('company_id', $companyId)
                ->whereIn('status', ['partially_confirmed', 'confirmed'])
                ->whereHas('lines', function ($q) {
                    $q->whereColumn('quantity_confirmed', '<', 'quantity_requested');
                })->count(),
        ];

        $vendors = Vendor::where('company_id', $companyId)->active()->orderBy('name')->get();

        return view('admin.purchase-orders.index', compact('purchaseOrders', 'kpis', 'vendors'));
    }

    public function create()
    {
        $companyId = auth()->user()->company_id;

        $vendors = Vendor::where('company_id', $companyId)
            ->active()
            ->orderBy('name')
            ->get();

        $skus = Sku::where('company_id', $companyId)
            ->active()
            ->with(['variant.product.design'])
            ->get()
            ->map(function ($sku) {
                return [
                    'id'         => $sku->id,
                    'sku_code'   => $sku->sku_code,
                    'name'       => $sku->short_name,
                    'cost_price' => $sku->cost_price,
                ];
            });

        return view('admin.purchase-orders.create', compact('vendors', 'skus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vendor_id'               => 'required|exists:vendors,id',
            'order_date'              => 'required|date',
            'expected_delivery_date'  => 'nullable|date|after_or_equal:order_date',
            'notes'                   => 'nullable|string',
            'lines'                   => 'required|array|min:1',
            'lines.*.sku_id'          => 'required|exists:skus,id',
            'lines.*.quantity'        => 'required|integer|min:1',
            'lines.*.unit_cost'       => 'required|numeric|min:0',
        ]);

        $po = $this->purchaseService->createPO(
            vendorId: $request->vendor_id,
            lines: $request->lines,
            companyId: auth()->user()->company_id,
            userId: auth()->id(),
            extra: $request->only(['order_date', 'expected_delivery_date', 'notes']),
        );

        return redirect()->route('admin.purchase-orders.show', $po)
            ->with('success', 'Purchase Order created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeCompany($purchaseOrder);

        $purchaseOrder->load([
            'vendor',
            'creator',
            'approver',
            'lines.sku.variant.product.design',
            'receipts.lines',
            'receipts.receiver',
            'receipts.warehouse',
        ]);

        $shortages = $this->purchaseService->calculateShortage($purchaseOrder);

        return view('admin.purchase-orders.show', compact('purchaseOrder', 'shortages'));
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeCompany($purchaseOrder);

        try {
            $this->purchaseService->approvePO($purchaseOrder, auth()->id());
            return redirect()->route('admin.purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase Order approved and sent.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorizeCompany($purchaseOrder);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->purchaseService->cancelPO($purchaseOrder, $request->reason);
            return redirect()->route('admin.purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase Order cancelled.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function receiveForm(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeCompany($purchaseOrder);

        if (! $purchaseOrder->canReceiveGoods()) {
            return redirect()->route('admin.purchase-orders.show', $purchaseOrder)
                ->with('error', 'This PO cannot receive goods in its current state.');
        }

        $purchaseOrder->load(['vendor', 'lines.sku.variant.product.design']);

        $warehouses = Warehouse::where('company_id', auth()->user()->company_id)
            ->active()
            ->orderBy('name')
            ->get();

        return view('admin.purchase-orders.receive', compact('purchaseOrder', 'warehouses'));
    }

    public function receiveStore(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorizeCompany($purchaseOrder);

        $request->validate([
            'warehouse_id'                     => 'required|exists:warehouses,id',
            'lines'                            => 'required|array|min:1',
            'lines.*.po_line_id'               => 'required|exists:purchase_order_lines,id',
            'lines.*.quantity_received'         => 'required|integer|min:0',
            'lines.*.quantity_accepted'         => 'required|integer|min:0',
            'lines.*.quantity_rejected'         => 'nullable|integer|min:0',
            'lines.*.rejection_reason'          => 'nullable|string|max:500',
        ]);

        // Filter out lines with 0 received
        $receivedLines = collect($request->lines)
            ->filter(fn ($l) => ($l['quantity_received'] ?? 0) > 0)
            ->values()
            ->toArray();

        if (empty($receivedLines)) {
            return redirect()->back()->with('error', 'No quantities were entered.');
        }

        try {
            $gr = $this->purchaseService->receiveGoods(
                po: $purchaseOrder,
                receivedLines: $receivedLines,
                warehouseId: $request->warehouse_id,
                userId: auth()->id(),
            );

            return redirect()->route('admin.purchase-orders.show', $purchaseOrder)
                ->with('success', "Goods Receipt {$gr->receipt_number} created successfully.");
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function authorizeCompany(PurchaseOrder $po): void
    {
        if ($po->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }
}
