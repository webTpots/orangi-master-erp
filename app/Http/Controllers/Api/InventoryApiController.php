<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryItemResource;
use App\Http\Traits\ApiResponse;
use App\Models\InventoryItem;
use App\Models\Sku;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    /**
     * Paginated inventory items with filters.
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $query = InventoryItem::where('inventory_items.company_id', $companyId)
            ->with(['sku', 'warehouse']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('sku', function ($q) use ($search) {
                $q->where('sku_code', 'like', "%{$search}%")
                  ->orWhere('sku_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->boolean('low_stock')) {
            $query->join('skus', 'skus.id', '=', 'inventory_items.sku_id')
                ->where('skus.minimum_stock_level', '>', 0)
                ->whereColumn('inventory_items.available_stock', '<=', 'skus.minimum_stock_level')
                ->select('inventory_items.*');
        }

        if ($request->boolean('out_of_stock')) {
            $query->where('inventory_items.available_stock', '<=', 0);
        }

        $query->orderBy('inventory_items.updated_at', 'desc');

        $perPage = min((int) ($request->per_page ?? 20), 50);
        $paginator = $query->paginate($perPage);

        return $this->paginated(
            $paginator->through(fn ($item) => new InventoryItemResource($item)),
            'Inventory retrieved.'
        );
    }

    /**
     * Inventory item detail with ledger history.
     */
    public function show(Request $request, InventoryItem $item)
    {
        if ($item->company_id !== $request->user()->company_id) {
            return $this->error('Inventory item not found.', 404);
        }

        $item->load(['sku', 'warehouse', 'ledgerEntries' => function ($q) {
            $q->orderByDesc('created_at')->limit(50);
        }]);

        return $this->success(
            new InventoryItemResource($item),
            'Inventory item detail retrieved.'
        );
    }

    /**
     * Stock adjustment (add/deduct with reason).
     */
    public function adjust(Request $request)
    {
        $request->validate([
            'sku_id'          => 'required|integer|exists:skus,id',
            'warehouse_id'    => 'required|integer|exists:warehouses,id',
            'quantity'        => 'required|integer|min:1',
            'adjustment_type' => 'required|string|in:add,remove,damage,block,unblock',
            'reason'          => 'required|string|max:500',
        ]);

        $companyId = $request->user()->company_id;

        $sku = Sku::where('id', $request->sku_id)
            ->where('company_id', $companyId)
            ->first();

        if (! $sku) {
            return $this->error('SKU not found.', 404);
        }

        $warehouse = Warehouse::where('id', $request->warehouse_id)
            ->where('company_id', $companyId)
            ->first();

        if (! $warehouse) {
            return $this->error('Warehouse not found.', 404);
        }

        try {
            $ledgerEntry = $this->inventoryService->adjustStock(
                $sku,
                $warehouse,
                $request->quantity,
                $request->reason,
                $request->user()->id,
                $request->adjustment_type,
            );

            return $this->success([
                'ledger_entry_id' => $ledgerEntry->id,
                'balance_after'   => $ledgerEntry->balance_after,
            ], 'Stock adjusted successfully.');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Search SKU and return stock info instantly.
     */
    public function quickCheck(Request $request)
    {
        $request->validate([
            'sku' => 'required|string',
        ]);

        $companyId = $request->user()->company_id;

        $sku = Sku::where('company_id', $companyId)
            ->where(function ($q) use ($request) {
                $q->where('sku_code', $request->sku)
                  ->orWhere('sku_code', 'like', "%{$request->sku}%");
            })
            ->first();

        if (! $sku) {
            return $this->error('SKU not found.', 404);
        }

        $balance = $this->inventoryService->getStockBalance($sku);

        return $this->success([
            'sku_id'    => $sku->id,
            'sku_code'  => $sku->sku_code,
            'sku_name'  => $sku->sku_code,
            'stock'     => $balance,
        ], 'Stock info retrieved.');
    }

    /**
     * Low stock and stockout alerts.
     */
    public function alerts(Request $request)
    {
        $companyId = $request->user()->company_id;

        // Low stock items
        $lowStock = DB::table('inventory_items')
            ->join('skus', 'skus.id', '=', 'inventory_items.sku_id')
            ->where('inventory_items.company_id', $companyId)
            ->where('skus.minimum_stock_level', '>', 0)
            ->whereColumn('inventory_items.available_stock', '<=', 'skus.minimum_stock_level')
            ->where('inventory_items.available_stock', '>', 0)
            ->select(
                'inventory_items.id',
                'inventory_items.sku_id',
                'skus.sku_code',
                'skus.sku_code',
                'inventory_items.available_stock',
                'skus.minimum_stock_level'
            )
            ->orderBy('inventory_items.available_stock')
            ->limit(50)
            ->get();

        // Out of stock items
        $outOfStock = DB::table('inventory_items')
            ->join('skus', 'skus.id', '=', 'inventory_items.sku_id')
            ->where('inventory_items.company_id', $companyId)
            ->where('inventory_items.available_stock', '<=', 0)
            ->select(
                'inventory_items.id',
                'inventory_items.sku_id',
                'skus.sku_code',
                'skus.sku_code',
                'inventory_items.available_stock'
            )
            ->orderBy('inventory_items.available_stock')
            ->limit(50)
            ->get();

        return $this->success([
            'low_stock'     => $lowStock,
            'out_of_stock'  => $outOfStock,
            'low_stock_count'    => $lowStock->count(),
            'out_of_stock_count' => $outOfStock->count(),
        ], 'Inventory alerts retrieved.');
    }
}
