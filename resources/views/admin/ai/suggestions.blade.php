<x-admin-layout>
    <x-slot name="title">AI Suggestions</x-slot>
    <x-slot name="header">
        <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('admin.ai.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">AI Center</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-content">Suggestions</span>
    </x-slot>

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="font-display text-xl font-bold text-content">AI Suggestions</h1>
            <p class="text-xs text-content-secondary mt-0.5">Review and act on AI-generated recommendations</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-4 mb-6">
        <form method="GET" action="{{ route('admin.ai.suggestions') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-content-secondary mb-1">Type</label>
                <select name="type" class="rounded-lg border-surface-border text-sm py-1.5 px-3 min-w-[140px]">
                    <option value="">All Types</option>
                    @foreach(\App\Models\AiSuggestion::TYPE_LABELS as $val => $label)
                        <option value="{{ $val }}" {{ request('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-content-secondary mb-1">Status</label>
                <select name="status" class="rounded-lg border-surface-border text-sm py-1.5 px-3 min-w-[120px]">
                    <option value="">All</option>
                    @foreach(\App\Models\AiSuggestion::STATUSES as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-content-secondary mb-1">Priority</label>
                <select name="priority" class="rounded-lg border-surface-border text-sm py-1.5 px-3 min-w-[120px]">
                    <option value="">All</option>
                    @foreach(\App\Models\AiSuggestion::PRIORITIES as $p)
                        <option value="{{ $p }}" {{ request('priority') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-1.5 text-sm font-semibold transition-colors">
                Filter
            </button>
            @if(request()->hasAny(['type', 'status', 'priority']))
                <a href="{{ route('admin.ai.suggestions') }}" class="text-sm text-content-secondary hover:text-content transition-colors">Clear</a>
            @endif
        </form>
    </div>

    {{-- Suggestions List --}}
    <div class="space-y-3">
        @forelse($suggestions as $suggestion)
            @php
                $borderColor = match($suggestion->priority) {
                    'critical' => 'border-l-danger-500',
                    'high'     => 'border-l-warning-500',
                    'medium'   => 'border-l-brand-500',
                    default    => 'border-l-neutral-300',
                };
                $priorityBg = match($suggestion->priority) {
                    'critical' => 'bg-danger-50 text-danger-600',
                    'high'     => 'bg-warning-50 text-warning-600',
                    'medium'   => 'bg-brand-50 text-brand-600',
                    default    => 'bg-neutral-50 text-neutral-600',
                };
                $statusBg = match($suggestion->status) {
                    'accepted' => 'bg-success-50 text-success-600',
                    'rejected' => 'bg-danger-50 text-danger-600',
                    'expired'  => 'bg-neutral-50 text-neutral-500',
                    default    => 'bg-brand-50 text-brand-600',
                };
                $confColor = $suggestion->confidence >= 0.80 ? 'bg-success-500' : ($suggestion->confidence >= 0.50 ? 'bg-warning-500' : 'bg-danger-500');

                // Icons by suggestion type
                $typeIcon = match($suggestion->suggestion_type) {
                    'sku_mapping'         => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>',
                    'reorder'             => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                    'price_adjustment'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    'quality_alert'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>',
                    'process_improvement' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.42 15.17l-5.658 5.658a2.121 2.121 0 01-3-3L8.42 12.17m7.17-7.17l5.658-5.658a2.121 2.121 0 013 3L18.59 7.83m-7.17 7.17l7.17-7.17"/>',
                    'anomaly'             => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
                    default               => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>',
                };
            @endphp
            <div class="bg-white rounded-xl border border-surface-border-light shadow-card border-l-2 {{ $borderColor }}">
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        {{-- Type Icon --}}
                        <div class="w-9 h-9 rounded-lg bg-surface-secondary flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4.5 h-4.5 text-content-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $typeIcon !!}</svg>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $priorityBg }}">{{ ucfirst($suggestion->priority) }}</span>
                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $statusBg }}">{{ ucfirst($suggestion->status) }}</span>
                                <span class="text-[10px] text-content-muted">{{ $suggestion->type_label }}</span>
                            </div>
                            <p class="text-sm font-medium text-content">{{ $suggestion->title }}</p>
                            <p class="text-xs text-content-secondary mt-0.5">{{ $suggestion->description }}</p>

                            {{-- Confidence bar --}}
                            <div class="flex items-center gap-2 mt-2">
                                <span class="text-[10px] text-content-muted">Confidence</span>
                                <div class="flex-1 h-1.5 bg-surface-secondary rounded-full max-w-[120px]">
                                    <div class="h-1.5 rounded-full {{ $confColor }}" style="width: {{ $suggestion->confidence_percent }}%"></div>
                                </div>
                                <span class="text-xs font-semibold text-content">{{ $suggestion->confidence_percent }}%</span>
                            </div>

                            <p class="text-[10px] text-content-muted mt-1.5">{{ $suggestion->created_at->diffForHumans() }}</p>
                        </div>

                        {{-- Actions --}}
                        @if($suggestion->status === 'pending')
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <form method="POST" action="{{ route('admin.ai.accept-suggestion', $suggestion) }}">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-success-50 hover:bg-success-100 text-success-600 text-xs font-semibold transition-colors inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Accept
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.ai.reject-suggestion', $suggestion) }}">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-danger-50 hover:bg-danger-100 text-danger-600 text-xs font-semibold transition-colors inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Reject
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-surface-border-light p-8 text-center">
                <div class="w-12 h-12 rounded-full bg-surface-secondary flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                </div>
                <p class="text-sm text-content-secondary">No suggestions found</p>
                <p class="text-xs text-content-muted mt-0.5">Run anomaly detection or adjust filters</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($suggestions->hasPages())
        <div class="mt-6">
            {{ $suggestions->links() }}
        </div>
    @endif
</x-admin-layout>
