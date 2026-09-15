<x-admin-layout>
    <x-slot name="title">SKU Matcher</x-slot>
    <x-slot name="header">
        <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('admin.ai.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">AI Center</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-content">SKU Matcher</span>
    </x-slot>

    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="font-display text-xl font-bold text-content">SKU Matcher</h1>
        <p class="text-xs text-content-secondary mt-0.5">Intelligent SKU matching with confidence-scored suggestions</p>
    </div>

    {{-- Search Form --}}
    <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-6 mb-6">
        <form method="POST" action="{{ route('admin.ai.match-sku') }}">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-content-secondary mb-1">External SKU <span class="text-danger-500">*</span></label>
                    <input type="text" name="external_sku" value="{{ old('external_sku', request('external_sku')) }}" placeholder="e.g. SKD-MOON-BLACK-L" class="w-full rounded-lg border-surface-border text-sm py-2 px-3" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-content-secondary mb-1">Product Name</label>
                    <input type="text" name="product_name" value="{{ old('product_name') }}" placeholder="Optional" class="w-full rounded-lg border-surface-border text-sm py-2 px-3">
                </div>
                <div class="flex gap-3">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-content-secondary mb-1">Color</label>
                        <input type="text" name="color" value="{{ old('color') }}" placeholder="e.g. Black" class="w-full rounded-lg border-surface-border text-sm py-2 px-3">
                    </div>
                    <div class="w-20">
                        <label class="block text-xs font-medium text-content-secondary mb-1">Size</label>
                        <input type="text" name="size" value="{{ old('size') }}" placeholder="e.g. L" class="w-full rounded-lg border-surface-border text-sm py-2 px-3">
                    </div>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-5 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    Find Matches
                </button>
            </div>
        </form>
    </div>

    {{-- Match Results --}}
    @isset($result)
        <div class="bg-white rounded-xl border border-surface-border-light shadow-card mb-6">
            <div class="px-4 py-3 border-b border-surface-border-light flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                    <h2 class="text-sm font-semibold text-content">Match Results for "{{ $result['external_sku'] }}"</h2>
                </div>
                <span class="text-[10px] text-content-muted">{{ $result['processing_ms'] ?? 0 }}ms | {{ $result['model_used'] ?? '' }}</span>
            </div>

            @if(empty($result['matches']))
                <div class="p-6 text-center">
                    <div class="w-10 h-10 rounded-full bg-warning-50 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    </div>
                    <p class="text-sm text-content-secondary">No matches found</p>
                    <p class="text-xs text-content-muted mt-0.5">Try with different search terms or add more details</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-surface-border-light">
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">SKU Code</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Name</th>
                                <th class="text-center px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Confidence</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Strategy</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Reason</th>
                                <th class="px-4 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border-light">
                            @foreach($result['matches'] as $match)
                                @php
                                    $confPct = round($match['confidence'] * 100);
                                    $confColor = $confPct >= 80 ? 'bg-success-500' : ($confPct >= 50 ? 'bg-warning-500' : 'bg-danger-500');
                                    $confText = $confPct >= 80 ? 'text-success-600' : ($confPct >= 50 ? 'text-warning-600' : 'text-danger-600');
                                @endphp
                                <tr class="hover:bg-surface-secondary/50 transition-colors">
                                    <td class="px-4 py-3 font-mono text-xs font-semibold text-content">{{ $match['sku_code'] }}</td>
                                    <td class="px-4 py-3 text-content-secondary text-xs">{{ $match['sku_name'] }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-2">
                                            <div class="w-16 h-1.5 bg-surface-secondary rounded-full">
                                                <div class="h-1.5 rounded-full {{ $confColor }}" style="width: {{ $confPct }}%"></div>
                                            </div>
                                            <span class="text-xs font-semibold {{ $confText }}">{{ $confPct }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-surface-secondary text-content-secondary">{{ str_replace('_', ' ', $match['strategy']) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-content-secondary">{{ Str::limit($match['reason'], 50) }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.sku-mappings.create', ['sku_id' => $match['sku_id'], 'marketplace_sku' => $result['external_sku']]) }}" class="text-xs text-brand-500 hover:text-brand-600 font-semibold transition-colors inline-flex items-center gap-0.5">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Map
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endisset

    {{-- Recent Matching Activity --}}
    <section>
        <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">Recent Matching Activity</h2>
        <div class="bg-white rounded-xl border border-surface-border-light shadow-card">
            @if($recentTasks->isEmpty())
                <div class="p-6 text-center">
                    <p class="text-sm text-content-secondary">No recent SKU matching activity</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-surface-border-light">
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">External SKU</th>
                                <th class="text-center px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Status</th>
                                <th class="text-center px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Matches</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">Time</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-content-secondary uppercase tracking-wide">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border-light">
                            @foreach($recentTasks as $task)
                                @php
                                    $statusBg = match($task->status) {
                                        'completed' => 'bg-success-50 text-success-600',
                                        'failed'    => 'bg-danger-50 text-danger-600',
                                        'processing' => 'bg-warning-50 text-warning-600',
                                        default     => 'bg-neutral-50 text-neutral-600',
                                    };
                                    $matchCount = count($task->output_data['matches'] ?? []);
                                @endphp
                                <tr class="hover:bg-surface-secondary/50 transition-colors">
                                    <td class="px-4 py-2.5 font-mono text-xs text-content">{{ $task->input_data['external_sku'] ?? '-' }}</td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $statusBg }}">{{ ucfirst($task->status) }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-content-secondary">{{ $matchCount }}</td>
                                    <td class="px-4 py-2.5 text-right text-xs text-content-muted">{{ $task->processing_time_ms ?? '-' }}ms</td>
                                    <td class="px-4 py-2.5 text-right text-xs text-content-muted">{{ $task->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
</x-admin-layout>
