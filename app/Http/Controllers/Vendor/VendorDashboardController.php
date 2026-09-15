<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Services\VendorPortalService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VendorDashboardController extends Controller
{
    public function __construct(
        private VendorPortalService $vendorPortalService
    ) {}

    public function login()
    {
        // If already logged in, redirect to dashboard
        if (session('vendor_portal_token')) {
            return redirect()->route('vendor.dashboard');
        }

        return view('vendor.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $vendor = $this->vendorPortalService->authenticateVendor($request->token);

        if (! $vendor) {
            return redirect()->route('vendor.login')
                ->with('error', 'Invalid or expired token. Please contact your buyer.');
        }

        session(['vendor_portal_token' => $request->token]);

        return redirect()->route('vendor.dashboard');
    }

    public function dashboard(Request $request)
    {
        $vendor = $request->attributes->get('vendor');
        $dashboardData = $this->vendorPortalService->getVendorDashboard($vendor);

        return view('vendor.dashboard', compact('vendor', 'dashboardData'));
    }

    public function purchaseOrders(Request $request)
    {
        $vendor = $request->attributes->get('vendor');
        $status = $request->get('status');

        $purchaseOrders = $this->vendorPortalService->getVendorPurchaseOrders($vendor, $status);

        return view('vendor.purchase-orders', compact('vendor', 'purchaseOrders', 'status'));
    }

    public function showPurchaseOrder(Request $request, PurchaseOrder $po)
    {
        $vendor = $request->attributes->get('vendor');

        if ($po->vendor_id !== $vendor->id) {
            abort(403);
        }

        $po->load(['lines.sku.variant.product.design', 'company']);

        return view('vendor.purchase-order-show', compact('vendor', 'po'));
    }

    public function confirmOrder(Request $request, PurchaseOrder $po)
    {
        $vendor = $request->attributes->get('vendor');

        try {
            $this->vendorPortalService->confirmPurchaseOrder($vendor, $po);
            return redirect()->route('vendor.purchase-orders.show', $po)
                ->with('success', 'Purchase order confirmed successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function updateDelivery(Request $request, PurchaseOrder $po)
    {
        $vendor = $request->attributes->get('vendor');

        $request->validate([
            'expected_delivery_date' => 'required|date|after_or_equal:today',
        ]);

        try {
            $this->vendorPortalService->updateDeliveryDate(
                $vendor,
                $po,
                Carbon::parse($request->expected_delivery_date)
            );

            return redirect()->route('vendor.purchase-orders.show', $po)
                ->with('success', 'Delivery date updated successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function logout()
    {
        session()->forget('vendor_portal_token');

        return redirect()->route('vendor.login')
            ->with('success', 'You have been logged out.');
    }
}
