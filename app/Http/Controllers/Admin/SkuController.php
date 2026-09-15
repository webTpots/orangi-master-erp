<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\Sku;
use Illuminate\Http\Request;

class SkuController extends Controller
{
    /**
     * Display all SKUs with search and filters.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = Sku::forCompany($companyId)
            ->with(['variant.product.design', 'skuMappings', 'inventoryItems']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('sku_code', 'LIKE', "%{$search}%")
                  ->orWhereHas('variant', function ($vq) use ($search) {
                      $vq->where('color', 'LIKE', "%{$search}%")
                         ->orWhere('size', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('variant.product.design', function ($dq) use ($search) {
                      $dq->where('name', 'LIKE', "%{$search}%")
                         ->orWhere('code', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Filter by design
        if ($designId = $request->input('design_id')) {
            $query->whereHas('variant.product', function ($q) use ($designId) {
                $q->where('design_id', $designId);
            });
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
        if ($stockFilter = $request->input('stock')) {
            match ($stockFilter) {
                'out_of_stock' => $query->whereDoesntHave('inventoryItems', fn ($q) => $q->where('available_stock', '>', 0)),
                'low_stock'    => $query->whereHas('inventoryItems', fn ($q) => $q->whereColumn('available_stock', '<=', 'minimum_stock_level')),
                'in_stock'     => $query->whereHas('inventoryItems', fn ($q) => $q->where('available_stock', '>', 0)),
                default        => null,
            };
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $skus = $query->orderBy('sku_code')->paginate(25)->withQueryString();

        // Data for filter dropdowns
        $designs = Design::forCompany($companyId)->active()->orderBy('name')->get(['id', 'name', 'code']);

        return view('admin.skus.index', compact('skus', 'designs'));
    }

    /**
     * Display a single SKU with all related data.
     */
    public function show(Sku $sku)
    {
        abort_unless($sku->company_id === auth()->user()->company_id, 403);

        $sku->load([
            'variant.product.design',
            'skuMappings.marketplaceAccount.marketplace',
            'inventoryItems.warehouse',
            'vendorProducts.vendor',
        ]);

        return view('admin.skus.show', compact('sku'));
    }
}
