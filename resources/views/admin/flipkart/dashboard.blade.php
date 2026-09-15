<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: #2874F0;">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72l1.189-1.19A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72M6.75 18h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .414.336.75.75.75z"/></svg>
                </div>
                <h2 class="text-xl font-display font-semibold text-content">Flipkart Dashboard</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.flipkart.import-labels') }}"
                   class="rounded-lg bg-white border border-surface-border px-4 py-2 text-sm font-medium text-content hover:bg-surface-secondary transition inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    Import Labels
                </a>
                <a href="{{ route('admin.flipkart.import-settlement') }}"
                   class="rounded-lg text-white px-4 py-2 text-sm font-medium hover:opacity-90 transition inline-flex items-center gap-2" style="background-color: #2874F0;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Import Settlement
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- KPI Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Total Orders --}}
                <div class="rounded-xl border border-surface-border bg-white p-5 shadow-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-content-muted uppercase tracking-wider">Total Orders</p>
                            <p class="mt-2 text-2xl font-display font-bold text-content">{{ number_format($kpis['total_orders']) }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: rgba(40, 116, 240, 0.1);">
                            <svg class="w-5 h-5" style="color: #2874F0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </div>
                    </div>
                </div>

                {{-- Pending Dispatch --}}
                <div class="rounded-xl border border-surface-border bg-white p-5 shadow-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-content-muted uppercase tracking-wider">Pending Dispatch</p>
                            <p class="mt-2 text-2xl font-display font-bold text-content">{{ number_format($kpis['pending_dispatch']) }}</p>
                            <p class="mt-1 text-xs text-content-muted">Cutoff: 12:00 PM</p>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-warning-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-warning-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                </div>

                {{-- Returns --}}
                <div class="rounded-xl border border-surface-border bg-white p-5 shadow-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-content-muted uppercase tracking-wider">Returns / RTO</p>
                            <p class="mt-2 text-2xl font-display font-bold text-content">{{ number_format($kpis['returns']) }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-danger-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-danger-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                        </div>
                    </div>
                </div>

                {{-- Settlement Value --}}
                <div class="rounded-xl border border-surface-border bg-white p-5 shadow-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-content-muted uppercase tracking-wider">Settlement Value</p>
                            <p class="mt-2 text-2xl font-display font-bold text-content">{{ number_format($kpis['settlement_value'], 2) }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-success-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            @if ($unmappedCount > 0)
                <div class="rounded-lg border border-warning-200 bg-warning-50 p-4 flex items-center gap-3">
                    <svg class="w-5 h-5 text-warning-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-warning-800">{{ $unmappedCount }} unmapped Flipkart SKU(s)</p>
                        <p class="text-xs text-warning-600 mt-0.5">Some Flipkart SKUs are not mapped to internal SKUs. Map them for accurate reporting.</p>
                    </div>
                    <a href="{{ route('admin.flipkart.sku-mapping') }}" class="text-sm font-medium text-warning-700 hover:text-warning-900 transition whitespace-nowrap">Manage Mappings</a>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Recent Orders --}}
                <div class="lg:col-span-2 rounded-xl border border-surface-border bg-white shadow-card">
                    <div class="px-6 py-4 border-b border-surface-border flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-content">Recent Flipkart Orders</h3>
                        <a href="{{ route('admin.orders.index', ['marketplace' => 'flipkart']) }}" class="text-xs font-medium hover:underline" style="color: #2874F0;">View all</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-surface-border bg-surface-secondary/50">
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-content-muted uppercase tracking-wider">Order ID</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-content-muted uppercase tracking-wider">SKU</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-content-muted uppercase tracking-wider">Customer</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-content-muted uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium text-content-muted uppercase tracking-wider">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-border">
                                @forelse ($recentOrders as $order)
                                    <tr class="hover:bg-surface-secondary/30 transition">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="font-medium hover:underline" style="color: #2874F0;">
                                                {{ $order->marketplace_order_id }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-content-secondary">{{ $order->sku_code ?? '-' }}</td>
                                        <td class="px-4 py-3 text-content-secondary">{{ $order->customer_name ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                                {{ $order->status === 'dispatched' ? 'bg-success-50 text-success-700' : '' }}
                                                {{ $order->status === 'new' ? 'bg-blue-50 text-blue-700' : '' }}
                                                {{ in_array($order->status, ['rto', 'return_received']) ? 'bg-danger-50 text-danger-700' : '' }}
                                                {{ in_array($order->status, ['accepted', 'label_ready', 'picking']) ? 'bg-warning-50 text-warning-700' : '' }}
                                            ">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-content">{{ number_format($order->total_amount ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-content-muted text-sm">No Flipkart orders yet. Import labels to get started.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Settlement Summary & Quick Actions --}}
                <div class="space-y-6">
                    {{-- Recent Settlements --}}
                    <div class="rounded-xl border border-surface-border bg-white shadow-card">
                        <div class="px-6 py-4 border-b border-surface-border">
                            <h3 class="text-sm font-semibold text-content">Recent Settlements</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            @forelse ($recentSettlements as $settlement)
                                <a href="{{ route('admin.settlements.show', $settlement) }}" class="block rounded-lg border border-surface-border p-3 hover:bg-surface-secondary/30 transition">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-medium text-content">{{ $settlement->settlement_reference }}</span>
                                        <span class="text-xs font-medium {{ $settlement->status === 'reconciled' ? 'text-success-600' : 'text-warning-600' }}">
                                            {{ ucfirst(str_replace('_', ' ', $settlement->status)) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between mt-1">
                                        <span class="text-xs text-content-muted">{{ $settlement->settlement_date }}</span>
                                        <span class="text-sm font-semibold text-content">{{ number_format($settlement->net_payable ?? 0, 2) }}</span>
                                    </div>
                                </a>
                            @empty
                                <p class="text-sm text-content-muted text-center py-4">No settlements imported yet.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="rounded-xl border border-surface-border bg-white shadow-card">
                        <div class="px-6 py-4 border-b border-surface-border">
                            <h3 class="text-sm font-semibold text-content">Quick Actions</h3>
                        </div>
                        <div class="p-4 space-y-2">
                            <a href="{{ route('admin.flipkart.import-labels') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-content hover:bg-surface-secondary transition">
                                <svg class="w-4 h-4 text-content-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                Import Labels
                            </a>
                            <a href="{{ route('admin.flipkart.import-settlement') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-content hover:bg-surface-secondary transition">
                                <svg class="w-4 h-4 text-content-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                Import Settlement
                            </a>
                            <a href="{{ route('admin.flipkart.sku-mapping') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-content hover:bg-surface-secondary transition">
                                <svg class="w-4 h-4 text-content-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                                Manage SKU Mappings
                                @if ($unmappedCount > 0)
                                    <span class="ml-auto inline-flex items-center rounded-full bg-warning-100 px-2 py-0.5 text-xs font-medium text-warning-700">{{ $unmappedCount }}</span>
                                @endif
                            </a>
                            <a href="{{ route('admin.orders.index', ['marketplace' => 'flipkart']) }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-content hover:bg-surface-secondary transition">
                                <svg class="w-4 h-4 text-content-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                View All Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-admin-layout>
