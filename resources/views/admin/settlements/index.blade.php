<x-admin-layout>
    <x-slot name="title">Settlements</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Settlements</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">Settlement Management</h1>
        <a href="{{ route('admin.settlements.upload') }}"
           class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Import Settlement
        </a>
    </div>

    {{-- KPI Row --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Total Settlements</p>
            <p class="mt-1 text-2xl font-semibold text-content">{{ $kpis['total_settlements'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Net Payable</p>
            <p class="mt-1 text-2xl font-semibold text-success-500">{{ Number::currency($kpis['total_net_payable'], 'INR') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Matched %</p>
            <p class="mt-1 text-2xl font-semibold text-brand-500">{{ $kpis['matched_percentage'] }}%</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Pending Reconciliation</p>
            <p class="mt-1 text-2xl font-semibold {{ $kpis['pending_reconciliation'] > 0 ? 'text-warning-500' : 'text-content-muted' }}">{{ $kpis['pending_reconciliation'] }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-4 mb-6">
        <form method="GET" action="{{ route('admin.settlements.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="w-48">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Marketplace</label>
                <select name="marketplace_account_id"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    <option value="">All</option>
                    @foreach ($marketplaceAccounts as $ma)
                        <option value="{{ $ma->id }}" @selected(request('marketplace_account_id') == $ma->id)>{{ $ma->account_name }} ({{ $ma->marketplace->name ?? '' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Status</label>
                <select name="status"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    <option value="">All</option>
                    @foreach (\App\Models\Settlement::STATUS_LABELS as $val => $label)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
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
                <a href="{{ route('admin.settlements.index') }}" class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">Clear</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
        <table class="min-w-full divide-y divide-surface-border">
            <thead class="bg-surface-secondary">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Settlement #</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Marketplace</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Date</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Orders</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Net Payable</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Match %</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-border-light">
                @forelse ($settlements as $settlement)
                    @php
                        $statusColors = [
                            'imported'              => 'bg-brand-50 text-brand-600',
                            'processing'            => 'bg-warning-50 text-warning-600',
                            'reconciled'            => 'bg-success-50 text-success-600',
                            'partially_reconciled'  => 'bg-warning-50 text-warning-600',
                            'disputed'              => 'bg-danger-50 text-danger-600',
                            'closed'                => 'bg-neutral-50 text-neutral-500',
                        ];
                        $totalLines = $settlement->lines_count ?? $settlement->lines()->count();
                        $matchedLines = $settlement->lines()->where('match_status', 'matched')->count();
                        $matchPct = $totalLines > 0 ? round($matchedLines / $totalLines * 100, 1) : 0;
                    @endphp
                    <tr class="hover:bg-surface-secondary/50 transition">
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('admin.settlements.show', $settlement) }}" class="font-mono text-brand-500 hover:underline text-xs">
                                {{ $settlement->settlement_reference }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-content">{{ $settlement->marketplaceAccount?->account_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-content-secondary">{{ $settlement->settlement_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-center text-sm text-content">{{ $settlement->total_orders }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-content">{{ Number::currency($settlement->net_payable, 'INR') }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$settlement->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                {{ $settlement->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-sm font-medium {{ $matchPct >= 90 ? 'text-success-500' : ($matchPct >= 50 ? 'text-warning-500' : 'text-danger-500') }}">{{ $matchPct }}%</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.settlements.show', $settlement) }}" class="text-content-muted hover:text-brand-500 transition" title="View">
                                <svg class="inline h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-sm text-content-muted">
                            No settlements found. <a href="{{ route('admin.settlements.upload') }}" class="text-brand-500 hover:underline">Import a settlement file.</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $settlements->links() }}
    </div>
</x-admin-layout>
