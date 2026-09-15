<x-admin-layout>
    <x-slot name="title">Returns</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Returns</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">Returns & RTO</h1>
        <a href="{{ route('admin.returns.create') }}"
           class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Initiate Return
        </a>
    </div>

    {{-- KPI Row --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Total Returns</p>
            <p class="mt-1 text-2xl font-semibold text-content">{{ $kpis['total'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Pending Inspection</p>
            <p class="mt-1 text-2xl font-semibold text-warning-500">{{ $kpis['pending_inspection'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Restocked</p>
            <p class="mt-1 text-2xl font-semibold text-success-500">{{ $kpis['restocked'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">RTO Rate</p>
            <p class="mt-1 text-2xl font-semibold {{ $kpis['rto_rate'] > 5 ? 'text-danger-500' : 'text-content' }}">{{ $kpis['rto_rate'] }}%</p>
        </div>
    </div>

    {{-- Status Filter Tabs --}}
    <div class="flex flex-wrap gap-2 mb-4">
        @php
            $statusTabs = [
                '' => 'All',
                'initiated' => 'Initiated',
                'in_transit' => 'In Transit',
                'received' => 'Received',
                'inspecting' => 'Inspecting',
                'restocked' => 'Restocked',
                'closed' => 'Closed',
            ];
        @endphp
        @foreach ($statusTabs as $val => $label)
            @php $isActive = ($val === '' && !request('status')) || request('status') === $val; @endphp
            <a href="{{ route('admin.returns.index', array_merge(request()->except('status', 'page'), $val ? ['status' => $val] : [])) }}"
               class="rounded-full px-4 py-1.5 text-sm font-medium transition
                   {{ $isActive ? 'bg-brand-500 text-white shadow-brand' : 'bg-surface-secondary text-content-secondary hover:bg-surface-tertiary' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Search & Filters --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-4 mb-4">
        <form method="GET" action="{{ route('admin.returns.index') }}" class="flex flex-wrap items-end gap-4">
            @if (request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            <div class="flex-1 min-w-[200px]">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Return ID, tracking, order..."
                       class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">
            </div>
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Return Type</label>
                <select name="return_type"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    <option value="">All</option>
                    @foreach (\App\Models\ReturnOrder::TYPE_LABELS as $val => $label)
                        <option value="{{ $val }}" @selected(request('return_type') === $val)>{{ $label }}</option>
                    @endforeach
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
                <a href="{{ route('admin.returns.index') }}" class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">Clear</a>
            </div>
        </form>
    </div>

    {{-- Returns Table --}}
    <div class="overflow-x-auto rounded-xl border border-surface-border bg-white shadow-card">
        <table class="min-w-full divide-y divide-surface-border">
            <thead class="bg-surface-secondary">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Return #</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Order #</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Type</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Reason</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Initiated</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Received</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-border-light">
                @forelse ($returns as $return)
                    @php
                        $statusColors = [
                            'initiated' => 'bg-warning-50 text-warning-600',
                            'in_transit' => 'bg-brand-50 text-brand-500',
                            'received' => 'bg-brand-50 text-brand-600',
                            'inspecting' => 'bg-warning-50 text-warning-700',
                            'inspection_complete' => 'bg-brand-50 text-brand-700',
                            'restocked' => 'bg-success-50 text-success-500',
                            'rejected' => 'bg-danger-50 text-danger-500',
                            'disposed' => 'bg-danger-50 text-danger-600',
                            'claim_filed' => 'bg-warning-50 text-warning-600',
                            'claim_settled' => 'bg-success-50 text-success-600',
                            'closed' => 'bg-neutral-50 text-neutral-500',
                        ];
                        $typeColors = [
                            'customer_return' => 'bg-brand-50 text-brand-600',
                            'rto' => 'bg-danger-50 text-danger-600',
                            'exchange' => 'bg-warning-50 text-warning-600',
                            'replacement' => 'bg-success-50 text-success-600',
                        ];
                    @endphp
                    <tr class="hover:bg-surface-secondary/50 transition">
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('admin.returns.show', $return) }}" class="font-mono text-brand-500 hover:underline text-xs">
                                RTN-{{ str_pad($return->id, 5, '0', STR_PAD_LEFT) }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('admin.orders.show', $return->order_id) }}" class="font-mono text-brand-500 hover:underline text-xs">
                                {{ \Illuminate\Support\Str::limit($return->order->marketplace_order_id ?? '-', 18) }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $typeColors[$return->return_type] ?? 'bg-neutral-50 text-neutral-500' }}">
                                {{ $return->type_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$return->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                {{ $return->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-content-secondary">{{ $return->reason_label }}</td>
                        <td class="px-4 py-3 text-sm text-content-secondary">{{ $return->initiated_at?->format('d M Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-content-secondary">{{ $return->received_at?->format('d M Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.returns.show', $return) }}" class="text-content-muted hover:text-brand-500 transition" title="View">
                                <svg class="inline h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-sm text-content-muted">
                            No returns found. <a href="{{ route('admin.returns.create') }}" class="text-brand-500 hover:underline">Initiate a return.</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $returns->links() }}
    </div>
</x-admin-layout>
