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

        // Filter by mapping status
        if ($status = $request->input('status')) {
            $query->where('mapping_status', $status);
        }

        // Filter by source type and source id
        if ($sourceType = $request->input('source_type')) {
            $query->where('source_type', $sourceType);
        }
        if ($sourceId = $request->input('source_id')) {
            $query->where('source_id', $sourceId);
        }

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('external_identifier', 'LIKE', "%{$search}%")
                  ->orWhere('external_name', 'LIKE', "%{$search}%")
                  ->orWhereHas('sku', fn ($sq) => $sq->where('sku_code', 'LIKE', "%{$search}%"));
            });
        }

        // Tab filters
        $tab = $request->input('tab', 'all');
        match ($tab) {
            'mapped'    => $query->where('mapping_status', 'confirmed'),
            'suggested' => $query->where('mapping_status', 'suggested'),
            'rejected'  => $query->where('mapping_status', 'rejected'),
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
            'sku_id'              => 'required|exists:skus,id',
            'external_identifier' => 'required|string|max:255',
            'external_name'       => 'nullable|string|max:255',
            'source_type'         => 'required|string|in:marketplace,vendor',
            'source_id'           => 'nullable|integer',
            'confidence_score'    => 'nullable|numeric|min:0|max:1',
        ]);

        // Verify SKU belongs to user's company
        $sku = Sku::findOrFail($validated['sku_id']);
        abort_unless($sku->company_id === auth()->user()->company_id, 403);

        $mapping = SkuMapping::create(array_merge($validated, [
            'company_id'       => auth()->user()->company_id,
            'mapped_by'        => auth()->id(),
            'confidence_score' => $validated['confidence_score'] ?? 1.00,
            'mapping_status'   => 'confirmed',
        ]));

        return redirect()
            ->route('admin.sku-mappings.index')
            ->with('success', "Mapping created: {$mapping->external_identifier} -> {$sku->sku_code}");
    }

    /**
     * Approve an AI-suggested mapping.
     */
    public function approve(SkuMapping $skuMapping)
    {
        abort_unless($skuMapping->sku->company_id === auth()->user()->company_id, 403);

        $skuMapping->update([
            'mapping_status'   => 'confirmed',
            'confidence_score' => 1.00,
            'mapped_by'        => auth()->id(),
        ]);

        return redirect()
            ->back()
            ->with('success', "Mapping approved: {$skuMapping->external_identifier}");
    }

    /**
     * Reject a suggested mapping.
     */
    public function reject(SkuMapping $skuMapping)
    {
        abort_unless($skuMapping->sku->company_id === auth()->user()->company_id, 403);

        $skuMapping->update([
            'mapping_status' => 'rejected',
            'mapped_by'      => auth()->id(),
        ]);

        return redirect()
            ->back()
            ->with('success', "Mapping rejected: {$skuMapping->external_identifier}");
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
