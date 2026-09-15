<x-vendor-layout :title="'Dashboard'">
    {{-- Welcome --}}
    <div class="mb-6">
        <h1 class="text-lg font-display font-semibold text-content">Welcome, {{ $vendor->name }}</h1>
        <p class="text-sm text-content-secondary mt-0.5">Here's an overview of your account</p>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- Active POs --}}
        <div class="bg-white rounded-xl border border-surface-border p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-brand-500/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4.5 h-4.5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-content-secondary">Active POs</p>
                    <p class="text-lg font-semibold text-content">{{ $dashboardData['active_pos'] }}</p>
                </div>
            </div>
        </div>

        {{-- Pending Deliveries --}}
        <div class="bg-white rounded-xl border border-surface-border p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-warning-500/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4.5 h-4.5 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                </div>
                <div>
                    <p class="text-xs text-content-secondary">Pending Deliveries</p>
                    <p class="text-lg font-semibold text-content">{{ $dashboardData['pending_deliveries'] }}</p>
                </div>
            </div>
        </div>

        {{-- This Month's Orders --}}
        <div class="bg-white rounded-xl border border-surface-border p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-success-500/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4.5 h-4.5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-content-secondary">This Month</p>
                    <p class="text-lg font-semibold text-content">{{ $dashboardData['this_month_orders'] }}</p>
                </div>
            </div>
        </div>

        {{-- Performance Score --}}
        <div class="bg-white rounded-xl border border-surface-border p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-info-500/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4.5 h-4.5 text-info-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-content-secondary">Performance</p>
                    <p class="text-lg font-semibold text-content">{{ $dashboardData['performance_score'] }}%</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Purchase Orders --}}
    <div class="bg-white rounded-xl border border-surface-border">
        <div class="px-5 py-4 border-b border-surface-border flex items-center justify-between">
            <h2 class="text-sm font-semibold text-content">Recent Purchase Orders</h2>
            <a href="{{ route('vendor.purchase-orders') }}" class="text-xs text-brand-600 hover:text-brand-700 font-medium">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-surface-border-light">
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">PO Number</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Date</th>
                        <th class="text-right px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Amount</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Status</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Delivery</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border-light">
                    @forelse($dashboardData['recent_pos'] as $po)
                        <tr class="hover:bg-surface-secondary/50 transition-colors">
                            <td class="px-5 py-3">
                                <a href="{{ route('vendor.purchase-orders.show', $po) }}" class="text-brand-600 hover:text-brand-700 font-medium">{{ $po->po_number }}</a>
                            </td>
                            <td class="px-5 py-3 text-content-secondary">{{ $po->order_date->format('d M Y') }}</td>
                            <td class="px-5 py-3 text-right font-medium">{{ number_format($po->total_amount, 2) }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-{{ $po->status_color }}-50 text-{{ $po->status_color }}-600">
                                    {{ $po->status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-content-secondary">{{ $po->expected_delivery_date?->format('d M Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-content-muted text-sm">No purchase orders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-vendor-layout>
