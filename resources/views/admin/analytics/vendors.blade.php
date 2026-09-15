<x-admin-layout>
    <x-slot name="title">Vendor Scorecard</x-slot>
    <x-slot name="header">
        <a href="{{ route('admin.analytics.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Analytics</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-content">Vendor Scorecard</span>
    </x-slot>

    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="font-display text-xl font-bold text-content">Vendor Scorecard</h1>
        <p class="text-xs text-content-secondary mt-0.5">This month &mdash; Vendor reliability and quality metrics</p>
    </div>

    @if($vendorData->isEmpty() && $vendors->isEmpty())
        <div class="bg-white rounded-xl border border-surface-border-light p-12 text-center">
            <div class="w-12 h-12 rounded-full bg-neutral-50 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <p class="text-sm text-content-secondary font-medium">No vendor data available</p>
            <p class="text-xs text-content-muted mt-1">Add vendors and create purchase orders to see performance data</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($vendorData as $vp)
                @php
                    $vendor = $vp->vendor;
                    $fulfillColor = $vp->fulfillment_rate >= 90 ? 'success' : ($vp->fulfillment_rate >= 70 ? 'warning' : 'danger');
                    $onTimeColor = $vp->on_time_delivery_rate >= 85 ? 'success' : ($vp->on_time_delivery_rate >= 60 ? 'warning' : 'danger');
                    $qualityColor = $vp->quality_return_rate <= 3 ? 'success' : ($vp->quality_return_rate <= 8 ? 'warning' : 'danger');
                    // Overall score
                    $score = round(($vp->fulfillment_rate * 0.4) + ($vp->on_time_delivery_rate * 0.3) + ((100 - $vp->quality_return_rate) * 0.3));
                    $scoreColor = $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger');
                @endphp
                <div class="bg-white rounded-xl border border-surface-border-light shadow-card overflow-hidden">
                    {{-- Header --}}
                    <div class="px-5 py-4 border-b border-surface-border-light flex items-center justify-between">
                        <div>
                            <h3 class="font-display text-base font-bold text-content">{{ $vendor->name ?? 'Unknown' }}</h3>
                            <p class="text-xs text-content-secondary mt-0.5">{{ $vendor->city ?? '' }}{{ $vendor->city && $vendor->state ? ', ' : '' }}{{ $vendor->state ?? '' }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-{{ $scoreColor }}-50 flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-bold text-{{ $scoreColor }}-600">{{ $score }}</span>
                        </div>
                    </div>

                    {{-- Metrics --}}
                    <div class="divide-y divide-surface-border-light">
                        <div class="px-5 py-3 flex items-center justify-between">
                            <span class="text-sm text-content-secondary">Total POs</span>
                            <span class="text-sm font-semibold text-content">{{ $vp->total_pos }}</span>
                        </div>
                        <div class="px-5 py-3 flex items-center justify-between">
                            <span class="text-sm text-content-secondary">Fulfillment Rate</span>
                            <span class="text-sm font-semibold px-2 py-0.5 rounded-md bg-{{ $fulfillColor }}-50 text-{{ $fulfillColor }}-600">{{ $vp->fulfillment_rate }}%</span>
                        </div>
                        <div class="px-5 py-3 flex items-center justify-between">
                            <span class="text-sm text-content-secondary">Avg Lead Time</span>
                            <span class="text-sm text-content">{{ $vp->avg_lead_time_days }} days</span>
                        </div>
                        <div class="px-5 py-3 flex items-center justify-between">
                            <span class="text-sm text-content-secondary">On-time Delivery</span>
                            <span class="text-sm font-semibold px-2 py-0.5 rounded-md bg-{{ $onTimeColor }}-50 text-{{ $onTimeColor }}-600">{{ $vp->on_time_delivery_rate }}%</span>
                        </div>
                        <div class="px-5 py-3 flex items-center justify-between">
                            <span class="text-sm text-content-secondary">Quality Return Rate</span>
                            <span class="text-sm font-semibold px-2 py-0.5 rounded-md bg-{{ $qualityColor }}-50 text-{{ $qualityColor }}-600">{{ $vp->quality_return_rate }}%</span>
                        </div>
                        <div class="px-5 py-3 flex items-center justify-between bg-surface-secondary/30">
                            <span class="text-sm font-semibold text-content">Total Amount</span>
                            <span class="text-sm font-bold text-content">&#8377;{{ number_format($vp->total_amount, 0) }}</span>
                        </div>
                    </div>
                </div>
            @empty
                {{-- Show vendors without performance data --}}
                @foreach($vendors as $vendor)
                    <div class="bg-white rounded-xl border border-surface-border-light shadow-card overflow-hidden">
                        <div class="px-5 py-4 border-b border-surface-border-light flex items-center justify-between">
                            <div>
                                <h3 class="font-display text-base font-bold text-content">{{ $vendor->name }}</h3>
                                <p class="text-xs text-content-secondary mt-0.5">{{ $vendor->city ?? '' }}{{ $vendor->city && $vendor->state ? ', ' : '' }}{{ $vendor->state ?? '' }}</p>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-neutral-50 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-neutral-400">--</span>
                            </div>
                        </div>
                        <div class="p-5 text-center">
                            <p class="text-sm text-content-secondary">No performance data yet</p>
                            <p class="text-xs text-content-muted mt-0.5">Create purchase orders to track this vendor</p>
                        </div>
                    </div>
                @endforeach
            @endforelse
        </div>

        {{-- Vendor Ranking Table --}}
        @if($vendorData->count() > 1)
            <section class="mt-6">
                <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">Vendor Ranking</h2>
                <div class="bg-white rounded-xl border border-surface-border-light shadow-card overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-surface-border">
                                <th class="text-left px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">#</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Vendor</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Score</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Fulfillment</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">On-time</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Quality</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border-light">
                            @foreach($vendorData->sortByDesc(fn($vp) => ($vp->fulfillment_rate * 0.4) + ($vp->on_time_delivery_rate * 0.3) + ((100 - $vp->quality_return_rate) * 0.3))->values() as $index => $vp)
                                @php
                                    $score = round(($vp->fulfillment_rate * 0.4) + ($vp->on_time_delivery_rate * 0.3) + ((100 - $vp->quality_return_rate) * 0.3));
                                    $scoreColor = $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger');
                                @endphp
                                <tr class="hover:bg-surface-secondary/50 transition-colors">
                                    <td class="px-4 py-2.5 text-content-muted">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2.5 font-medium text-content">{{ $vp->vendor->name ?? 'Unknown' }}</td>
                                    <td class="px-4 py-2.5 text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-{{ $scoreColor }}-50 text-{{ $scoreColor }}-600">{{ $score }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-content">{{ $vp->fulfillment_rate }}%</td>
                                    <td class="px-4 py-2.5 text-right text-content">{{ $vp->on_time_delivery_rate }}%</td>
                                    <td class="px-4 py-2.5 text-right text-content">{{ $vp->quality_return_rate }}%</td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-content">&#8377;{{ number_format($vp->total_amount, 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    @endif
</x-admin-layout>
