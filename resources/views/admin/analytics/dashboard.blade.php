<x-admin-layout>
    <x-slot name="title">Analytics Dashboard</x-slot>
    <x-slot name="header">
        <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-content">Analytics</span>
    </x-slot>

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="font-display text-xl font-bold text-content">Owner Decision Dashboard</h1>
            <p class="text-xs text-content-secondary mt-0.5">{{ now()->format('l, d M Y') }} &mdash; Real-time business insights</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.analytics.profit-loss') }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                View P&L
            </a>
        </div>
    </div>

    {{-- Row 1: KPI Cards --}}
    <section class="mb-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Revenue Today --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-success-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold text-content text-right">
                    <span class="text-sm font-normal text-content-secondary">&#8377;</span>{{ number_format($data['today']['revenue'], 0) }}
                </p>
                <p class="text-xs text-content-secondary mt-0.5">Revenue Today</p>
                <div class="mt-1 flex items-center gap-1 text-xs">
                    <span class="text-content-muted">This week:</span>
                    <span class="font-semibold text-content">&#8377;{{ number_format($data['week']['revenue'], 0) }}</span>
                </div>
            </div>

            {{-- Orders Today --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold text-content text-right">{{ number_format($data['today']['orders']) }}</p>
                <p class="text-xs text-content-secondary mt-0.5">Orders Today</p>
                <div class="mt-1 flex items-center gap-1 text-xs">
                    <span class="text-content-muted">This month:</span>
                    <span class="font-semibold text-content">{{ number_format($data['month']['orders']) }}</span>
                </div>
            </div>

            {{-- Profit Margin --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-{{ $data['month']['profit_margin'] >= 10 ? 'success' : ($data['month']['profit_margin'] >= 0 ? 'warning' : 'danger') }}-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-{{ $data['month']['profit_margin'] >= 10 ? 'success' : ($data['month']['profit_margin'] >= 0 ? 'warning' : 'danger') }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold text-right {{ $data['month']['profit_margin'] >= 10 ? 'text-success-500' : ($data['month']['profit_margin'] >= 0 ? 'text-warning-500' : 'text-danger-500') }}">
                    {{ $data['month']['profit_margin'] }}%
                </p>
                <p class="text-xs text-content-secondary mt-0.5">Profit Margin (Month)</p>
                <div class="mt-1 flex items-center gap-1 text-xs">
                    <span class="text-content-muted">Net:</span>
                    <span class="font-semibold text-content">&#8377;{{ number_format($data['month']['profit'], 0) }}</span>
                </div>
            </div>

            {{-- Pending Actions --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-{{ $data['pending_actions']['total'] > 0 ? 'danger' : 'success' }}-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-{{ $data['pending_actions']['total'] > 0 ? 'danger' : 'success' }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold text-right {{ $data['pending_actions']['total'] > 0 ? 'text-danger-500' : 'text-content' }}">
                    {{ $data['pending_actions']['total'] }}
                </p>
                <p class="text-xs text-content-secondary mt-0.5">Pending Actions</p>
                <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-content-muted">
                    @if($data['pending_actions']['unmatched_settlements'] > 0)
                        <span>{{ $data['pending_actions']['unmatched_settlements'] }} unmatched</span>
                    @endif
                    @if($data['pending_actions']['pending_pos'] > 0)
                        <span>{{ $data['pending_actions']['pending_pos'] }} POs</span>
                    @endif
                    @if($data['pending_actions']['pending_returns'] > 0)
                        <span>{{ $data['pending_actions']['pending_returns'] }} returns</span>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Row 2: Revenue Trend Chart Placeholder --}}
    <section class="mb-6">
        <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">Revenue Trend (Last 30 Days)</h2>
        <div class="bg-white rounded-xl border border-surface-border-light p-6"
             data-chart="revenue-trend"
             data-labels="{{ $profitTrend->pluck('period_date')->map(fn($d) => $d->format('d M'))->toJson() }}"
             data-revenue="{{ $profitTrend->pluck('total_revenue')->toJson() }}"
             data-profit="{{ $profitTrend->pluck('net_profit')->toJson() }}"
        >
            {{-- Chart.js placeholder — replace with actual chart implementation --}}
            <div class="flex items-center justify-center h-48 text-content-muted">
                <div class="text-center">
                    <svg class="w-10 h-10 mx-auto mb-2 text-surface-border" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                    <p class="text-sm">Chart data ready &mdash; integrate Chart.js to visualize</p>
                    <p class="text-xs mt-1">{{ $profitTrend->count() }} data points available</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Row 3: Two columns --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Top Performing Designs --}}
        <section>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide">Top Performing Designs</h2>
                <a href="{{ route('admin.analytics.designs') }}" class="text-xs text-brand-500 hover:text-brand-600 font-medium transition-colors">View All</a>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light shadow-card">
                @if($data['top_designs']->isEmpty())
                    <div class="p-6 text-center">
                        <p class="text-sm text-content-secondary">No design performance data yet</p>
                        <p class="text-xs text-content-muted mt-0.5">Data will appear after analytics are calculated</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-surface-border-light">
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Design</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Units</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Revenue</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Profit</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Margin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-border-light">
                                @foreach($data['top_designs'] as $dp)
                                    <tr class="hover:bg-surface-secondary/50 transition-colors">
                                        <td class="px-4 py-2.5 font-medium text-content">{{ $dp->design->name ?? "Design #{$dp->design_id}" }}</td>
                                        <td class="px-4 py-2.5 text-right text-content-secondary">{{ number_format($dp->total_units) }}</td>
                                        <td class="px-4 py-2.5 text-right text-content">&#8377;{{ number_format($dp->total_revenue, 0) }}</td>
                                        <td class="px-4 py-2.5 text-right font-semibold {{ $dp->total_profit >= 0 ? 'text-success-500' : 'text-danger-500' }}">&#8377;{{ number_format($dp->total_profit, 0) }}</td>
                                        <td class="px-4 py-2.5 text-right">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold {{ $dp->margin >= 15 ? 'bg-success-50 text-success-600' : ($dp->margin >= 0 ? 'bg-warning-50 text-warning-600' : 'bg-danger-50 text-danger-600') }}">
                                                {{ $dp->margin }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        {{-- Attention Required --}}
        <section>
            <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">Attention Required</h2>
            <div class="bg-white rounded-xl border border-surface-border-light shadow-card">
                <div class="divide-y divide-surface-border-light">
                    {{-- High return rate designs --}}
                    @forelse($data['problem_designs'] as $pd)
                        @php $pDesign = \App\Models\Design::find($pd->design_id); @endphp
                        <div class="px-4 py-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-danger-50 text-danger-500">Returns</span>
                                <div class="min-w-0">
                                    <p class="text-sm text-content truncate">{{ $pDesign->name ?? "Design #{$pd->design_id}" }}</p>
                                    <p class="text-xs text-content-secondary">Return rate: {{ $pd->return_pct }}% ({{ $pd->total_returned }}/{{ $pd->total_units }} units)</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        {{-- No problematic designs --}}
                    @endforelse

                    {{-- Low stock alert --}}
                    @if($data['inventory_alerts']['low_stock'] > 0)
                        <div class="px-4 py-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-warning-50 text-warning-500">Stock</span>
                                <div class="min-w-0">
                                    <p class="text-sm text-content">{{ $data['inventory_alerts']['low_stock'] }} SKUs below minimum stock level</p>
                                    <p class="text-xs text-content-secondary">Review and place reorder POs</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.analytics.inventory') }}" class="text-xs text-brand-500 hover:text-brand-600 font-medium flex-shrink-0">View</a>
                        </div>
                    @endif

                    @if($data['inventory_alerts']['out_of_stock'] > 0)
                        <div class="px-4 py-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-danger-50 text-danger-500">OOS</span>
                                <div class="min-w-0">
                                    <p class="text-sm text-content">{{ $data['inventory_alerts']['out_of_stock'] }} SKUs are out of stock</p>
                                    <p class="text-xs text-content-secondary">Lost sales potential</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Unmatched settlements --}}
                    @if($data['pending_actions']['unmatched_settlements'] > 0)
                        <div class="px-4 py-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-brand-50 text-brand-500">Settle</span>
                                <div class="min-w-0">
                                    <p class="text-sm text-content">{{ $data['pending_actions']['unmatched_settlements'] }} unreconciled settlement lines</p>
                                    <p class="text-xs text-content-secondary">Match with orders for accurate P&L</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.settlements.index') }}" class="text-xs text-brand-500 hover:text-brand-600 font-medium flex-shrink-0">View</a>
                        </div>
                    @endif

                    @if($data['problem_designs']->isEmpty() && $data['inventory_alerts']['low_stock'] === 0 && $data['inventory_alerts']['out_of_stock'] === 0 && $data['pending_actions']['unmatched_settlements'] === 0)
                        <div class="p-6 text-center">
                            <div class="w-10 h-10 rounded-full bg-success-50 flex items-center justify-center mx-auto mb-2">
                                <svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <p class="text-sm text-content-secondary">Everything looks good</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>

    {{-- Row 4: Two columns --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Marketplace Comparison --}}
        <section>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide">Marketplace Comparison</h2>
                <a href="{{ route('admin.analytics.marketplaces') }}" class="text-xs text-brand-500 hover:text-brand-600 font-medium transition-colors">Details</a>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light shadow-card">
                @if($data['marketplace_data']->isEmpty())
                    <div class="p-6 text-center">
                        <p class="text-sm text-content-secondary">No marketplace data yet</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-surface-border-light">
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Marketplace</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Orders</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Revenue</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Comm%</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Return%</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Net Profit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-border-light">
                                @foreach($data['marketplace_data'] as $mp)
                                    @php $account = \App\Models\MarketplaceAccount::find($mp->marketplace_account_id); @endphp
                                    <tr class="hover:bg-surface-secondary/50 transition-colors">
                                        <td class="px-4 py-2.5 font-medium text-content">{{ $account->account_name ?? 'Unknown' }}</td>
                                        <td class="px-4 py-2.5 text-right text-content-secondary">{{ number_format($mp->orders) }}</td>
                                        <td class="px-4 py-2.5 text-right text-content">&#8377;{{ number_format($mp->revenue, 0) }}</td>
                                        <td class="px-4 py-2.5 text-right text-content-secondary">{{ $mp->commission_pct }}%</td>
                                        <td class="px-4 py-2.5 text-right">
                                            <span class="{{ $mp->return_pct > 10 ? 'text-danger-500' : 'text-content-secondary' }}">{{ $mp->return_pct }}%</span>
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-semibold {{ $mp->net_profit >= 0 ? 'text-success-500' : 'text-danger-500' }}">&#8377;{{ number_format($mp->net_profit, 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        {{-- WHY Analysis --}}
        <section>
            <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">WHY Analysis</h2>
            <div class="bg-white rounded-xl border border-surface-border-light shadow-card">
                @if(empty($insights))
                    <div class="p-6 text-center">
                        <div class="w-10 h-10 rounded-full bg-neutral-50 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-5 h-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        </div>
                        <p class="text-sm text-content-secondary">Not enough data for insights yet</p>
                        <p class="text-xs text-content-muted mt-0.5">Insights will appear as more data is collected</p>
                    </div>
                @else
                    <div class="divide-y divide-surface-border-light">
                        @foreach($insights as $insight)
                            @php
                                $borderColor = match($insight['type']) {
                                    'danger'  => 'border-l-danger-500',
                                    'warning' => 'border-l-warning-500',
                                    'success' => 'border-l-success-500',
                                    default   => 'border-l-brand-500',
                                };
                                $iconColor = match($insight['type']) {
                                    'danger'  => 'text-danger-500',
                                    'warning' => 'text-warning-500',
                                    'success' => 'text-success-500',
                                    default   => 'text-brand-500',
                                };
                            @endphp
                            <div class="px-4 py-3 border-l-2 {{ $borderColor }}">
                                <div class="flex items-start gap-2">
                                    @if($insight['type'] === 'danger')
                                        <svg class="w-4 h-4 {{ $iconColor }} flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h-.01M13 10V6.5M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12z"/></svg>
                                    @elseif($insight['type'] === 'warning')
                                        <svg class="w-4 h-4 {{ $iconColor }} flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    @elseif($insight['type'] === 'success')
                                        <svg class="w-4 h-4 {{ $iconColor }} flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                    @else
                                        <svg class="w-4 h-4 {{ $iconColor }} flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @endif
                                    <div>
                                        <p class="text-sm font-semibold text-content">{{ $insight['title'] }}</p>
                                        <p class="text-xs text-content-secondary mt-0.5">{{ $insight['message'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>

    {{-- Row 5: P&L Summary --}}
    <section>
        <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">P&L Summary (This Month vs Previous)</h2>
        <div class="bg-white rounded-xl border border-surface-border-light shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-surface-border-light">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Item</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">This Month</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Previous Month</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Change</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border-light">
                        @php
                            $pnlRows = [
                                ['Revenue', $currentPnl['revenue'], $prevPnl['revenue'], false],
                                ['(-) Cost of Goods Sold', $currentPnl['cogs'], $prevPnl['cogs'], true],
                                ['= Gross Profit', $currentPnl['gross_profit'], $prevPnl['gross_profit'], false],
                                ['(-) Marketplace Commission', $currentPnl['commission'], $prevPnl['commission'], true],
                                ['(-) Shipping Costs', $currentPnl['shipping'], $prevPnl['shipping'], true],
                                ['(-) Return/RTO Costs', $currentPnl['returns'], $prevPnl['returns'], true],
                                ['(-) Penalties', $currentPnl['penalties'], $prevPnl['penalties'], true],
                                ['(-) Other Expenses', $currentPnl['other'], $prevPnl['other'], true],
                                ['= Net Profit', $currentPnl['net_profit'], $prevPnl['net_profit'], false],
                            ];
                        @endphp
                        @foreach($pnlRows as [$label, $current, $prev, $isDeduction])
                            @php
                                $change = $prev != 0 ? round((($current - $prev) / abs($prev)) * 100, 1) : 0;
                                $isTotal = str_starts_with($label, '=');
                                // For deductions: increase is bad (red), decrease is good (green)
                                // For revenue/profit: increase is good (green), decrease is bad (red)
                                $changeIsGood = $isDeduction ? $change <= 0 : $change >= 0;
                            @endphp
                            <tr class="{{ $isTotal ? 'bg-surface-secondary/30 font-semibold' : '' }} hover:bg-surface-secondary/50 transition-colors">
                                <td class="px-4 py-2.5 text-content {{ $isTotal ? 'font-semibold' : '' }}">{{ $label }}</td>
                                <td class="px-4 py-2.5 text-right text-content {{ $isTotal ? 'font-semibold' : '' }}">&#8377;{{ number_format($current, 0) }}</td>
                                <td class="px-4 py-2.5 text-right text-content-secondary">&#8377;{{ number_format($prev, 0) }}</td>
                                <td class="px-4 py-2.5 text-right">
                                    @if($change != 0)
                                        <span class="inline-flex items-center gap-0.5 text-xs font-semibold {{ $changeIsGood ? 'text-success-500' : 'text-danger-500' }}">
                                            @if($change > 0)
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                            @else
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            @endif
                                            {{ abs($change) }}%
                                        </span>
                                    @else
                                        <span class="text-xs text-content-muted">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        {{-- Profit Margin Row --}}
                        @php
                            $currentMargin = $currentPnl['revenue'] > 0 ? round(($currentPnl['net_profit'] / $currentPnl['revenue']) * 100, 1) : 0;
                            $prevMargin = $prevPnl['revenue'] > 0 ? round(($prevPnl['net_profit'] / $prevPnl['revenue']) * 100, 1) : 0;
                            $marginDiff = round($currentMargin - $prevMargin, 1);
                        @endphp
                        <tr class="bg-surface-secondary/30 font-semibold hover:bg-surface-secondary/50 transition-colors">
                            <td class="px-4 py-2.5 font-semibold text-content">Profit Margin</td>
                            <td class="px-4 py-2.5 text-right font-semibold {{ $currentMargin >= 0 ? 'text-success-500' : 'text-danger-500' }}">{{ $currentMargin }}%</td>
                            <td class="px-4 py-2.5 text-right text-content-secondary">{{ $prevMargin }}%</td>
                            <td class="px-4 py-2.5 text-right">
                                @if($marginDiff != 0)
                                    <span class="inline-flex items-center gap-0.5 text-xs font-semibold {{ $marginDiff >= 0 ? 'text-success-500' : 'text-danger-500' }}">
                                        @if($marginDiff > 0)
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        @else
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        @endif
                                        {{ abs($marginDiff) }}pp
                                    </span>
                                @else
                                    <span class="text-xs text-content-muted">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-admin-layout>
