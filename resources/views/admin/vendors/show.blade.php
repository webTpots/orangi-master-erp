<x-admin-layout>
    <x-slot name="title">{{ $vendor->name }}</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.vendors.index') }}" class="text-content-secondary hover:text-content transition-colors">Vendors</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">{{ $vendor->name }}</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <h1 class="font-display text-xl font-bold text-content">{{ $vendor->name }}</h1>
            @if($vendor->status === 'active')
                <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-success-50 text-success-500">Active</span>
            @else
                <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-neutral-50 text-neutral-500">Inactive</span>
            @endif
        </div>
        <a href="{{ route('admin.vendors.edit', $vendor) }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit
        </a>
    </div>

    {{-- Metrics --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-semibold text-content-secondary uppercase tracking-wide">Total POs</p>
            <p class="font-display text-2xl font-bold text-content mt-1">{{ $metrics['total_pos'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-semibold text-content-secondary uppercase tracking-wide">Active POs</p>
            <p class="font-display text-2xl font-bold text-brand-500 mt-1">{{ $metrics['active_pos'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-semibold text-content-secondary uppercase tracking-wide">Total Value</p>
            <p class="font-display text-2xl font-bold text-content mt-1">{{ number_format($metrics['total_value'], 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-semibold text-content-secondary uppercase tracking-wide">Products</p>
            <p class="font-display text-2xl font-bold text-content mt-1">{{ $metrics['products_count'] }}</p>
        </div>
    </div>

    <div class="space-y-6">
            {{-- Vendor Info --}}
            <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-5">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-content-secondary">Vendor Information</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs text-content-muted">Contact Person</p>
                        <p class="text-sm font-medium text-content">{{ $vendor->contact_person ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-content-muted">Phone</p>
                        <p class="text-sm font-medium text-content">{{ $vendor->phone ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-content-muted">Email</p>
                        <p class="text-sm font-medium text-content">{{ $vendor->email ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-content-muted">GSTIN</p>
                        <p class="text-sm font-medium text-content">{{ $vendor->gstin ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-content-muted">PAN</p>
                        <p class="text-sm font-medium text-content">{{ $vendor->pan ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-content-muted">Payment Terms</p>
                        <p class="text-sm font-medium text-content">{{ $vendor->payment_terms ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-content-muted">Lead Time</p>
                        <p class="text-sm font-medium text-content">{{ $vendor->lead_time_days ?? '-' }} days</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-content-muted">Address</p>
                        <p class="text-sm font-medium text-content">
                            {{ implode(', ', array_filter([$vendor->address, $vendor->city, $vendor->state, $vendor->pincode])) ?: '-' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Products Supplied --}}
            <div class="rounded-xl border border-surface-border bg-white shadow-card">
                <div class="border-b border-surface-border px-6 py-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Products Supplied</h3>
                </div>
                <table class="min-w-full divide-y divide-surface-border">
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">SKU Code</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Product</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Vendor Price</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">MOQ</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Lead Time</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Preferred</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border-light">
                        @forelse ($vendor->vendorProducts as $vp)
                            <tr class="hover:bg-surface-secondary/50 transition">
                                <td class="px-4 py-3 text-sm font-mono text-content">{{ $vp->sku->sku_code }}</td>
                                <td class="px-4 py-3 text-sm text-content-secondary">{{ $vp->sku->short_name }}</td>
                                <td class="px-4 py-3 text-right text-sm text-content">{{ number_format($vp->vendor_price, 2) }}</td>
                                <td class="px-4 py-3 text-center text-sm text-content-secondary">{{ $vp->moq ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-sm text-content-secondary">{{ $vp->lead_time_days ?? '-' }}d</td>
                                <td class="px-4 py-3 text-center">
                                    @if ($vp->is_preferred)
                                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-success-50 text-success-500">
                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                        </span>
                                    @else
                                        <span class="text-content-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-content-muted">No products linked to this vendor.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Purchase History --}}
            <div class="rounded-xl border border-surface-border bg-white shadow-card">
                <div class="border-b border-surface-border px-6 py-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Purchase History</h3>
                    <a href="{{ route('admin.purchase-orders.create') }}?vendor_id={{ $vendor->id }}"
                       class="text-sm font-medium text-brand-500 hover:text-brand-600 transition">
                        + New PO
                    </a>
                </div>
                <table class="min-w-full divide-y divide-surface-border">
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">PO #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Date</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Items</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border-light">
                        @forelse ($purchaseOrders as $po)
                            <tr class="hover:bg-surface-secondary/50 transition">
                                <td class="px-4 py-3 text-sm font-medium">
                                    <a href="{{ route('admin.purchase-orders.show', $po) }}" class="text-brand-500 hover:underline">{{ $po->po_number }}</a>
                                </td>
                                <td class="px-4 py-3 text-sm text-content-secondary">{{ $po->order_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-center text-sm text-content-secondary">{{ $po->lines->count() }}</td>
                                <td class="px-4 py-3 text-right text-sm text-content">{{ number_format($po->total_amount, 2) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                        bg-{{ $po->status_color }}-50 text-{{ $po->status_color }}-500">
                                        {{ $po->status_label }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-content-muted">No purchase orders yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

    </div>
</x-admin-layout>
