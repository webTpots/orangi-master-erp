<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DesignController extends Controller
{
    public function __construct(
        protected ProductService $productService,
    ) {}

    /**
     * Display listing of designs with search, filter, pagination.
     */
    public function index(Request $request)
    {
        $query = Design::query()
            ->forCompany(auth()->user()->company_id)
            ->withCount(['products', 'products as variants_count' => function ($q) {
                // Count variants through products
            }]);

        // Load variant counts via products relationship
        $query->withCount('products')
              ->with(['products' => function ($q) {
                  $q->withCount('variants');
              }]);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%")
                  ->orWhere('hsn_code', 'LIKE', "%{$search}%");
            });
        }

        // Filter by category
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $designs = $query->orderBy('name')->paginate(20)->withQueryString();

        // Get unique categories for filter dropdown
        $categories = Design::forCompany(auth()->user()->company_id)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return view('admin.designs.index', compact('designs', 'categories'));
    }

    /**
     * Show the design creation form.
     */
    public function create()
    {
        return view('admin.designs.create');
    }

    /**
     * Store a new design with variants.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'           => 'required|string|max:50',
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'category'       => 'nullable|string|max:100',
            'hsn_code'       => 'nullable|string|max:20',
            'gst_rate'       => 'nullable|numeric|min:0|max:100',
            'image'          => 'nullable|image|max:2048',
            'selling_price'  => 'nullable|numeric|min:0',
            'cost_price'     => 'nullable|numeric|min:0',
            'mrp'            => 'nullable|numeric|min:0',
            'variants'       => 'nullable|array',
            'variants.*.color' => 'required_with:variants|string',
            'variants.*.sizes' => 'required_with:variants|array',
        ]);

        $data = array_merge($validated, [
            'company_id' => auth()->user()->company_id,
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['primary_image_path'] = $request->file('image')->store('designs', 'public');
        }

        $design = $this->productService->createDesign($data);

        return redirect()
            ->route('admin.designs.show', $design)
            ->with('success', "Design '{$design->name}' created successfully.");
    }

    /**
     * Display a single design with variants, SKUs, and mappings.
     */
    public function show(Design $design)
    {
        $this->authorizeCompany($design);

        $design->load([
            'products.variants.skus.skuMappings',
            'products.variants.skus.inventoryItems',
            'images',
        ]);

        // Build color x size matrix
        $colorSizeMatrix = [];
        foreach ($design->products as $product) {
            foreach ($product->variants as $variant) {
                $color = $variant->color ?? 'Default';
                $size  = $variant->size ?? 'One Size';
                $stock = $variant->skus->sum(fn ($s) => $s->inventoryItems->sum('available_stock'));

                $colorSizeMatrix[$color][$size] = [
                    'variant'  => $variant,
                    'sku'      => $variant->skus->first(),
                    'stock'    => $stock,
                    'mappings' => $variant->skus->flatMap->skuMappings->count(),
                ];
            }
        }

        // All unique sizes
        $sizes = collect($colorSizeMatrix)
            ->flatMap(fn ($sizes) => array_keys($sizes))
            ->unique()
            ->values();

        // All SKUs for this design
        $skus = $design->products
            ->flatMap(fn ($p) => $p->variants)
            ->flatMap(fn ($v) => $v->skus);

        return view('admin.designs.show', compact('design', 'colorSizeMatrix', 'sizes', 'skus'));
    }

    /**
     * Show the edit form for a design.
     */
    public function edit(Design $design)
    {
        $this->authorizeCompany($design);

        $design->load('products.variants.skus');

        return view('admin.designs.edit', compact('design'));
    }

    /**
     * Update a design.
     */
    public function update(Request $request, Design $design)
    {
        $this->authorizeCompany($design);

        $validated = $request->validate([
            'code'        => 'required|string|max:50',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:100',
            'hsn_code'    => 'nullable|string|max:20',
            'gst_rate'    => 'nullable|numeric|min:0|max:100',
            'image'       => 'nullable|image|max:2048',
            'status'      => 'nullable|in:active,inactive',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image
            if ($design->primary_image_path) {
                Storage::disk('public')->delete($design->primary_image_path);
            }
            $validated['primary_image_path'] = $request->file('image')->store('designs', 'public');
        }

        $design->update($validated);

        return redirect()
            ->route('admin.designs.show', $design)
            ->with('success', "Design '{$design->name}' updated.");
    }

    /**
     * Soft-destroy a design (set inactive).
     */
    public function destroy(Design $design)
    {
        $this->authorizeCompany($design);

        $design->update(['status' => 'inactive']);

        return redirect()
            ->route('admin.designs.index')
            ->with('success', "Design '{$design->name}' deactivated.");
    }

    /**
     * Ensure the design belongs to the authenticated user's company.
     */
    protected function authorizeCompany(Design $design): void
    {
        abort_unless($design->company_id === auth()->user()->company_id, 403);
    }
}
