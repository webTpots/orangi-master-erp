<x-admin-layout>
    <x-slot name="title">AI Center</x-slot>
    <x-slot name="header">
        <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-content">AI Center</span>
    </x-slot>

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="font-display text-xl font-bold text-content">AI Center</h1>
            <p class="text-xs text-content-secondary mt-0.5">Intelligent insights and automation for your business</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.ai.suggestions') }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                All Suggestions
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <section class="mb-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Active Suggestions --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold text-content text-right">{{ $stats['active_suggestions'] }}</p>
                <p class="text-xs text-content-secondary mt-0.5">Active Suggestions</p>
            </div>

            {{-- Anomalies Detected --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-{{ $anomalySummary['total'] > 0 ? 'danger' : 'success' }}-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-{{ $anomalySummary['total'] > 0 ? 'danger' : 'success' }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold text-right {{ $anomalySummary['total'] > 0 ? 'text-danger-500' : 'text-content' }}">{{ $anomalySummary['total'] }}</p>
                <p class="text-xs text-content-secondary mt-0.5">Anomalies Detected</p>
                @if($anomalySummary['critical'] > 0)
                    <p class="text-xs text-danger-500 mt-0.5">{{ $anomalySummary['critical'] }} critical</p>
                @endif
            </div>

            {{-- Tasks Today --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-success-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold text-content text-right">{{ $stats['tasks_today'] }}</p>
                <p class="text-xs text-content-secondary mt-0.5">Tasks Today</p>
            </div>

            {{-- Total Processed --}}
            <div class="bg-white rounded-xl border border-surface-border-light p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="w-8 h-8 rounded-lg bg-neutral-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    </span>
                </div>
                <p class="font-display text-2xl font-bold text-content text-right">{{ $stats['tasks_processed'] }}</p>
                <p class="text-xs text-content-secondary mt-0.5">Tasks Processed</p>
            </div>
        </div>
    </section>

    {{-- Two columns: Suggestions + Anomalies --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- AI Suggestions --}}
        <section>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide">AI Suggestions</h2>
                <a href="{{ route('admin.ai.suggestions') }}" class="text-xs text-brand-500 hover:text-brand-600 font-medium transition-colors">View All</a>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light shadow-card">
                @if($activeSuggestions->isEmpty())
                    <div class="p-6 text-center">
                        <div class="w-10 h-10 rounded-full bg-success-50 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <p class="text-sm text-content-secondary">No active suggestions</p>
                        <p class="text-xs text-content-muted mt-0.5">Run anomaly detection to generate suggestions</p>
                    </div>
                @else
                    <div class="divide-y divide-surface-border-light">
                        @foreach($activeSuggestions as $suggestion)
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
                                $confColor = $suggestion->confidence >= 0.80 ? 'bg-success-500' : ($suggestion->confidence >= 0.50 ? 'bg-warning-500' : 'bg-danger-500');
                            @endphp
                            <div class="px-4 py-3 border-l-2 {{ $borderColor }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $priorityBg }}">{{ ucfirst($suggestion->priority) }}</span>
                                            <span class="text-[10px] text-content-muted">{{ $suggestion->type_label }}</span>
                                        </div>
                                        <p class="text-sm font-medium text-content">{{ $suggestion->title }}</p>
                                        <p class="text-xs text-content-secondary mt-0.5">{{ Str::limit($suggestion->description, 100) }}</p>
                                        {{-- Confidence bar --}}
                                        <div class="flex items-center gap-2 mt-1.5">
                                            <div class="flex-1 h-1 bg-surface-secondary rounded-full max-w-[80px]">
                                                <div class="h-1 rounded-full {{ $confColor }}" style="width: {{ $suggestion->confidence_percent }}%"></div>
                                            </div>
                                            <span class="text-[10px] text-content-muted">{{ $suggestion->confidence_percent }}%</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 flex-shrink-0">
                                        <form method="POST" action="{{ route('admin.ai.accept-suggestion', $suggestion) }}">
                                            @csrf
                                            <button type="submit" class="w-7 h-7 rounded-md bg-success-50 hover:bg-success-100 text-success-500 flex items-center justify-center transition-colors" title="Accept">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.ai.reject-suggestion', $suggestion) }}">
                                            @csrf
                                            <button type="submit" class="w-7 h-7 rounded-md bg-danger-50 hover:bg-danger-100 text-danger-500 flex items-center justify-center transition-colors" title="Reject">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- Anomalies Overview --}}
        <section>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide">Anomalies</h2>
                <a href="{{ route('admin.ai.anomalies') }}" class="text-xs text-brand-500 hover:text-brand-600 font-medium transition-colors">View All</a>
            </div>
            <div class="bg-white rounded-xl border border-surface-border-light shadow-card">
                @if(empty($anomalySummary['anomalies']))
                    <div class="p-6 text-center">
                        <div class="w-10 h-10 rounded-full bg-success-50 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <p class="text-sm text-content-secondary">No anomalies detected</p>
                        <p class="text-xs text-content-muted mt-0.5">Your operations look healthy</p>
                    </div>
                @else
                    <div class="divide-y divide-surface-border-light">
                        @foreach(array_slice($anomalySummary['anomalies'], 0, 5) as $anomaly)
                            @php
                                $sevColor = match($anomaly['severity']) {
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
                            <div class="px-4 py-3 border-l-2 {{ $sevColor }}">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $sevBadge }}">{{ ucfirst($anomaly['severity']) }}</span>
                                </div>
                                <p class="text-sm font-medium text-content">{{ $anomaly['title'] }}</p>
                                <p class="text-xs text-content-secondary mt-0.5">{{ Str::limit($anomaly['description'], 120) }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>

    {{-- Quick Actions --}}
    <section>
        <h2 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-3">Quick Actions</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {{-- Run Anomaly Detection --}}
            <form method="POST" action="{{ route('admin.ai.run-detection') }}">
                @csrf
                <button type="submit" class="w-full bg-white rounded-xl border border-surface-border-light p-5 hover:border-brand-500/30 hover:shadow-sm transition-all text-left group">
                    <div class="w-10 h-10 rounded-lg bg-danger-50 flex items-center justify-center mb-3 group-hover:bg-danger-100 transition-colors">
                        <svg class="w-5 h-5 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-content">Run Anomaly Detection</p>
                    <p class="text-xs text-content-secondary mt-0.5">Scan orders, inventory, settlements & returns</p>
                </button>
            </form>

            {{-- Analyze Document --}}
            <a href="{{ route('admin.ai.document-analyzer') }}" class="bg-white rounded-xl border border-surface-border-light p-5 hover:border-brand-500/30 hover:shadow-sm transition-all group">
                <div class="w-10 h-10 rounded-lg bg-brand-50 flex items-center justify-center mb-3 group-hover:bg-brand-100 transition-colors">
                    <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                </div>
                <p class="text-sm font-semibold text-content">Analyze Document</p>
                <p class="text-xs text-content-secondary mt-0.5">Upload and classify documents with AI</p>
            </a>

            {{-- Match SKUs --}}
            <a href="{{ route('admin.ai.sku-matcher') }}" class="bg-white rounded-xl border border-surface-border-light p-5 hover:border-brand-500/30 hover:shadow-sm transition-all group">
                <div class="w-10 h-10 rounded-lg bg-success-50 flex items-center justify-center mb-3 group-hover:bg-success-100 transition-colors">
                    <svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                </div>
                <p class="text-sm font-semibold text-content">Match SKUs</p>
                <p class="text-xs text-content-secondary mt-0.5">Intelligent SKU matching with confidence scores</p>
            </a>
        </div>
    </section>
</x-admin-layout>
