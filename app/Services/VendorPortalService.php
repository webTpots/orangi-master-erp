<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\VendorPortalToken;
use Carbon\Carbon;
use Illuminate\Support\Str;

class VendorPortalService
{
    /**
     * Generate a portal token for a vendor.
     */
    public function generatePortalToken(Vendor $vendor, string $email): VendorPortalToken
    {
        return VendorPortalToken::create([
            'vendor_id'  => $vendor->id,
            'token'      => Str::random(64),
            'email'      => $email,
            'expires_at' => now()->addMonths(6),
            'is_active'  => true,
        ]);
    }

    /**
     * Authenticate a vendor using a portal token.
     */
    public function authenticateVendor(string $token): ?Vendor
    {
        $portalToken = VendorPortalToken::where('token', $token)
            ->valid()
            ->with('vendor')
            ->first();

        if (! $portalToken) {
            return null;
        }

        $portalToken->markUsed();

        return $portalToken->vendor;
    }

    /**
     * Get vendor dashboard data.
     */
    public function getVendorDashboard(Vendor $vendor): array
    {
        $activePOs = PurchaseOrder::where('vendor_id', $vendor->id)
            ->active()
            ->count();

        $pendingDeliveries = PurchaseOrder::where('vendor_id', $vendor->id)
            ->whereIn('status', ['sent', 'confirmed', 'partially_confirmed', 'partially_received'])
            ->count();

        $thisMonthOrders = PurchaseOrder::where('vendor_id', $vendor->id)
            ->whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->count();

        $thisMonthValue = PurchaseOrder::where('vendor_id', $vendor->id)
            ->whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->sum('total_amount');

        $recentPOs = PurchaseOrder::where('vendor_id', $vendor->id)
            ->with('company')
            ->orderByDesc('order_date')
            ->limit(10)
            ->get();

        // Performance: on-time delivery rate (received POs where actual <= expected)
        $totalReceived = PurchaseOrder::where('vendor_id', $vendor->id)
            ->where('status', 'received')
            ->count();

        $onTimeReceived = PurchaseOrder::where('vendor_id', $vendor->id)
            ->where('status', 'received')
            ->whereNotNull('expected_delivery_date')
            ->whereColumn('updated_at', '<=', 'expected_delivery_date')
            ->count();

        $performanceScore = $totalReceived > 0
            ? round(($onTimeReceived / $totalReceived) * 100, 1)
            : 100;

        return [
            'active_pos'         => $activePOs,
            'pending_deliveries' => $pendingDeliveries,
            'this_month_orders'  => $thisMonthOrders,
            'this_month_value'   => $thisMonthValue,
            'performance_score'  => $performanceScore,
            'recent_pos'         => $recentPOs,
        ];
    }

    /**
     * Get vendor's purchase orders.
     */
    public function getVendorPurchaseOrders(Vendor $vendor, ?string $status = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = PurchaseOrder::where('vendor_id', $vendor->id)
            ->with(['lines.sku.variant.product.design']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(20);
    }

    /**
     * Vendor confirms a purchase order.
     */
    public function confirmPurchaseOrder(Vendor $vendor, PurchaseOrder $po): void
    {
        if ($po->vendor_id !== $vendor->id) {
            throw new \RuntimeException('This PO does not belong to your account.');
        }

        if (! in_array($po->status, ['sent'])) {
            throw new \RuntimeException('This PO cannot be confirmed in its current state.');
        }

        // Confirm all lines at requested quantity
        foreach ($po->lines as $line) {
            $line->update([
                'quantity_confirmed' => $line->quantity_requested,
                'status'             => 'confirmed',
            ]);
        }

        $po->update(['status' => 'confirmed']);
    }

    /**
     * Vendor updates expected delivery date.
     */
    public function updateDeliveryDate(Vendor $vendor, PurchaseOrder $po, Carbon $date): void
    {
        if ($po->vendor_id !== $vendor->id) {
            throw new \RuntimeException('This PO does not belong to your account.');
        }

        if (in_array($po->status, ['cancelled', 'closed', 'received'])) {
            throw new \RuntimeException('This PO cannot be updated in its current state.');
        }

        $po->update(['expected_delivery_date' => $date]);
    }
}
