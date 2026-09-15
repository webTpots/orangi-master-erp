<x-admin-layout>
    <x-slot:title>Marketplace Comparison</x-slot:title>

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.marketplace-config.index') }}" class="text-content-secondary hover:text-content transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-lg font-display font-semibold text-content">Marketplace Comparison</h1>
                <p class="text-sm text-content-secondary mt-0.5">Side-by-side comparison of all marketplace configurations</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-surface-border">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-surface-border-light">
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider sticky left-0 bg-white">Metric</th>
                        @foreach($comparison as $mp)
                            <th class="text-center px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider min-w-[160px]">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-6 h-6 rounded bg-brand-500/10 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349"/></svg>
                                    </div>
                                    {{ $mp['marketplace_name'] }}
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border-light">
                    @php
                        $metrics = [
                            'commission_rate' => ['label' => 'Commission Rate', 'suffix' => '%', 'lower_better' => true],
                            'return_window_days' => ['label' => 'Return Window', 'suffix' => ' days', 'lower_better' => true],
                            'cutoff_time' => ['label' => 'Cutoff Time', 'suffix' => '', 'lower_better' => false],
                            'avg_delivery_days' => ['label' => 'Avg Delivery', 'suffix' => ' days', 'lower_better' => true],
                            'sla_days' => ['label' => 'SLA Days', 'suffix' => ' days', 'lower_better' => false],
                            'payment_cycle_days' => ['label' => 'Payment Cycle', 'suffix' => ' days', 'lower_better' => true],
                        ];
                    @endphp

                    @foreach($metrics as $key => $metric)
                        @php
                            $values = collect($comparison)->pluck($key)->filter(fn($v) => $v !== '-' && $v !== null)->map(fn($v) => (float) $v);
                            $best = $metric['lower_better'] ? $values->min() : $values->max();
                            $worst = $metric['lower_better'] ? $values->max() : $values->min();
                        @endphp
                        <tr class="hover:bg-surface-secondary/50 transition-colors">
                            <td class="px-5 py-3 font-medium text-content sticky left-0 bg-white">{{ $metric['label'] }}</td>
                            @foreach($comparison as $mp)
                                @php
                                    $val = $mp[$key];
                                    $numVal = is_numeric($val) ? (float) $val : null;
                                    $isBest = $numVal !== null && $numVal == $best && $values->count() > 1;
                                    $isWorst = $numVal !== null && $numVal == $worst && $values->count() > 1;
                                @endphp
                                <td class="px-5 py-3 text-center">
                                    <span class="{{ $isBest ? 'text-success-600 font-semibold' : ($isWorst ? 'text-danger-600' : 'text-content') }}">
                                        {{ $val !== '-' ? $val . $metric['suffix'] : '-' }}
                                    </span>
                                    @if($isBest)
                                        <svg class="w-3.5 h-3.5 text-success-500 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Legend --}}
    <div class="mt-4 flex items-center gap-6 text-xs text-content-secondary">
        <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-full bg-success-500/20 border border-success-500"></span>
            <span>Best value</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-full bg-danger-500/20 border border-danger-500"></span>
            <span>Needs attention</span>
        </div>
    </div>
</x-admin-layout>
