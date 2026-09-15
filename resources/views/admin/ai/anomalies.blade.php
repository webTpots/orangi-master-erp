<x-admin-layout>
    <x-slot name="title">Anomaly Detection</x-slot>
    <x-slot name="header">
        <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('admin.ai.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">AI Center</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-content">Anomalies</span>
    </x-slot>

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="font-display text-xl font-bold text-content">Anomaly Detection</h1>
            <p class="text-xs text-content-secondary mt-0.5">
                @if($lastRun)
                    Last run: {{ $lastRun->processed_at?->diffForHumans() ?? $lastRun->created_at->diffForHumans() }}
                @else
                    No detection run yet
                @endif
            </p>
        </div>
        <form method="POST" action="{{ route('admin.ai.run-detection') }}">
            @csrf
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-5 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                Run Detection
            </button>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-surface-border-light p-4 text-center">
            <p class="font-display text-2xl font-bold {{ $anomalySummary['total'] > 0 ? 'text-danger-500' : 'text-content' }}">{{ $anomalySummary['total'] }}</p>
            <p class="text-xs text-content-secondary mt-0.5">Total</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4 text-center">
            <p class="font-display text-2xl font-bold {{ $anomalySummary['critical'] > 0 ? 'text-danger-500' : 'text-content' }}">{{ $anomalySummary['critical'] }}</p>
            <p class="text-xs text-content-secondary mt-0.5">Critical</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4 text-center">
            <p class="font-display text-2xl font-bold {{ $anomalySummary['high'] > 0 ? 'text-warning-500' : 'text-content' }}">{{ $anomalySummary['high'] }}</p>
            <p class="text-xs text-content-secondary mt-0.5">High</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4 text-center">
            <p class="font-display text-2xl font-bold text-content">{{ $anomalySummary['medium'] }}</p>
            <p class="text-xs text-content-secondary mt-0.5">Medium</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4 text-center">
            <p class="font-display text-2xl font-bold text-content">{{ $anomalySummary['low'] }}</p>
            <p class="text-xs text-content-secondary mt-0.5">Low</p>
        </div>
    </div>

    {{-- Anomalies by Category --}}
    @php
        $categories = [
            'order'      => ['label' => 'Order Anomalies', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>'],
            'inventory'  => ['label' => 'Inventory Anomalies', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>'],
            'settlement' => ['label' => 'Settlement Anomalies', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>'],
            'return'     => ['label' => 'Return Anomalies', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/>'],
        ];

        // Group anomalies by category
        $grouped = [];
        foreach ($anomalySummary['anomalies'] as $anomaly) {
            $cat = $anomaly['type'];
            // Map anomaly types to categories
            if (in_array($cat, ['high_quantity', 'price_below_cost'])) $cat = 'order';
            elseif (in_array($cat, ['negative_stock', 'over_reserved'])) $cat = 'inventory';
            elseif (in_array($cat, ['unmatched_settlements', 'settlement_mismatch'])) $cat = 'settlement';
            elseif (in_array($cat, ['high_return_rate'])) $cat = 'return';
            $grouped[$cat][] = $anomaly;
        }
    @endphp

    @if(empty($anomalySummary['anomalies']))
        <div class="bg-white rounded-xl border border-surface-border-light p-8 text-center">
            <div class="w-12 h-12 rounded-full bg-success-50 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <p class="text-sm font-medium text-content">All Clear</p>
            <p class="text-xs text-content-muted mt-0.5">No anomalies detected. Your operations are running smoothly.</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($categories as $catKey => $catInfo)
                @if(isset($grouped[$catKey]) && count($grouped[$catKey]) > 0)
                    <section>
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-content-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $catInfo['icon'] !!}</svg>
                            <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide">{{ $catInfo['label'] }}</h2>
                            <span class="text-xs bg-surface-secondary text-content-muted px-1.5 py-0.5 rounded">{{ count($grouped[$catKey]) }}</span>
                        </div>
                        <div class="space-y-2">
                            @foreach($grouped[$catKey] as $anomaly)
                                @php
                                    $sevBorder = match($anomaly['severity']) {
                                        'critical' => 'border-l-danger-500',
                                        'high'     => 'border-l-warning-500',
                                        'medium'   => 'border-l-brand-500',
                                        default    => 'border-l-neutral-300',
                                    };
                                    $sevBadge = match($anomaly['severity']) {
                                        'critical' => 'bg-danger-50 text-danger-600',
                                        'high'     => 'bg-warning-50 text-warning-600',
                                        'medium'   => 'bg-brand-50 text-brand-600',
                                        default    => 'bg-neutral-50 text-neutral-600',
                                    };
                                @endphp
                                <div class="bg-white rounded-xl border border-surface-border-light shadow-card border-l-2 {{ $sevBorder }} p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $sevBadge }}">{{ ucfirst($anomaly['severity']) }}</span>
                                            </div>
                                            <p class="text-sm font-medium text-content">{{ $anomaly['title'] }}</p>
                                            <p class="text-xs text-content-secondary mt-0.5">{{ $anomaly['description'] }}</p>
                                            @if(!empty($anomaly['action']))
                                                <p class="text-xs text-brand-500 mt-1.5 font-medium">
                                                    <svg class="w-3 h-3 inline mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                                    {{ $anomaly['action'] }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </div>
    @endif
</x-admin-layout>
