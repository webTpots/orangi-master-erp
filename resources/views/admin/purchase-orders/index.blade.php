<x-admin-layout>
    <x-slot name="title">Purchase Orders</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Purchase Orders</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">Purchase Orders</h1>
        <a href="{{ route('admin.purchase-orders.create') }}" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create PO
        </a>
    </div>

    {{-- KPI Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-semibold text-content-secondary uppercase tracking-wide">Total POs</p>
            <p class="font-display text-2xl font-bold text-content mt-1">{{ $kpis['total'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-semibold text-content-secondary uppercase tracking-wide">Pending</p>
            <p class="font-display text-2xl font-bold text-warning-500 mt-1">{{ $kpis['pending'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-semibold text-content-secondary uppercase tracking-wide">Confirmed</p>
            <p class="font-display text-2xl font-bold text-success-500 mt-1">{{ $kpis['confirmed'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-semibold text-content-secondary uppercase tracking-wide">Short Supply</p>
            <p class="font-display text-2xl font-bold text-danger-500 mt-1">{{ $kpis['short'] }}</p>
        </div>
    </div>

    {{-- Status Filter Tabs --}}
    <div class="flex flex-wrap gap-2 mb-6">
        @php
            $statuses = ['' => 'All', 'draft' => 'Draft', 'sent' => 'Sent', 'confirmed' => 'Confirmed', 'partially_received' => 'Partial', 'received' => 'Received', 'cancelled' => 'Cancelled'];
        @endphp
        @foreach ($statuses as $val => $label)
            <a href="{{ route('admin.purchase-orders.index', array_merge(request()->except('status', 'page'), $val ? ['status' => $val] : [])) }}"
               class="rounded-lg px-3 py-1.5 text-sm font-semibold transition-colors
                   {{ request('status', '') === $val ? 'bg-brand-500 text-white' : 'bg-white border border-surface-border-light text-content-secondary hover:bg-surface-secondary' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-4 mb-6">
        <form method="GET" action="{{ route('admin.purchase-orders.index') }}" class="flex flex-wrap items-end gap-4">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <div class="flex-1 min-w-[180px]">
                <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="PO number or vendor..." class="input">
            </div>
            <div class="w-44">
                <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Vendor</label>
                <select name="vendor_id" class="input">
                    <option value="">All Vendors</option>
                    @foreach ($vendors as $v)
                        <option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="input">
            </div>
            <div class="w-36">
                <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="input">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold transition-colors">Filter</button>
                <a href="{{ route('admin.purchase-orders.index') }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold transition-colors">Clear</a>
            </div>
        </form>
    </div>

    {{-- PO Table --}}
    <div class="bg-white rounded-xl border border-surface-border-light shadow-card overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-surface-border-light bg-surface-secondary/50">
                    <th class="px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">PO #</th>
                    <th class="px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Vendor</th>
                    <th class="px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Date</th>
                    <th class="px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide text-right">Total</th>
                    <th class="px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide text-center">Status</th>
                    <th class="px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Created By</th>
                    <th class="px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchaseOrders as $po)
                    <tr class="border-b border-surface-border-light hover:bg-surface-secondary/50 transition-colors">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.purchase-orders.show', $po) }}" class="text-sm font-medium text-brand-500 hover:underline font-mono">
                                {{ $po->po_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-content">{{ $po->vendor->name }}</td>
                        <td class="px-4 py-3 text-sm text-content-secondary">{{ $po->order_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-content text-right">{{ number_format($po->total_amount, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $colorMap = [
                                    'draft' => 'bg-neutral-50 text-neutral-500',
                                    'sent' => 'bg-warning-50 text-warning-500',
                                    'partially_confirmed' => 'bg-warning-50 text-warning-600',
                                    'confirmed' => 'bg-success-50 text-success-500',
                                    'partially_received' => 'bg-brand-50 text-brand-500',
                                    'received' => 'bg-success-50 text-success-600',
                                    'closed' => 'bg-neutral-50 text-neutral-500',
                                    'cancelled' => 'bg-danger-50 text-danger-500',
                                ];
                            @endphp
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-md {{ $colorMap[$po->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                {{ $po->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-content-secondary">{{ $po->creator->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.purchase-orders.show', $po) }}" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-content-muted hover:text-brand-500 hover:bg-brand-50 transition-colors" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-12 h-12 rounded-full bg-surface-secondary flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <p class="text-sm text-content-secondary">No purchase orders found</p>
                                <a href="{{ route('admin.purchase-orders.create') }}" class="text-sm text-brand-500 hover:underline mt-1">Create your first PO</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($purchaseOrders->hasPages())
        <div class="mt-4">
            {{ $purchaseOrders->links() }}
        </div>
    @endif
</x-admin-layout>
