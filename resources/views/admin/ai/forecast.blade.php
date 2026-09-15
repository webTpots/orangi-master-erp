<x-admin-layout>
    <x-slot name="title">Demand Forecast</x-slot>
    <x-slot name="header">
        <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('admin.ai.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">AI Center</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-content">Forecast</span>
    </x-slot>

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="font-display text-xl font-bold text-content">Demand Forecast</h1>
            <p class="text-xs text-content-secondary mt-0.5">AI-powered demand prediction and stockout risk analysis</p>
        </div>
    </div>

    {{-- Design Selector --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-4 mb-6">
        <form method="GET" action="{{ route('admin.ai.forecast') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-content-secondary mb-1">Select Design</label>
                <select name="design_id" class="w-full rounded-lg border-surface-border text-sm py-1.5 px-3">
                    <option value="">-- Choose a design --</option>
                    @foreach($designs as $design)
                        <option value="{{ $design->id }}" {{ (string)$selectedDesignId === (string)$design->id ? 'selected' : '' }}>{{ $design->name }} ({{ $design->code }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-5 py-1.5 text-sm font-semibold transition-colors">
                Generate Forecast
            </button>
        </form>
    </div>

    {{-- Forecast Results --}}
    @if($forecast)
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Current Stock --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <p class="text-xs text-content-secondary mb-1">Current Stock</p>
                <p class="font-display text-2xl font-bold text-content">{{ number_format($forecast['current_stock']) }}</p>
                <p class="text-xs text-content-muted">units available</p>
            </div>

            {{-- Daily Run Rate --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <p class="text-xs text-content-secondary mb-1">Daily Run Rate</p>
                <p class="font-display text-2xl font-bold text-content">{{ $forecast['daily_run_rate'] }}</p>
                <p class="text-xs text-content-muted">units/day (7-day avg)</p>
            </div>

            {{-- Days Until Stockout --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <p class="text-xs text-content-secondary mb-1">Days Until Stockout</p>
                @php
                    $stockoutColor = $forecast['days_until_stockout'] <= 3 ? 'text-danger-500' : ($forecast['days_until_stockout'] <= 7 ? 'text-warning-500' : 'text-success-500');
                @endphp
                <p class="font-display text-2xl font-bold {{ $stockoutColor }}">
                    {{ $forecast['days_until_stockout'] >= 999 ? '999+' : $forecast['days_until_stockout'] }}
                </p>
                <p class="text-xs text-content-muted">at current rate</p>
            </div>

            {{-- Trend --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <p class="text-xs text-content-secondary mb-1">Trend</p>
                <div class="flex items-center gap-2">
                    @if($forecast['trend'] === 'up')
                        <svg class="w-6 h-6 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                        <span class="font-display text-xl font-bold text-success-500">+{{ abs($forecast['trend_pct']) }}%</span>
                    @elseif($forecast['trend'] === 'down')
                        <svg class="w-6 h-6 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        <span class="font-display text-xl font-bold text-danger-500">-{{ abs($forecast['trend_pct']) }}%</span>
                    @else
                        <svg class="w-6 h-6 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/></svg>
                        <span class="font-display text-xl font-bold text-content-muted">Stable</span>
                    @endif
                </div>
                <p class="text-xs text-content-muted">vs previous week</p>
            </div>
        </div>

        {{-- Suggested Reorder --}}
        @if($forecast['suggested_reorder'] > 0)
            <div class="bg-warning-50 border border-warning-500/20 rounded-xl p-4 mb-6 flex items-center gap-3">
                <svg class="w-5 h-5 text-warning-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <p class="text-sm font-semibold text-warning-700">Reorder Suggested</p>
                    <p class="text-xs text-warning-600">Order <strong>{{ number_format($forecast['suggested_reorder']) }} units</strong> of {{ $forecast['design_name'] }} to maintain 10 days of stock.</p>
                </div>
            </div>
        @endif
    @endif

    {{-- Stockout Risk Table --}}
    <section class="mt-6">
        <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">Stockout Risk (Within 7 Days)</h2>
        <div class="bg-white rounded-xl border border-surface-border-light shadow-card">
            @if(empty($stockoutRisks))
                <div class="p-6 text-center">
                    <div class="w-10 h-10 rounded-full bg-success-50 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <p class="text-sm text-content-secondary">No stockout risks detected</p>
                    <p class="text-xs text-content-muted mt-0.5">All SKUs have sufficient stock for the next 7 days</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-surface-border-light">
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">SKU</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Name</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Stock</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Daily Rate</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Days Left</th>
                                <th class="text-center px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Urgency</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border-light">
                            @foreach($stockoutRisks as $risk)
                                @php
                                    $urgencyBadge = match($risk['urgency']) {
                                        'critical' => 'bg-danger-50 text-danger-600',
                                        'high'     => 'bg-warning-50 text-warning-600',
                                        default    => 'bg-brand-50 text-brand-600',
                                    };
                                @endphp
                                <tr class="hover:bg-surface-secondary/50 transition-colors">
                                    <td class="px-4 py-2.5 font-mono text-xs text-content">{{ $risk['sku_code'] }}</td>
                                    <td class="px-4 py-2.5 text-content-secondary">{{ Str::limit($risk['sku_name'], 40) }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-content">{{ $risk['current_stock'] }}</td>
                                    <td class="px-4 py-2.5 text-right text-content-secondary">{{ $risk['avg_daily'] }}/day</td>
                                    <td class="px-4 py-2.5 text-right font-semibold {{ $risk['days_of_stock'] <= 2 ? 'text-danger-500' : 'text-warning-500' }}">{{ $risk['days_of_stock'] }}</td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $urgencyBadge }}">{{ ucfirst($risk['urgency']) }}</span>
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
