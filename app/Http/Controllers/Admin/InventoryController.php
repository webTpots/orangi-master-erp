<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\InventoryItem;
use App\Models\InventoryLedger;
use App\Models\Sku;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService
    ) {}

    /**
     * Stock overview — list all SKUs with current stock levels.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = Sku::forCompany($companyId)
            ->with(['variant.product.design', 'inventoryItems.warehouse']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('sku_code', 'LIKE', "%{$search}%")
                  ->orWhereHas('variant', fn ($vq) => $vq->where('color', 'LIKE', "%{$search}%")->orWhere('size', 'LIKE', "%{$search}%"))
                  ->orWhereHas('variant.product.design', fn ($dq) => $dq->where('name', 'LIKE', "%{$search}%")->orWhere('code', 'LIKE', "%{$search}%"));
            });
        }

        // Filter by warehouse
        if ($warehouseId = $request->input('warehouse_id')) {
            $query->whereHas('inventoryItems', fn ($q) => $q->where('warehouse_id', $warehouseId));
        }

        // Filter by design
        if ($designId = $request->input('design_id')) {
            $query->whereHas('variant.product', fn ($q) => $q->where('design_id', $designId));
        }

        // Filter by color
        if ($color = $request->input('color')) {
            $query->whereHas('variant', fn ($q) => $q->where('color', $color));
        }

        // Filter by size
        if ($size = $request->input('size')) {
            $query->whereHas('variant', fn ($q) => $q->where('size', $size));
        }

        // Filter by stock level
        if ($stockLevel = $request->input('stock_level')) {
            match ($stockLevel) {
                'out'  => $query->where(function ($q) {
                    $q->whereDoesntHave('inventoryItems')
                      ->orWhereHas('inventoryItems', fn ($iq) => $iq->havingRaw('SUM(available_stock) <= 0')->groupBy('sku_id'));
                }),
                'low'  => $query->whereHas('inventoryItems', fn ($q) => $q->where('available_stock', '>', 0)->where('available_stock', '<=', 5)),
                'in'   => $query->whereHas('inventoryItems', fn ($q) => $q->where('available_stock', '>', 0)),
                default => null,
            };
        }

        $skus = $query->orderBy('sku_code')->paginate(25)->withQueryString();

        // KPIs
        $allItems = InventoryItem::where('company_id', $companyId);
        $kpis = [
            'total_skus'    => Sku::forCompany($companyId)->count(),
            'total_stock'   => (clone $allItems)->sum('physical_stock'),
            'stock_value'   => (clone $allItems)->sum('total_value'),
            'low_stock'     => (clone $allItems)->where('available_stock', '>', 0)->where('available_stock', '<=', 5)->count(),
            'out_of_stock'  => (clone $allItems)->where('available_stock', '<=', 0)->count(),
        ];

        // Filter data
        $warehouses = Warehouse::where('company_id', $companyId)->active()->orderBy('name')->get();
        $designs = Design::forCompany($companyId)->active()->orderBy('name')->get();

        return view('admin.inventory.index', compact('skus', 'kpis', 'warehouses', 'designs'));
    }

    /**
     * SKU inventory detail.
     */
    public function show(Sku $sku)
    {
        abort_unless($sku->company_id === auth()->user()->company_id, 403);

        $sku->load(['variant.product.design', 'inventoryItems.warehouse']);

        $balance = $this->inventoryService->getStockBalance($sku);

        // Ledger history
        $ledger = InventoryLedger::where('sku_id', $sku->id)
            ->with(['warehouse', 'performer'])
            ->orderByDesc('created_at')
            ->paginate(50);

        // Stock by warehouse
        $stockByWarehouse = $sku->inventoryItems->map(fn ($item) => [
            'warehouse' => $item->warehouse->name ?? 'Unknown',
            'physical'  => $item->physical_stock,
            'reserved'  => $item->reserved_stock,
            'available' => $item->available_stock,
            'damaged'   => $item->damaged_stock,
            'blocked'   => $item->blocked_stock,
            'value'     => $item->total_value,
        ]);

        return view('admin.inventory.show', compact('sku', 'balance', 'ledger', 'stockByWarehouse'));
    }

    /**
     * Manual adjustment form.
     */
    public function adjustCreate()
    {
        $companyId = auth()->user()->company_id;
        $skus = Sku::forCompany($companyId)->with(['variant.product.design', 'inventoryItems'])->active()->orderBy('sku_code')->get();
        $warehouses = Warehouse::where('company_id', $companyId)->active()->orderBy('name')->get();

        return view('admin.inventory.adjust', compact('skus', 'warehouses'));
    }

    /**
     * Process manual adjustment.
     */
    public function adjustStore(Request $request)
    {
        $request->validate([
            'sku_id'          => 'required|exists:skus,id',
            'warehouse_id'    => 'required|exists:warehouses,id',
            'adjustment_type' => 'required|in:add,remove,damage,block,unblock',
            'quantity'        => 'required|integer|min:1',
            'reason'          => 'required|string|max:500',
        ]);

        $sku = Sku::findOrFail($request->sku_id);
        abort_unless($sku->company_id === auth()->user()->company_id, 403);

        $warehouse = Warehouse::findOrFail($request->warehouse_id);

        try {
            $this->inventoryService->adjustStock(
                $sku,
                $warehouse,
                $request->quantity,
                $request->reason,
                auth()->id(),
                $request->adjustment_type
            );

            return redirect()->route('admin.inventory.index')
                ->with('success', "Stock adjusted: {$request->adjustment_type} {$request->quantity} units of {$sku->sku_code}.");
        } catch (\RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Transfer form.
     */
    public function transferCreate()
    {
        $companyId = auth()->user()->company_id;
        $skus = Sku::forCompany($companyId)->with(['variant.product.design', 'inventoryItems.warehouse'])->active()->orderBy('sku_code')->get();
        $warehouses = Warehouse::where('company_id', $companyId)->active()->orderBy('name')->get();

        return view('admin.inventory.transfer', compact('skus', 'warehouses'));
    }

    /**
     * Process stock transfer.
     */
    public function transferStore(Request $request)
    {
        $request->validate([
            'sku_id'           => 'required|exists:skus,id',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id'   => 'required|exists:warehouses,id|different:from_warehouse_id',
            'quantity'          => 'required|integer|min:1',
        ]);

        $sku = Sku::findOrFail($request->sku_id);
        abort_unless($sku->company_id === auth()->user()->company_id, 403);

        $from = Warehouse::findOrFail($request->from_warehouse_id);
        $to = Warehouse::findOrFail($request->to_warehouse_id);

        try {
            $this->inventoryService->transferStock($sku, $from, $to, $request->quantity, auth()->id());

            return redirect()->route('admin.inventory.index')
                ->with('success', "Transferred {$request->quantity} units of {$sku->sku_code} from {$from->name} to {$to->name}.");
        } catch (\RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Opening stock import wizard.
     */
    public function openingStock()
    {
        $companyId = auth()->user()->company_id;
        $warehouses = Warehouse::where('company_id', $companyId)->active()->orderBy('name')->get();

        return view('admin.inventory.opening-stock', compact('warehouses'));
    }

    /**
     * Process opening stock import.
     */
    public function openingStockStore(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'csv_file'     => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $companyId = auth()->user()->company_id;
        $file = $request->file('csv_file');

        $rows = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_shift($rows); // Remove header row

        $items = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            if (count($row) < 3) continue;

            $skuCode = trim($row[0] ?? '');
            $quantity = (int) trim($row[1] ?? 0);
            $unitCost = (float) trim($row[2] ?? 0);

            $sku = Sku::where('sku_code', $skuCode)->where('company_id', $companyId)->first();

            if (!$sku) {
                $errors[] = "Row " . ($index + 2) . ": SKU '{$skuCode}' not found.";
                continue;
            }

            if ($quantity <= 0) {
                $errors[] = "Row " . ($index + 2) . ": Invalid quantity for {$skuCode}.";
                continue;
            }

            $items[] = [
                'sku_id'    => $sku->id,
                'quantity'  => $quantity,
                'unit_cost' => $unitCost > 0 ? $unitCost : ($sku->cost_price ?? 0),
            ];
        }

        if (empty($items)) {
            return back()->withErrors(['csv_file' => 'No valid items found in CSV. ' . implode(' ', $errors)])->withInput();
        }

        $batch = $this->inventoryService->importOpeningStock(
            $items,
            $request->warehouse_id,
            $companyId,
            auth()->id()
        );

        $message = "Opening stock imported: {$batch->successful_records} items successful, {$batch->failed_records} failed.";
        if (!empty($errors)) {
            $message .= ' Skipped rows: ' . implode(', ', array_slice($errors, 0, 5));
        }

        return redirect()->route('admin.inventory.index')->with('success', $message);
    }

    /**
     * Physical count form.
     */
    public function physicalCount(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $warehouses = Warehouse::where('company_id', $companyId)->active()->orderBy('name')->get();

        $warehouseId = $request->input('warehouse_id');
        $items = collect();

        if ($warehouseId) {
            $items = InventoryItem::where('warehouse_id', $warehouseId)
                ->where('company_id', $companyId)
                ->with(['sku.variant.product.design'])
                ->orderBy('sku_id')
                ->get();
        }

        return view('admin.inventory.physical-count', compact('warehouses', 'items', 'warehouseId'));
    }

    /**
     * Process physical count adjustments.
     */
    public function physicalCountStore(Request $request)
    {
        $request->validate([
            'warehouse_id'   => 'required|exists:warehouses,id',
            'counts'         => 'required|array',
            'counts.*.sku_id' => 'required|exists:skus,id',
            'counts.*.actual' => 'required|integer|min:0',
        ]);

        $companyId = auth()->user()->company_id;
        $warehouse = Warehouse::findOrFail($request->warehouse_id);
        $adjustments = 0;

        DB::transaction(function () use ($request, $warehouse, $companyId, &$adjustments) {
            foreach ($request->counts as $count) {
                $sku = Sku::find($count['sku_id']);
                if (!$sku || $sku->company_id !== $companyId) continue;

                $item = InventoryItem::where('sku_id', $sku->id)
                    ->where('warehouse_id', $warehouse->id)
                    ->first();

                $systemStock = $item ? $item->physical_stock : 0;
                $actualStock = (int) $count['actual'];
                $difference = $actualStock - $systemStock;

                if ($difference === 0) continue;

                if ($difference > 0) {
                    $this->inventoryService->adjustStock(
                        $sku, $warehouse, $difference,
                        'Physical count adjustment (excess)',
                        auth()->id(), 'add'
                    );
                } else {
                    $this->inventoryService->adjustStock(
                        $sku, $warehouse, abs($difference),
                        'Physical count adjustment (shortage)',
                        auth()->id(), 'remove'
                    );
                }

                // Update last counted timestamp
                if ($item) {
                    $item->update(['last_counted_at' => now()]);
                }

                $adjustments++;
            }
        });

        return redirect()->route('admin.inventory.index')
            ->with('success', "Physical count completed. {$adjustments} adjustment(s) created.");
    }

    /**
     * Export current stock as CSV.
     */
    public function export(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $items = InventoryItem::where('company_id', $companyId)
            ->with(['sku.variant.product.design', 'warehouse'])
            ->orderBy('sku_id')
            ->get();

        $filename = 'stock-export-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($items) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'SKU Code', 'Design', 'Color', 'Size', 'Warehouse',
                'Physical', 'Reserved', 'Available', 'Damaged', 'Blocked',
                'Unit Cost', 'Total Value',
            ]);

            foreach ($items as $item) {
                fputcsv($file, [
                    $item->sku->sku_code ?? '',
                    $item->sku->variant?->product?->design?->name ?? '',
                    $item->sku->variant?->color ?? '',
                    $item->sku->variant?->size ?? '',
                    $item->warehouse->name ?? '',
                    $item->physical_stock,
                    $item->reserved_stock,
                    $item->available_stock,
                    $item->damaged_stock,
                    $item->blocked_stock,
                    number_format($item->unit_cost ?? 0, 4),
                    number_format($item->total_value ?? 0, 2),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
