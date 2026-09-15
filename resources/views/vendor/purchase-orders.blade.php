<x-vendor-layout :title="'Purchase Orders'">
    <div class="mb-6">
        <h1 class="text-lg font-display font-semibold text-content">Purchase Orders</h1>
        <p class="text-sm text-content-secondary mt-0.5">View and manage your purchase orders</p>
    </div>

    {{-- Status Filter Tabs --}}
    <div class="flex items-center gap-1 mb-4 border-b border-surface-border">
        <a href="{{ route('vendor.purchase-orders') }}" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ !$status ? 'text-brand-600 border-brand-500' : 'text-content-secondary border-transparent hover:text-content' }}">All</a>
        @foreach(['sent' => 'Pending', 'confirmed' => 'Confirmed', 'partially_received' => 'Partial', 'received' => 'Received', 'cancelled' => 'Cancelled'] as $key => $label)
            <a href="{{ route('vendor.purchase-orders', ['status' => $key]) }}" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $status === $key ? 'text-brand-600 border-brand-500' : 'text-content-secondary border-transparent hover:text-content' }}">{{ $label }}</a>
        @endforeach
    </div>

    {{-- PO Table --}}
    <div class="bg-white rounded-xl border border-surface-border">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-surface-border-light">
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">PO Number</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Date</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Items</th>
                        <th class="text-right px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Total</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Status</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Delivery</th>
                        <th class="text-right px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border-light">
                    @forelse($purchaseOrders as $po)
                        <tr class="hover:bg-surface-secondary/50 transition-colors">
                            <td class="px-5 py-3">
                                <a href="{{ route('vendor.purchase-orders.show', $po) }}" class="text-brand-600 hover:text-brand-700 font-medium">{{ $po->po_number }}</a>
                            </td>
                            <td class="px-5 py-3 text-content-secondary">{{ $po->order_date->format('d M Y') }}</td>
                            <td class="px-5 py-3 text-content-secondary">{{ $po->lines->count() }} items</td>
                            <td class="px-5 py-3 text-right font-medium">{{ number_format($po->total_amount, 2) }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-{{ $po->status_color }}-50 text-{{ $po->status_color }}-600">
                                    {{ $po->status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-content-secondary">{{ $po->expected_delivery_date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('vendor.purchase-orders.show', $po) }}" class="text-xs text-brand-600 hover:text-brand-700 font-medium">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-content-muted text-sm">No purchase orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchaseOrders->hasPages())
            <div class="px-5 py-3 border-t border-surface-border-light">
                {{ $purchaseOrders->links() }}
            </div>
        @endif
    </div>
</x-vendor-layout>
