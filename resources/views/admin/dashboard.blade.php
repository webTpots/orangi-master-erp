<x-admin-layout>
    <x-slot name="title">Dashboard</x-slot>

    {{-- Top bar: greeting + quick actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="font-display text-xl font-bold text-content">{{ $greeting }}, {{ auth()->user()->name }}</h1>
            <p class="text-xs text-content-secondary mt-0.5">{{ now()->format('l, d M Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.purchase-orders.create') }}" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New PO
            </a>
            <a href="{{ route('admin.products.import') }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Upload Labels
            </a>
        </div>
    </div>

    {{-- Revenue KPIs --}}
    <section class="mb-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide">Revenue Snapshot</h2>
            <a href="{{ route('admin.analytics.dashboard') }}" class="text-xs text-brand-500 hover:text-brand-600 font-medium transition-colors inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                Full Analytics
            </a>
        </div>
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <p class="font-display text-2xl font-bold text-content text-right"><span class="text-sm font-normal text-content-secondary">&#8377;</span>{{ number_format($todayRevenue, 0) }}</p>
                <p class="text-xs text-content-secondary mt-0.5">Revenue Today</p>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <p class="font-display text-2xl font-bold text-content text-right"><span class="text-sm font-normal text-content-secondary">&#8377;</span>{{ number_format($weekRevenue, 0) }}</p>
                <p class="text-xs text-content-secondary mt-0.5">Revenue This Week</p>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <p class="font-display text-2xl font-bold text-content text-right"><span class="text-sm font-normal text-content-secondary">&#8377;</span>{{ number_format($monthRevenue, 0) }}</p>
                <p class="text-xs text-content-secondary mt-0.5">Revenue This Month</p>
            </div>
        </div>
    </section>

    {{-- Pending Actions --}}
    @if($unmatchedSettlements + $pendingPOs + $pendingReturns > 0)
        <section class="mb-6">
            <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">Pending Actions</h2>
            <div class="bg-white rounded-xl border border-surface-border-light p-4 flex flex-wrap gap-4">
                @if($unmatchedSettlements > 0)
                    <a href="{{ route('admin.settlements.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-warning-50 hover:bg-warning-100 transition-colors">
                        <svg class="w-4 h-4 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        <span class="text-sm font-semibold text-warning-700">{{ $unmatchedSettlements }} unmatched settlements</span>
                    </a>
                @endif
                @if($pendingPOs > 0)
                    <a href="{{ route('admin.purchase-orders.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-brand-50 hover:bg-brand-100 transition-colors">
                        <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="text-sm font-semibold text-brand-700">{{ $pendingPOs }} pending POs</span>
                    </a>
                @endif
                @if($pendingReturns > 0)
                    <a href="{{ route('admin.returns.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-danger-50 hover:bg-danger-100 transition-colors">
                        <svg class="w-4 h-4 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                        <span class="text-sm font-semibold text-danger-700">{{ $pendingReturns }} inspection pending returns</span>
                    </a>
                @endif
            </div>
        </section>
    @endif

    {{-- Section 1: TODAY'S OVERVIEW --}}
    <section class="mb-6">
        <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">Today's Overview</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold text-content">0</p>
                <p class="text-xs text-content-secondary mt-0.5">Total Orders</p>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-success-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold text-content">0</p>
                <p class="text-xs text-content-secondary mt-0.5">Ready to Ship</p>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold text-content">0</p>
                <p class="text-xs text-content-secondary mt-0.5">Shipped</p>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-warning-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold text-content">0</p>
                <p class="text-xs text-content-secondary mt-0.5">Returns / RTO</p>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-danger-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold text-danger-500">0</p>
                <p class="text-xs text-content-secondary mt-0.5">SLA Risk</p>
            </div>
        </div>
    </section>

    {{-- Section 2: INVENTORY SNAPSHOT --}}
    <section class="mb-6">
        <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">Inventory Snapshot</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <p class="font-display text-2xl font-bold text-content">{{ number_format($totalSkus) }}</p>
                <p class="text-xs text-content-secondary mt-0.5">Total SKUs</p>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <p class="font-display text-2xl font-bold text-content">{{ number_format($totalStock) }}</p>
                <p class="text-xs text-content-secondary mt-0.5">Total Stock (units)</p>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <p class="font-display text-2xl font-bold text-content">{{ $stockValue > 0 ? number_format($stockValue, 0) : '0' }}</p>
                <p class="text-xs text-content-secondary mt-0.5">Stock Value (cost)</p>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-baseline gap-2">
                    <p class="font-display text-2xl font-bold {{ $lowStockCount + $outOfStockCount > 0 ? 'text-warning-500' : 'text-content' }}">{{ $lowStockCount }}</p>
                    @if($outOfStockCount > 0)
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-danger-50 text-danger-500">{{ $outOfStockCount }} OOS</span>
                    @endif
                </div>
                <p class="text-xs text-content-secondary mt-0.5">Low Stock / Out of Stock</p>
            </div>
        </div>
    </section>

    {{-- Section 3: Two columns --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Needs Attention --}}
        <section>
            <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">Needs Attention</h2>
            <div class="bg-white rounded-xl border border-surface-border-light shadow-card">
                @if($attentionItems->isEmpty())
                    <div class="p-6 text-center">
                        <div class="w-10 h-10 rounded-full bg-success-50 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <p class="text-sm text-content-secondary">No issues right now</p>
                        <p class="text-xs text-content-muted mt-0.5">All systems operating normally</p>
                    </div>
                @else
                    <div class="divide-y divide-surface-border-light">
                        @foreach($attentionItems as $item)
                            <div class="px-4 py-3 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    @php
                                        $severityColor = match($item->severity) {
                                            'critical' => 'bg-danger-50 text-danger-500',
                                            'high'     => 'bg-warning-50 text-warning-500',
                                            'medium'   => 'bg-brand-50 text-brand-500',
                                            default    => 'bg-neutral-50 text-neutral-500',
                                        };
                                    @endphp
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-md {{ $severityColor }}">{{ ucfirst($item->severity) }}</span>
                                    <div class="min-w-0">
                                        <p class="text-sm text-content truncate">{{ $item->title }}</p>
                                        <p class="text-xs text-content-secondary truncate">{{ $item->exception_type }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- Recent Activity --}}
        <section>
            <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">Recent Activity</h2>
            <div class="bg-white rounded-xl border border-surface-border-light shadow-card">
                @if($recentLogs->isEmpty())
                    <div class="p-6 text-center">
                        <p class="text-sm text-content-secondary">No recent activity</p>
                        <p class="text-xs text-content-muted mt-0.5">Actions will appear here as you use the system</p>
                    </div>
                @else
                    <div class="divide-y divide-surface-border-light">
                        @foreach($recentLogs as $log)
                            <div class="px-4 py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-content truncate">
                                        <span class="font-medium">{{ $log->user?->name ?? 'System' }}</span>
                                        <span class="text-content-secondary">{{ $log->action }}</span>
                                        <span class="text-content-muted">{{ class_basename($log->auditable_type ?? '') }}</span>
                                    </p>
                                </div>
                                <time class="text-xs text-content-muted flex-shrink-0">{{ $log->created_at->diffForHumans() }}</time>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>

    {{-- Section 4: QUICK METRICS --}}
    <section>
        <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">Quick Metrics</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-surface-border-light p-4 flex items-center gap-3">
                <span class="w-8 h-8 rounded-lg bg-warning-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
                <div>
                    <p class="font-display text-lg font-bold text-content">{{ $pendingPOs }}</p>
                    <p class="text-xs text-content-secondary">Pending POs</p>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light p-4 flex items-center gap-3">
                <span class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </span>
                <div>
                    <p class="font-display text-lg font-bold text-content">{{ $unmappedSkus }}</p>
                    <p class="text-xs text-content-secondary">Unmapped SKUs</p>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light p-4 flex items-center gap-3">
                <span class="w-8 h-8 rounded-lg bg-neutral-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <div>
                    <p class="font-display text-lg font-bold text-content">0</p>
                    <p class="text-xs text-content-secondary">Pending Payments</p>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light p-4 flex items-center gap-3">
                <span class="w-8 h-8 rounded-lg bg-danger-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
                <div>
                    <p class="font-display text-lg font-bold {{ $openExceptions > 0 ? 'text-danger-500' : 'text-content' }}">{{ $openExceptions }}</p>
                    <p class="text-xs text-content-secondary">Open Exceptions</p>
                </div>
            </div>
        </div>
    </section>
</x-admin-layout>
