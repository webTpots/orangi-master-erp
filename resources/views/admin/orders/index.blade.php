<x-admin-layout>
    <x-slot name="title">Orders</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Orders</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">Order Management</h1>
        <a href="{{ route('admin.labels.upload') }}"
           class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Import Labels
        </a>
    </div>

            {{-- KPI Row --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-7 mb-6">
                <div class="bg-white rounded-xl border border-surface-border-light p-4">
                    <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Total</p>
                    <p class="mt-1 text-2xl font-semibold text-content">{{ $kpis['total'] }}</p>
                </div>
                <div class="bg-white rounded-xl border border-surface-border-light p-4">
                    <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">New</p>
                    <p class="mt-1 text-2xl font-semibold text-brand-500">{{ $kpis['new'] }}</p>
                </div>
                <div class="bg-white rounded-xl border border-surface-border-light p-4">
                    <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Ready to Ship</p>
                    <p class="mt-1 text-2xl font-semibold text-warning-500">{{ $kpis['ready_to_ship'] }}</p>
                </div>
                <div class="bg-white rounded-xl border border-surface-border-light p-4">
                    <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Shipped</p>
                    <p class="mt-1 text-2xl font-semibold text-brand-600">{{ $kpis['shipped'] }}</p>
                </div>
                <div class="bg-white rounded-xl border border-surface-border-light p-4">
                    <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Delivered</p>
                    <p class="mt-1 text-2xl font-semibold text-success-500">{{ $kpis['delivered'] }}</p>
                </div>
                <div class="bg-white rounded-xl border border-surface-border-light p-4">
                    <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Returns</p>
                    <p class="mt-1 text-2xl font-semibold text-danger-500">{{ $kpis['returns'] }}</p>
                </div>
                <div class="bg-white rounded-xl border border-surface-border-light p-4">
                    <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">SLA Risk</p>
                    <p class="mt-1 text-2xl font-semibold {{ $kpis['sla_risk'] > 0 ? 'text-danger-500' : 'text-content-muted' }}">{{ $kpis['sla_risk'] }}</p>
                </div>
            </div>

            {{-- Status Filter Tabs --}}
            <div class="flex flex-wrap gap-2">
                @php
                    $statusTabs = [
                        '' => 'All',
                        'new' => 'New',
                        'accepted' => 'Accepted',
                        'label_ready' => 'Label Ready',
                        'packed' => 'Packed',
                        'ready_to_ship' => 'Ready to Ship',
                        'handed_over' => 'Shipped',
                        'delivered' => 'Delivered',
                        'return' => 'Return/RTO',
                    ];
                @endphp
                @foreach ($statusTabs as $val => $label)
                    @php
                        $isActive = ($val === '' && !request('status')) || request('status') === $val
                            || ($val === 'handed_over' && in_array(request('status'), ['handed_over', 'in_transit']))
                            || ($val === 'return' && in_array(request('status'), ['return', 'rto']));
                    @endphp
                    <a href="{{ route('admin.orders.index', array_merge(request()->except('status', 'page'), $val ? ['status' => $val] : [])) }}"
                       class="rounded-full px-4 py-1.5 text-sm font-medium transition
                           {{ $isActive ? 'bg-brand-500 text-white shadow-brand' : 'bg-surface-secondary text-content-secondary hover:bg-surface-tertiary' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Courier Filter Chips --}}
            @if ($couriers->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-medium text-content-secondary uppercase tracking-wider">Courier:</span>
                    <a href="{{ route('admin.orders.index', request()->except('courier', 'page')) }}"
                       class="rounded-full px-3 py-1 text-xs font-medium transition {{ !request('courier') ? 'bg-brand-100 text-brand-700' : 'bg-surface-secondary text-content-secondary hover:bg-surface-tertiary' }}">
                        All
                    </a>
                    @foreach ($couriers as $c)
                        <a href="{{ route('admin.orders.index', array_merge(request()->except('courier', 'page'), ['courier' => $c])) }}"
                           class="rounded-full px-3 py-1 text-xs font-medium transition {{ request('courier') === $c ? 'bg-brand-100 text-brand-700' : 'bg-surface-secondary text-content-secondary hover:bg-surface-tertiary' }}">
                            {{ $c }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Search & Filters --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-wrap items-end gap-4">
                    {{-- Preserve existing filters --}}
                    @if (request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                    @if (request('courier'))
                        <input type="hidden" name="courier" value="{{ request('courier') }}">
                    @endif

                    <div class="flex-1 min-w-[200px]">
                        <label class="mb-1 block text-xs font-medium text-content-secondary">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Order ID, customer, AWB, SKU..."
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">
                    </div>
                    <div class="w-40">
                        <label class="mb-1 block text-xs font-medium text-content-secondary">Marketplace</label>
                        <select name="marketplace_id"
                                class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            <option value="">All</option>
                            @foreach ($marketplaces as $mp)
                                <option value="{{ $mp->id }}" @selected(request('marketplace_id') == $mp->id)>{{ $mp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-32">
                        <label class="mb-1 block text-xs font-medium text-content-secondary">Payment</label>
                        <select name="payment_type"
                                class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            <option value="">All</option>
                            <option value="prepaid" @selected(request('payment_type') === 'prepaid')>Prepaid</option>
                            <option value="cod" @selected(request('payment_type') === 'cod')>COD</option>
                        </select>
                    </div>
                    <div class="w-36">
                        <label class="mb-1 block text-xs font-medium text-content-secondary">From Date</label>
                        <input type="date" name="from_date" value="{{ request('from_date') }}"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    </div>
                    <div class="w-36">
                        <label class="mb-1 block text-xs font-medium text-content-secondary">To Date</label>
                        <input type="date" name="to_date" value="{{ request('to_date') }}"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">Filter</button>
                        <a href="{{ route('admin.orders.index') }}" class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">Clear</a>
                    </div>
                </form>
            </div>

            {{-- Bulk Actions --}}
            <form method="POST" action="{{ route('admin.orders.bulk-status') }}" id="bulkForm">
                @csrf
                <div class="flex items-center gap-3 rounded-xl border border-surface-border bg-white px-4 py-3 shadow-card" id="bulkBar" style="display: none;">
                    <span class="text-sm text-content-secondary"><span id="selectedCount">0</span> selected</span>
                    <select name="status" class="rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        <option value="">Change status to...</option>
                        @foreach (\App\Models\Order::STATUS_LABELS as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">Apply</button>
                </div>

                {{-- Orders Table --}}
                <div class="mt-4 overflow-x-auto rounded-xl border border-surface-border bg-white shadow-card">
                    <table class="min-w-full divide-y divide-surface-border">
                        <thead class="bg-surface-secondary">
                            <tr>
                                <th class="px-3 py-3 text-left">
                                    <input type="checkbox" id="selectAll" class="rounded border-surface-border text-brand-500 focus:ring-brand-400">
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Order ID</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Product</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Size</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Qty</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Customer</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">City</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Payment</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Courier</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">AWB</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Stock</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                                <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border-light">
                            @forelse ($orders as $order)
                                @php
                                    $firstSub = $order->subOrders->first();
                                    $statusColors = [
                                        'new' => 'bg-brand-50 text-brand-600',
                                        'accepted' => 'bg-brand-50 text-brand-700',
                                        'label_ready' => 'bg-warning-50 text-warning-600',
                                        'picking' => 'bg-warning-50 text-warning-700',
                                        'packed' => 'bg-success-50 text-success-500',
                                        'ready_to_ship' => 'bg-success-50 text-success-600',
                                        'scanned' => 'bg-success-50 text-success-700',
                                        'handed_over' => 'bg-brand-50 text-brand-600',
                                        'in_transit' => 'bg-brand-50 text-brand-500',
                                        'delivered' => 'bg-success-50 text-success-600',
                                        'cancelled' => 'bg-danger-50 text-danger-500',
                                        'return' => 'bg-danger-50 text-danger-600',
                                        'rto' => 'bg-danger-50 text-danger-700',
                                    ];
                                    $stockColors = [
                                        'pending' => 'bg-neutral-50 text-neutral-500',
                                        'in_stock' => 'bg-success-50 text-success-500',
                                        'partial' => 'bg-warning-50 text-warning-500',
                                        'out_of_stock' => 'bg-danger-50 text-danger-500',
                                        'not_mapped' => 'bg-neutral-50 text-neutral-400',
                                    ];
                                @endphp
                                <tr class="hover:bg-surface-secondary/50 transition">
                                    <td class="px-3 py-3">
                                        <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="order-checkbox rounded border-surface-border text-brand-500 focus:ring-brand-400">
                                    </td>
                                    <td class="px-3 py-3 text-sm">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-brand-500 hover:underline text-xs">
                                            {{ \Illuminate\Support\Str::limit($order->marketplace_order_id, 18) }}
                                        </a>
                                        <div class="text-xs text-content-muted">{{ $order->order_date->format('d M') }}</div>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-content max-w-[200px] truncate">
                                        {{ $firstSub?->product_name ?? $firstSub?->marketplace_sku ?? '-' }}
                                    </td>
                                    <td class="px-3 py-3 text-center text-sm text-content">{{ $firstSub?->size ?? '-' }}</td>
                                    <td class="px-3 py-3 text-center text-sm text-content">{{ $order->subOrders->sum('quantity') ?: 1 }}</td>
                                    <td class="px-3 py-3 text-sm text-content">{{ \Illuminate\Support\Str::limit($order->customer_name, 20) ?? '-' }}</td>
                                    <td class="px-3 py-3 text-sm text-content-secondary">{{ $order->customer_city ?? '-' }}</td>
                                    <td class="px-3 py-3 text-center">
                                        @if ($order->payment_type)
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $order->payment_type === 'cod' ? 'bg-warning-50 text-warning-600' : 'bg-success-50 text-success-500' }}">
                                                {{ strtoupper($order->payment_type) }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-sm text-content-secondary">{{ $order->courier_partner ?? '-' }}</td>
                                    <td class="px-3 py-3 text-xs font-mono text-content-muted">{{ $order->awb_number ? \Illuminate\Support\Str::limit($order->awb_number, 14) : '-' }}</td>
                                    <td class="px-3 py-3 text-center">
                                        @if ($firstSub)
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $stockColors[$firstSub->stock_status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                                {{ $firstSub->stock_status_label }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$order->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                            {{ $order->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="text-content-muted hover:text-brand-500 transition" title="View">
                                            <svg class="inline h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="px-4 py-12 text-center text-sm text-content-muted">
                                        No orders found. <a href="{{ route('admin.labels.upload') }}" class="text-brand-500 hover:underline">Import labels to create orders.</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.order-checkbox');
            const bulkBar = document.getElementById('bulkBar');
            const selectedCount = document.getElementById('selectedCount');

            function updateBulkBar() {
                const checked = document.querySelectorAll('.order-checkbox:checked').length;
                selectedCount.textContent = checked;
                bulkBar.style.display = checked > 0 ? 'flex' : 'none';
            }

            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateBulkBar();
            });

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateBulkBar);
            });
        });
    </script>
    @endpush
</x-admin-layout>
