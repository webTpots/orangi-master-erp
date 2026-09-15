<x-admin-layout>
    <x-slot name="title">Marketplace Performance</x-slot>
    <x-slot name="header">
        <a href="{{ route('admin.analytics.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Analytics</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-content">Marketplace Performance</span>
    </x-slot>

    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="font-display text-xl font-bold text-content">Marketplace Performance</h1>
        <p class="text-xs text-content-secondary mt-0.5">This month &mdash; Side-by-side marketplace comparison</p>
    </div>

    @if($marketplaceData->isEmpty())
        <div class="bg-white rounded-xl border border-surface-border-light p-12 text-center">
            <div class="w-12 h-12 rounded-full bg-neutral-50 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <p class="text-sm text-content-secondary font-medium">No marketplace data available</p>
            <p class="text-xs text-content-muted mt-1">Data will appear as orders and settlements are processed</p>
        </div>
    @else
        {{-- Comparison Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-{{ min($marketplaceData->count(), 3) }} gap-6">
            @php
                // Find best values for each metric
                $bestOrders = $marketplaceData->max('orders');
                $bestRevenue = $marketplaceData->max('revenue');
                $lowestCommission = $marketplaceData->min('commission_pct');
                $lowestReturn = $marketplaceData->min('return_pct');
                $bestProfit = $marketplaceData->max('net_profit');
            @endphp

            @foreach($marketplaceData as $mp)
                @php $account = $accounts[$mp->marketplace_account_id] ?? null; @endphp
                <div class="bg-white rounded-xl border border-surface-border-light shadow-card overflow-hidden">
                    {{-- Header --}}
                    <div class="px-5 py-4 border-b border-surface-border-light bg-surface-secondary/30">
                        <h3 class="font-display text-lg font-bold text-content">{{ $account->account_name ?? 'Unknown' }}</h3>
                        <p class="text-xs text-content-secondary mt-0.5">{{ $account->marketplace->name ?? '' }}</p>
                    </div>

                    {{-- Metrics --}}
                    <div class="divide-y divide-surface-border-light">
                        {{-- Orders --}}
                        <div class="px-5 py-3 flex items-center justify-between">
                            <span class="text-sm text-content-secondary">Orders</span>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-content">{{ number_format($mp->orders) }}</span>
                                @if($mp->orders == $bestOrders && $marketplaceData->count() > 1)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-success-50 text-success-600">BEST</span>
                                @endif
                            </div>
                        </div>

                        {{-- Revenue --}}
                        <div class="px-5 py-3 flex items-center justify-between">
                            <span class="text-sm text-content-secondary">Revenue</span>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-content">&#8377;{{ number_format($mp->revenue, 0) }}</span>
                                @if($mp->revenue == $bestRevenue && $marketplaceData->count() > 1)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-success-50 text-success-600">BEST</span>
                                @endif
                            </div>
                        </div>

                        {{-- Commission --}}
                        <div class="px-5 py-3 flex items-center justify-between">
                            <span class="text-sm text-content-secondary">Commission</span>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-content">&#8377;{{ number_format($mp->commission, 0) }}</span>
                                <span class="text-xs text-content-muted">({{ $mp->commission_pct }}%)</span>
                                @if($mp->commission_pct == $lowestCommission && $marketplaceData->count() > 1)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-success-50 text-success-600">BEST</span>
                                @endif
                            </div>
                        </div>

                        {{-- Returns --}}
                        <div class="px-5 py-3 flex items-center justify-between">
                            <span class="text-sm text-content-secondary">Returns</span>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-content">{{ number_format($mp->returns) }}</span>
                                <span class="text-xs px-1.5 py-0.5 rounded font-semibold {{ $mp->return_pct > 10 ? 'bg-danger-50 text-danger-500' : 'bg-success-50 text-success-500' }}">{{ $mp->return_pct }}%</span>
                                @if($mp->return_pct == $lowestReturn && $marketplaceData->count() > 1)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-success-50 text-success-600">BEST</span>
                                @endif
                            </div>
                        </div>

                        {{-- RTO --}}
                        <div class="px-5 py-3 flex items-center justify-between">
                            <span class="text-sm text-content-secondary">RTO Rate</span>
                            <span class="text-sm {{ $mp->rto_pct ?? 0 > 5 ? 'text-danger-500' : 'text-content' }}">
                                {{ $mp->rto_pct ?? 0 }}%
                            </span>
                        </div>

                        {{-- Avg Delivery Days --}}
                        <div class="px-5 py-3 flex items-center justify-between">
                            <span class="text-sm text-content-secondary">Avg Delivery Days</span>
                            <span class="text-sm text-content">{{ $mp->avg_delivery_days ?? '-' }}</span>
                        </div>

                        {{-- Net Profit --}}
                        <div class="px-5 py-4 flex items-center justify-between bg-surface-secondary/30">
                            <span class="text-sm font-semibold text-content">Net Profit</span>
                            <div class="flex items-center gap-2">
                                <span class="text-base font-bold {{ $mp->net_profit >= 0 ? 'text-success-600' : 'text-danger-600' }}">&#8377;{{ number_format($mp->net_profit, 0) }}</span>
                                @if($mp->net_profit == $bestProfit && $marketplaceData->count() > 1)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-success-50 text-success-600">BEST</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-admin-layout>
