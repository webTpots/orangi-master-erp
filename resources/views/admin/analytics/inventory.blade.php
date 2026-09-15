<x-admin-layout>
    <x-slot name="title">Inventory Analytics</x-slot>
    <x-slot name="header">
        <a href="{{ route('admin.analytics.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Analytics</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-content">Inventory Analytics</span>
    </x-slot>

    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="font-display text-xl font-bold text-content">Inventory Analytics</h1>
        <p class="text-xs text-content-secondary mt-0.5">Stock health, movement analysis, and reorder alerts</p>
    </div>

    {{-- KPI Cards --}}
    <section class="mb-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold text-content">{{ number_format($data['total_skus']) }}</p>
                <p class="text-xs text-content-secondary mt-0.5">Total SKUs</p>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-success-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold text-content">{{ number_format($data['total_units']) }}</p>
                <p class="text-xs text-content-secondary mt-0.5">Total Units</p>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-neutral-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold text-content text-right">
                    <span class="text-sm font-normal text-content-secondary">&#8377;</span>{{ number_format($data['stock_value'], 0) }}
                </p>
                <p class="text-xs text-content-secondary mt-0.5">Stock Value (Cost)</p>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-{{ $data['dead_stock'] > 0 ? 'warning' : 'success' }}-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-{{ $data['dead_stock'] > 0 ? 'warning' : 'success' }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold {{ $data['dead_stock'] > 0 ? 'text-warning-500' : 'text-content' }}">{{ number_format($data['dead_stock']) }}</p>
                <p class="text-xs text-content-secondary mt-0.5">Dead Stock SKUs</p>
            </div>
        </div>
    </section>

    {{-- Stock Health Distribution --}}
    <section class="mb-6">
        <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">Stock Health</h2>
        <div class="bg-white rounded-xl border border-surface-border-light p-5">
            @php
                $healthTotal = $data['stock_health']['good'] + $data['stock_health']['warning'] + $data['stock_health']['critical'];
                $goodPct = $healthTotal > 0 ? round(($data['stock_health']['good'] / $healthTotal) * 100) : 0;
                $warningPct = $healthTotal > 0 ? round(($data['stock_health']['warning'] / $healthTotal) * 100) : 0;
                $criticalPct = $healthTotal > 0 ? round(($data['stock_health']['critical'] / $healthTotal) * 100) : 0;
            @endphp

            @if($healthTotal > 0)
                {{-- Progress bar --}}
                <div class="w-full h-4 rounded-full bg-neutral-100 overflow-hidden flex mb-4">
                    @if($goodPct > 0)
                        <div class="bg-success-500 h-full" style="width: {{ $goodPct }}%"></div>
                    @endif
                    @if($warningPct > 0)
                        <div class="bg-warning-500 h-full" style="width: {{ $warningPct }}%"></div>
                    @endif
                    @if($criticalPct > 0)
                        <div class="bg-danger-500 h-full" style="width: {{ $criticalPct }}%"></div>
                    @endif
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-success-500 flex-shrink-0"></span>
                        <div>
                            <p class="text-sm font-semibold text-content">{{ $data['stock_health']['good'] }}</p>
                            <p class="text-xs text-content-secondary">Good ({{ $goodPct }}%)</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-warning-500 flex-shrink-0"></span>
                        <div>
                            <p class="text-sm font-semibold text-content">{{ $data['stock_health']['warning'] }}</p>
                            <p class="text-xs text-content-secondary">Warning ({{ $warningPct }}%)</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-danger-500 flex-shrink-0"></span>
                        <div>
                            <p class="text-sm font-semibold text-content">{{ $data['stock_health']['critical'] }}</p>
                            <p class="text-xs text-content-secondary">Critical ({{ $criticalPct }}%)</p>
                        </div>
                    </div>
                </div>
            @else
                <p class="text-sm text-content-secondary text-center py-4">Set minimum stock levels on SKUs to see stock health data</p>
            @endif
        </div>
    </section>

    {{-- Two columns: Fast Movers + Slow Movers --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Fast Movers --}}
        <section>
            <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">Fast Movers (Top 10)</h2>
            <div class="bg-white rounded-xl border border-surface-border-light shadow-card">
                @if($data['fast_movers']->isEmpty())
                    <div class="p-6 text-center">
                        <p class="text-sm text-content-secondary">No sales data in last 30 days</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-surface-border-light">
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">SKU</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Sold (30d)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-border-light">
                                @foreach($data['fast_movers'] as $item)
                                    @php $sku = $skus[$item->sku_id] ?? null; @endphp
                                    <tr class="hover:bg-surface-secondary/50 transition-colors">
                                        <td class="px-4 py-2.5">
                                            <p class="text-sm font-medium text-content">{{ $sku->sku_code ?? "SKU #{$item->sku_id}" }}</p>
                                            @if($sku)
                                                <p class="text-xs text-content-secondary truncate">{{ $sku->short_name }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-right">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-success-50 text-success-600">{{ number_format($item->total_sold) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        {{-- Slow Movers --}}
        <section>
            <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">Slow Movers / Dead Stock (Bottom 10)</h2>
            <div class="bg-white rounded-xl border border-surface-border-light shadow-card">
                @if($data['slow_movers']->isEmpty())
                    <div class="p-6 text-center">
                        <p class="text-sm text-content-secondary">No inventory data available</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-surface-border-light">
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">SKU</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Stock</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Sold (30d)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-border-light">
                                @foreach($data['slow_movers'] as $item)
                                    @php $sku = $skus[$item->sku_id] ?? null; @endphp
                                    <tr class="hover:bg-surface-secondary/50 transition-colors">
                                        <td class="px-4 py-2.5">
                                            <p class="text-sm font-medium text-content">{{ $sku->sku_code ?? "SKU #{$item->sku_id}" }}</p>
                                            @if($sku)
                                                <p class="text-xs text-content-secondary truncate">{{ $sku->short_name }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-right text-content">{{ number_format($item->available_stock) }}</td>
                                        <td class="px-4 py-2.5 text-right">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold {{ $item->total_sold == 0 ? 'bg-danger-50 text-danger-600' : 'bg-warning-50 text-warning-600' }}">
                                                {{ number_format($item->total_sold) }}
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
    </div>

    {{-- Reorder Alerts --}}
    <section>
        <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">Reorder Alerts</h2>
        <div class="bg-white rounded-xl border border-surface-border-light shadow-card">
            @if($data['reorder_alerts']->isEmpty())
                <div class="p-6 text-center">
                    <div class="w-10 h-10 rounded-full bg-success-50 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <p class="text-sm text-content-secondary">All SKUs are above minimum stock levels</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-surface-border">
                                <th class="text-left px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">SKU Code</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Name</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Available</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Min Level</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border-light">
                            @foreach($data['reorder_alerts'] as $alert)
                                @php
                                    $sku = $skus[$alert->sku_id] ?? null;
                                    $isOOS = $alert->available_stock <= 0;
                                @endphp
                                <tr class="hover:bg-surface-secondary/50 transition-colors">
                                    <td class="px-4 py-2.5 font-medium text-content">{{ $alert->sku_code }}</td>
                                    <td class="px-4 py-2.5 text-content-secondary">{{ $sku ? $sku->short_name : '-' }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold {{ $isOOS ? 'text-danger-500' : 'text-warning-500' }}">{{ $alert->available_stock }}</td>
                                    <td class="px-4 py-2.5 text-right text-content-secondary">{{ $alert->minimum_stock_level }}</td>
                                    <td class="px-4 py-2.5 text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold {{ $isOOS ? 'bg-danger-50 text-danger-600' : 'bg-warning-50 text-warning-600' }}">
                                            {{ $isOOS ? 'Out of Stock' : 'Low Stock' }}
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
</x-admin-layout>
