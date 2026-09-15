<x-admin-layout>
    <x-slot name="title">Shipments</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Shipments</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">Shipment Management</h1>
    </div>

    {{-- KPI Row --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Total Shipments</p>
            <p class="mt-1 text-2xl font-semibold text-content">{{ $kpis['total'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">In Transit</p>
            <p class="mt-1 text-2xl font-semibold text-brand-500">{{ $kpis['in_transit'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Delivered Today</p>
            <p class="mt-1 text-2xl font-semibold text-success-500">{{ $kpis['delivered_today'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">RTO Rate</p>
            <p class="mt-1 text-2xl font-semibold text-danger-500">{{ $kpis['rto_rate'] }}</p>
        </div>
    </div>

    {{-- Status Filter Tabs --}}
    <div class="flex flex-wrap gap-2 mb-4">
        @php
            $statusTabs = [
                '' => 'All',
                'created' => 'Created',
                'picked_up' => 'Picked Up',
                'in_transit' => 'In Transit',
                'out_for_delivery' => 'Out for Delivery',
                'delivered' => 'Delivered',
                'rto_initiated' => 'RTO',
                'lost' => 'Lost/Damaged',
            ];
        @endphp
        @foreach ($statusTabs as $val => $label)
            @php
                $isActive = ($val === '' && !request('status')) || request('status') === $val
                    || ($val === 'rto_initiated' && in_array(request('status'), ['rto_initiated', 'rto_in_transit', 'rto_delivered']))
                    || ($val === 'lost' && in_array(request('status'), ['lost', 'damaged']));
            @endphp
            <a href="{{ route('admin.shipments.index', array_merge(request()->except('status', 'page'), $val ? ['status' => $val] : [])) }}"
               class="rounded-full px-4 py-1.5 text-sm font-medium transition
                   {{ $isActive ? 'bg-brand-500 text-white shadow-brand' : 'bg-surface-secondary text-content-secondary hover:bg-surface-tertiary' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Courier Filter Chips --}}
    @if ($couriers->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <span class="text-xs font-medium text-content-secondary uppercase tracking-wider">Courier:</span>
            <a href="{{ route('admin.shipments.index', request()->except('courier', 'page')) }}"
               class="rounded-full px-3 py-1 text-xs font-medium transition {{ !request('courier') ? 'bg-brand-100 text-brand-700' : 'bg-surface-secondary text-content-secondary hover:bg-surface-tertiary' }}">
                All
            </a>
            @foreach ($couriers as $c)
                <a href="{{ route('admin.shipments.index', array_merge(request()->except('courier', 'page'), ['courier' => $c])) }}"
                   class="rounded-full px-3 py-1 text-xs font-medium transition {{ request('courier') === $c ? 'bg-brand-100 text-brand-700' : 'bg-surface-secondary text-content-secondary hover:bg-surface-tertiary' }}">
                    {{ $c }}
                </a>
            @endforeach
        </div>
    @endif

    {{-- Search & Date Filters --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-4 mb-4">
        <form method="GET" action="{{ route('admin.shipments.index') }}" class="flex flex-wrap items-end gap-4">
            @if (request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            @if (request('courier'))
                <input type="hidden" name="courier" value="{{ request('courier') }}">
            @endif

            <div class="flex-1 min-w-[200px]">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Tracking #, AWB, order ID, customer..."
                       class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">
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
                <a href="{{ route('admin.shipments.index') }}" class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">Clear</a>
            </div>
        </form>
    </div>

    {{-- Bulk Actions --}}
    <form method="POST" action="{{ route('admin.shipments.bulk-dispatch') }}" id="bulkForm">
        @csrf
        <div class="flex items-center gap-3 rounded-xl border border-surface-border bg-white px-4 py-3 shadow-card mb-4" id="bulkBar" style="display: none;">
            <span class="text-sm text-content-secondary"><span id="selectedCount">0</span> selected</span>
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">Dispatch Selected</button>
        </div>

        {{-- Shipments Table --}}
        <div class="overflow-x-auto rounded-xl border border-surface-border bg-white shadow-card">
            <table class="min-w-full divide-y divide-surface-border">
                <thead class="bg-surface-secondary">
                    <tr>
                        <th class="px-3 py-3 text-left">
                            <input type="checkbox" id="selectAll" class="rounded border-surface-border text-brand-500 focus:ring-brand-400">
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Tracking #</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Order #</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Courier</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Shipped</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Delivered</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border-light">
                    @forelse ($shipments as $shipment)
                        @php
                            $statusColors = [
                                'created'          => 'bg-neutral-50 text-neutral-500',
                                'picked_up'        => 'bg-warning-50 text-warning-600',
                                'in_transit'       => 'bg-brand-50 text-brand-500',
                                'out_for_delivery' => 'bg-brand-50 text-brand-600',
                                'delivered'        => 'bg-success-50 text-success-600',
                                'rto_initiated'    => 'bg-danger-50 text-danger-500',
                                'rto_in_transit'   => 'bg-danger-50 text-danger-600',
                                'rto_delivered'    => 'bg-danger-50 text-danger-700',
                                'lost'             => 'bg-danger-50 text-danger-500',
                                'damaged'          => 'bg-warning-50 text-warning-700',
                            ];
                        @endphp
                        <tr class="hover:bg-surface-secondary/50 transition">
                            <td class="px-3 py-3">
                                <input type="checkbox" name="shipment_ids[]" value="{{ $shipment->id }}" class="shipment-checkbox rounded border-surface-border text-brand-500 focus:ring-brand-400">
                            </td>
                            <td class="px-3 py-3 text-sm">
                                <a href="{{ route('admin.shipments.show', $shipment) }}" class="font-mono text-brand-500 hover:underline text-xs">
                                    {{ \Illuminate\Support\Str::limit($shipment->tracking_number, 20) }}
                                </a>
                            </td>
                            <td class="px-3 py-3 text-sm">
                                <a href="{{ route('admin.orders.show', $shipment->order_id) }}" class="font-mono text-content-secondary hover:text-brand-500 text-xs">
                                    {{ \Illuminate\Support\Str::limit($shipment->order?->marketplace_order_id, 18) }}
                                </a>
                            </td>
                            <td class="px-3 py-3 text-sm text-content">{{ $shipment->courier_name }}</td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$shipment->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                    {{ $shipment->status_label }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-sm text-content-secondary">{{ $shipment->shipped_at?->format('d M Y') ?? '-' }}</td>
                            <td class="px-3 py-3 text-sm text-content-secondary">{{ $shipment->delivered_at?->format('d M Y') ?? '-' }}</td>
                            <td class="px-3 py-3 text-right">
                                <a href="{{ route('admin.shipments.show', $shipment) }}" class="text-content-muted hover:text-brand-500 transition" title="View">
                                    <svg class="inline h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-content-muted">
                                No shipments found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <div class="mt-4">
        {{ $shipments->links() }}
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.shipment-checkbox');
            const bulkBar = document.getElementById('bulkBar');
            const selectedCount = document.getElementById('selectedCount');

            function updateBulkBar() {
                const checked = document.querySelectorAll('.shipment-checkbox:checked').length;
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
