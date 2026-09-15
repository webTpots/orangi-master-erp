<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sku;
use App\Models\SkuMapping;
use App\Models\MarketplaceAccount;
use Illuminate\Http\Request;

class SkuMappingController extends Controller
{
    /**
     * Display all SKU mappings with filters.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = SkuMapping::whereHas('sku', fn ($q) => $q->where('company_id', $companyId))
            ->with(['sku.variant.product.design', 'mapper']);

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by source / marketplace account
        if ($accountId = $request->input('marketplace_account_id')) {
            $query->where('marketplace_account_id', $accountId);
        }

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('marketplace_sku', 'LIKE', "%{$search}%")
                  ->orWhereHas('sku', fn ($sq) => $sq->where('sku_code', 'LIKE', "%{$search}%"));
            });
        }

        // Tab filters
        $tab = $request->input('tab', 'all');
        match ($tab) {
            'mapped'    => $query->where('status', 'active'),
            'suggested' => $query->where('status', 'suggested'),
            'rejected'  => $query->where('status', 'rejected'),
            default     => null,
        };

        $mappings = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        $accounts = MarketplaceAccount::where('company_id', $companyId)
            ->with('marketplace')
            ->get();

        return view('admin.sku-mappings.index', compact('mappings', 'accounts', 'tab'));
    }

    /**
     * Store a new SKU mapping (manual or from AI suggestion).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku_id'                 => 'required|exists:skus,id',
            'marketplace_account_id' => 'nullable|exists:marketplace_accounts,id',
            'marketplace_sku'        => 'required|string|max:255',
            'marketplace_product_id' => 'nullable|string|max:255',
            'marketplace_listing_id' => 'nullable|string|max:255',
            'confidence_score'       => 'nullable|numeric|min:0|max:1',
        ]);

        // Verify SKU belongs to user's company
        $sku = Sku::findOrFail($validated['sku_id']);
        abort_unless($sku->company_id === auth()->user()->company_id, 403);

        $mapping = SkuMapping::create(array_merge($validated, [
            'mapped_by'        => auth()->id(),
            'confidence_score' => $validated['confidence_score'] ?? 1.00,
            'status'           => 'active',
        ]));

        return redirect()
            ->route('admin.sku-mappings.index')
            ->with('success', "Mapping created: {$mapping->marketplace_sku} -> {$sku->sku_code}");
    }

    /**
     * Approve an AI-suggested mapping.
     */
    public function approve(SkuMapping $skuMapping)
    {
        abort_unless($skuMapping->sku->company_id === auth()->user()->company_id, 403);

        $skuMapping->update([
            'status'           => 'active',
            'confidence_score' => 1.00,
            'mapped_by'        => auth()->id(),
        ]);

        return redirect()
            ->back()
            ->with('success', "Mapping approved: {$skuMapping->marketplace_sku}");
    }

    /**
     * Reject a suggested mapping.
     */
    public function reject(SkuMapping $skuMapping)
    {
        abort_unless($skuMapping->sku->company_id === auth()->user()->company_id, 403);

        $skuMapping->update([
            'status'    => 'rejected',
            'mapped_by' => auth()->id(),
        ]);

        return redirect()
            ->back()
            ->with('success', "Mapping rejected: {$skuMapping->marketplace_sku}");
    }

    /**
     * Delete a mapping.
     */
    public function destroy(SkuMapping $skuMapping)
    {
        abort_unless($skuMapping->sku->company_id === auth()->user()->company_id, 403);

        $skuMapping->delete();

        return redirect()
            ->route('admin.sku-mappings.index')
            ->with('success', 'Mapping deleted.');
    }
}
