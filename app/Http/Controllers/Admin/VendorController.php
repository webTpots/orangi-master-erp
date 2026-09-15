<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = Vendor::where('company_id', $companyId)
            ->withCount(['vendorProducts']);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $vendors = $query->orderBy('name')->paginate(20)->withQueryString();

        // Load active PO counts
        $vendorIds = $vendors->pluck('id');
        $activePoCounts = PurchaseOrder::whereIn('vendor_id', $vendorIds)
            ->active()
            ->selectRaw('vendor_id, count(*) as count')
            ->groupBy('vendor_id')
            ->pluck('count', 'vendor_id');

        return view('admin.vendors.index', compact('vendors', 'activePoCounts'));
    }

    public function create()
    {
        return view('admin.vendors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
            'gstin'          => 'nullable|string|max:15',
            'pan'            => 'nullable|string|max:10',
            'address'        => 'nullable|string',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:100',
            'pincode'        => 'nullable|string|max:6',
            'payment_terms'  => 'nullable|string|max:100',
            'lead_time_days' => 'nullable|integer|min:0',
        ]);

        $validated['company_id'] = auth()->user()->company_id;
        $validated['status'] = 'active';

        Vendor::create($validated);

        return redirect()->route('admin.vendors.index')
            ->with('success', 'Vendor created successfully.');
    }

    public function show(Vendor $vendor)
    {
        $this->authorizeCompany($vendor);

        $vendor->load(['vendorProducts.sku.variant.product.design']);

        $purchaseOrders = PurchaseOrder::where('vendor_id', $vendor->id)
            ->with(['lines'])
            ->orderByDesc('order_date')
            ->limit(20)
            ->get();

        // Performance metrics
        $metrics = [
            'total_pos'     => PurchaseOrder::where('vendor_id', $vendor->id)->count(),
            'active_pos'    => PurchaseOrder::where('vendor_id', $vendor->id)->active()->count(),
            'total_value'   => PurchaseOrder::where('vendor_id', $vendor->id)->whereNotIn('status', ['cancelled'])->sum('total_amount'),
            'products_count' => $vendor->vendorProducts()->count(),
        ];

        return view('admin.vendors.show', compact('vendor', 'purchaseOrders', 'metrics'));
    }

    public function edit(Vendor $vendor)
    {
        $this->authorizeCompany($vendor);

        return view('admin.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $this->authorizeCompany($vendor);

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
            'gstin'          => 'nullable|string|max:15',
            'pan'            => 'nullable|string|max:10',
            'address'        => 'nullable|string',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:100',
            'pincode'        => 'nullable|string|max:6',
            'payment_terms'  => 'nullable|string|max:100',
            'lead_time_days' => 'nullable|integer|min:0',
            'status'         => 'nullable|in:active,inactive',
        ]);

        $vendor->update($validated);

        return redirect()->route('admin.vendors.show', $vendor)
            ->with('success', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor)
    {
        $this->authorizeCompany($vendor);

        // Soft-deactivate rather than hard delete
        $vendor->update(['status' => 'inactive']);

        return redirect()->route('admin.vendors.index')
            ->with('success', 'Vendor deactivated successfully.');
    }

    private function authorizeCompany(Vendor $vendor): void
    {
        if ($vendor->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }
}
