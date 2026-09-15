<x-admin-layout>
    <x-slot name="title">Profit & Loss</x-slot>
    <x-slot name="header">
        <a href="{{ route('admin.analytics.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Analytics</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-content">Profit & Loss</span>
    </x-slot>

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="font-display text-xl font-bold text-content">Profit & Loss Statement</h1>
            <p class="text-xs text-content-secondary mt-0.5">{{ $from->format('d M Y') }} &mdash; {{ $to->format('d M Y') }}</p>
        </div>

        {{-- Period Selector --}}
        <div class="flex items-center gap-1 bg-white rounded-lg border border-surface-border p-0.5">
            <a href="{{ route('admin.analytics.profit-loss', ['period' => 'month']) }}"
               class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ $period === 'month' ? 'bg-brand-500 text-white' : 'text-content-secondary hover:text-content hover:bg-surface-secondary' }}">
                Month
            </a>
            <a href="{{ route('admin.analytics.profit-loss', ['period' => 'quarter']) }}"
               class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ $period === 'quarter' ? 'bg-brand-500 text-white' : 'text-content-secondary hover:text-content hover:bg-surface-secondary' }}">
                Quarter
            </a>
            <a href="{{ route('admin.analytics.profit-loss', ['period' => 'year']) }}"
               class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ $period === 'year' ? 'bg-brand-500 text-white' : 'text-content-secondary hover:text-content hover:bg-surface-secondary' }}">
                Year
            </a>
        </div>
    </div>

    {{-- P&L Table --}}
    <div class="bg-white rounded-xl border border-surface-border-light shadow-card">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-surface-border">
                        <th class="text-left px-6 py-4 text-xs font-semibold text-content-secondary uppercase tracking-wide w-1/2">Particulars</th>
                        <th class="text-right px-6 py-4 text-xs font-semibold text-content-secondary uppercase tracking-wide">Current Period</th>
                        <th class="text-right px-6 py-4 text-xs font-semibold text-content-secondary uppercase tracking-wide">Previous Period</th>
                        <th class="text-right px-6 py-4 text-xs font-semibold text-content-secondary uppercase tracking-wide">Change (Amount)</th>
                        <th class="text-right px-6 py-4 text-xs font-semibold text-content-secondary uppercase tracking-wide">Change (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $rows = [
                            ['label' => 'Revenue (from Orders)', 'current' => $currentPnl['revenue'], 'prev' => $prevPnl['revenue'], 'bold' => false, 'type' => 'income'],
                            ['label' => '(-) Cost of Goods Sold', 'current' => $currentPnl['cogs'], 'prev' => $prevPnl['cogs'], 'bold' => false, 'type' => 'expense'],
                            ['label' => 'Gross Profit', 'current' => $currentPnl['gross_profit'], 'prev' => $prevPnl['gross_profit'], 'bold' => true, 'type' => 'subtotal'],
                            ['label' => '(-) Marketplace Commission', 'current' => $currentPnl['commission'], 'prev' => $prevPnl['commission'], 'bold' => false, 'type' => 'expense'],
                            ['label' => '(-) Shipping Costs', 'current' => $currentPnl['shipping'], 'prev' => $prevPnl['shipping'], 'bold' => false, 'type' => 'expense'],
                            ['label' => '(-) Return/RTO Costs', 'current' => $currentPnl['returns'], 'prev' => $prevPnl['returns'], 'bold' => false, 'type' => 'expense'],
                            ['label' => '(-) Penalties & Deductions', 'current' => $currentPnl['penalties'], 'prev' => $prevPnl['penalties'], 'bold' => false, 'type' => 'expense'],
                            ['label' => '(-) Other Expenses', 'current' => $currentPnl['other'], 'prev' => $prevPnl['other'], 'bold' => false, 'type' => 'expense'],
                            ['label' => 'Net Profit', 'current' => $currentPnl['net_profit'], 'prev' => $prevPnl['net_profit'], 'bold' => true, 'type' => 'total'],
                        ];
                    @endphp

                    @foreach($rows as $row)
                        @php
                            $changeAmt = $row['current'] - $row['prev'];
                            $changePct = $row['prev'] != 0 ? round(($changeAmt / abs($row['prev'])) * 100, 1) : 0;
                            $isExpense = $row['type'] === 'expense';
                            $changeIsGood = $isExpense ? $changeAmt <= 0 : $changeAmt >= 0;
                        @endphp
                        <tr class="{{ $row['bold'] ? 'border-t-2 border-b border-surface-border bg-surface-secondary/30' : 'border-b border-surface-border-light' }} hover:bg-surface-secondary/50 transition-colors">
                            <td class="px-6 py-3 {{ $row['bold'] ? 'font-semibold text-content' : 'text-content' }} {{ $row['type'] === 'total' ? 'text-base' : '' }}">
                                {{ $row['label'] }}
                            </td>
                            <td class="px-6 py-3 text-right {{ $row['bold'] ? 'font-semibold' : '' }} {{ $row['type'] === 'total' ? ($row['current'] >= 0 ? 'text-success-600 text-base' : 'text-danger-600 text-base') : 'text-content' }}">
                                &#8377;{{ number_format(abs($row['current']), 2) }}
                            </td>
                            <td class="px-6 py-3 text-right text-content-secondary {{ $row['bold'] ? 'font-semibold' : '' }}">
                                &#8377;{{ number_format(abs($row['prev']), 2) }}
                            </td>
                            <td class="px-6 py-3 text-right {{ $changeIsGood ? 'text-success-500' : 'text-danger-500' }} {{ $row['bold'] ? 'font-semibold' : '' }}">
                                @if($changeAmt != 0)
                                    <span class="inline-flex items-center gap-0.5">
                                        @if($changeAmt > 0)
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        @else
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        @endif
                                        &#8377;{{ number_format(abs($changeAmt), 0) }}
                                    </span>
                                @else
                                    <span class="text-content-muted">&mdash;</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right {{ $row['bold'] ? 'font-semibold' : '' }}">
                                @if($changePct != 0)
                                    <span class="inline-flex items-center gap-0.5 text-xs font-semibold px-2 py-0.5 rounded-md {{ $changeIsGood ? 'bg-success-50 text-success-600' : 'bg-danger-50 text-danger-600' }}">
                                        @if($changePct > 0)
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        @else
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        @endif
                                        {{ abs($changePct) }}%
                                    </span>
                                @else
                                    <span class="text-content-muted">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    {{-- Profit Margin --}}
                    @php
                        $currentMargin = $currentPnl['revenue'] > 0 ? round(($currentPnl['net_profit'] / $currentPnl['revenue']) * 100, 2) : 0;
                        $prevMargin = $prevPnl['revenue'] > 0 ? round(($prevPnl['net_profit'] / $prevPnl['revenue']) * 100, 2) : 0;
                        $marginDiff = round($currentMargin - $prevMargin, 2);
                    @endphp
                    <tr class="border-t-2 border-surface-border bg-surface-secondary/50">
                        <td class="px-6 py-3 font-semibold text-content text-base">Profit Margin</td>
                        <td class="px-6 py-3 text-right font-bold text-base {{ $currentMargin >= 0 ? 'text-success-600' : 'text-danger-600' }}">{{ $currentMargin }}%</td>
                        <td class="px-6 py-3 text-right font-semibold text-content-secondary">{{ $prevMargin }}%</td>
                        <td colspan="2" class="px-6 py-3 text-right">
                            @if($marginDiff != 0)
                                <span class="inline-flex items-center gap-0.5 text-sm font-semibold {{ $marginDiff >= 0 ? 'text-success-500' : 'text-danger-500' }}">
                                    @if($marginDiff > 0)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    @endif
                                    {{ abs($marginDiff) }} percentage points
                                </span>
                            @else
                                <span class="text-content-muted">No change</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Order Metrics --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-6">
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="font-display text-2xl font-bold text-content">{{ number_format($currentPnl['orders']) }}</p>
            <p class="text-xs text-content-secondary mt-0.5">Total Orders</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="font-display text-2xl font-bold text-content">{{ number_format($currentPnl['units']) }}</p>
            <p class="text-xs text-content-secondary mt-0.5">Total Units Sold</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="font-display text-2xl font-bold text-content">
                &#8377;{{ $currentPnl['orders'] > 0 ? number_format($currentPnl['revenue'] / $currentPnl['orders'], 0) : '0' }}
            </p>
            <p class="text-xs text-content-secondary mt-0.5">Average Order Value</p>
        </div>
    </div>
</x-admin-layout>
