<x-admin-layout>
    <x-slot name="title">Design Performance</x-slot>
    <x-slot name="header">
        <a href="{{ route('admin.analytics.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Analytics</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-content">Design Performance</span>
    </x-slot>

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="font-display text-xl font-bold text-content">Design Performance</h1>
            <p class="text-xs text-content-secondary mt-0.5">This month &mdash; Design-level profit and return analysis</p>
        </div>

        {{-- Sort Selector --}}
        <div class="flex items-center gap-1 bg-white rounded-lg border border-surface-border p-0.5">
            @foreach(['profit' => 'Profit', 'revenue' => 'Revenue', 'units' => 'Units', 'return_rate' => 'Returns', 'stock_days' => 'Stock'] as $key => $label)
                <a href="{{ route('admin.analytics.designs', ['sort' => $key]) }}"
                   class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ $sortBy === $key ? 'bg-brand-500 text-white' : 'text-content-secondary hover:text-content hover:bg-surface-secondary' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Design Table --}}
    <div class="bg-white rounded-xl border border-surface-border-light shadow-card">
        @if($rankings->isEmpty())
            <div class="p-12 text-center">
                <div class="w-12 h-12 rounded-full bg-neutral-50 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <p class="text-sm text-content-secondary font-medium">No design performance data available</p>
                <p class="text-xs text-content-muted mt-1">Analytics will populate as orders are processed</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-surface-border">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">#</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Design</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Category</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Units Sold</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Revenue</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Cost</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Profit</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Margin%</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Return%</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Stock</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Days Left</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border-light">
                        @foreach($rankings as $index => $rank)
                            @php
                                $design = $designs[$rank->design_id] ?? null;
                                $profitColor = $rank->total_profit > 0 ? 'text-success-500' : ($rank->total_profit < 0 ? 'text-danger-500' : 'text-content-secondary');
                                $marginColor = $rank->margin >= 15 ? 'bg-success-50 text-success-600' : ($rank->margin >= 0 ? 'bg-warning-50 text-warning-600' : 'bg-danger-50 text-danger-600');
                                $returnColor = $rank->return_pct > 15 ? 'bg-danger-50 text-danger-600' : ($rank->return_pct > 8 ? 'bg-warning-50 text-warning-600' : 'bg-success-50 text-success-600');
                                $stockColor = $rank->days_stock <= 7 ? 'text-danger-500' : ($rank->days_stock <= 14 ? 'text-warning-500' : 'text-content-secondary');
                            @endphp
                            <tr class="hover:bg-surface-secondary/50 transition-colors">
                                <td class="px-4 py-3 text-content-muted text-xs">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-content">{{ $design->name ?? "Design #{$rank->design_id}" }}</td>
                                <td class="px-4 py-3 text-content-secondary text-xs">{{ $design->category ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-content">{{ number_format($rank->total_units) }}</td>
                                <td class="px-4 py-3 text-right text-content">&#8377;{{ number_format($rank->total_revenue, 0) }}</td>
                                <td class="px-4 py-3 text-right text-content-secondary">&#8377;{{ number_format($rank->total_cost, 0) }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $profitColor }}">&#8377;{{ number_format($rank->total_profit, 0) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold {{ $marginColor }}">{{ $rank->margin }}%</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold {{ $returnColor }}">{{ $rank->return_pct }}%</span>
                                </td>
                                <td class="px-4 py-3 text-right text-content">{{ number_format($rank->stock) }}</td>
                                <td class="px-4 py-3 text-right {{ $stockColor }} font-medium">
                                    {{ $rank->days_stock >= 999 ? 'N/A' : $rank->days_stock . 'd' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-admin-layout>
