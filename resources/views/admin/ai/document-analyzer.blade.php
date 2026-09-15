<x-admin-layout>
    <x-slot name="title">Document Analyzer</x-slot>
    <x-slot name="header">
        <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('admin.ai.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">AI Center</a>
        <svg class="w-3 h-3 text-content-muted mx-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-content">Document Analyzer</span>
    </x-slot>

    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="font-display text-xl font-bold text-content">Document Analyzer</h1>
        <p class="text-xs text-content-secondary mt-0.5">Upload documents for AI-powered classification and data extraction</p>
    </div>

    {{-- Upload Area --}}
    <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-6 mb-6">
        <form method="POST" action="{{ route('admin.ai.analyze-document') }}" enctype="multipart/form-data" x-data="{ fileName: '' }">
            @csrf
            <div class="border-2 border-dashed border-surface-border rounded-xl p-8 text-center hover:border-brand-500/40 transition-colors">
                <div class="w-12 h-12 rounded-full bg-brand-50 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                </div>
                <p class="text-sm font-medium text-content mb-1">Upload a document to analyze</p>
                <p class="text-xs text-content-muted mb-4">Supports PDF, CSV, XLSX, JPG, PNG (max 10MB)</p>
                <div class="flex items-center justify-center gap-3 flex-wrap">
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-danger-50 text-danger-600">PDF</span>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-success-50 text-success-600">CSV</span>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-brand-50 text-brand-600">XLSX</span>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-warning-50 text-warning-600">Image</span>
                </div>
                <div class="mt-4">
                    <label class="cursor-pointer inline-flex items-center gap-1.5 bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-5 py-2 text-sm font-semibold transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Choose File
                        <input type="file" name="document" class="hidden" @change="fileName = $event.target.files[0]?.name || ''" required>
                    </label>
                </div>
                <p x-show="fileName" x-text="fileName" class="text-xs text-content mt-2 font-medium"></p>
            </div>
            <div class="mt-4 flex justify-end">
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-5 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                    Analyze Document
                </button>
            </div>
        </form>
    </div>

    {{-- Analysis Results --}}
    @isset($result)
        <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-6">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                <h2 class="text-sm font-semibold text-content">Analysis Results</h2>
            </div>

            {{-- Detected Type --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="bg-surface-secondary rounded-lg p-3">
                    <p class="text-[10px] text-content-muted uppercase tracking-wide mb-0.5">Detected Document Type</p>
                    <p class="text-sm font-semibold text-content capitalize">{{ str_replace('_', ' ', $result['detected_type'] ?? 'Unknown') }}</p>
                </div>
                <div class="bg-surface-secondary rounded-lg p-3">
                    <p class="text-[10px] text-content-muted uppercase tracking-wide mb-0.5">File Info</p>
                    <p class="text-sm text-content">{{ $result['file_name'] ?? '' }} ({{ $result['file_size_human'] ?? '' }})</p>
                </div>
            </div>

            {{-- Confidence --}}
            @php
                $confPct = round(($result['confidence'] ?? 0) * 100);
                $confColor = $confPct >= 80 ? 'bg-success-500' : ($confPct >= 50 ? 'bg-warning-500' : 'bg-danger-500');
            @endphp
            <div class="mb-4">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs text-content-secondary">Confidence Score</span>
                    <span class="text-xs font-semibold text-content">{{ $confPct }}%</span>
                </div>
                <div class="h-2 bg-surface-secondary rounded-full">
                    <div class="h-2 rounded-full {{ $confColor }}" style="width: {{ $confPct }}%"></div>
                </div>
            </div>

            {{-- Extracted Data --}}
            @if(!empty($result['extracted_data']))
                <div class="border-t border-surface-border-light pt-4 mb-4">
                    <h3 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-2">Extracted Data</h3>
                    <div class="space-y-1.5">
                        @foreach($result['extracted_data'] as $key => $value)
                            <div class="flex items-center justify-between py-1">
                                <span class="text-xs text-content-secondary capitalize">{{ str_replace('_', ' ', $key) }}</span>
                                <span class="text-xs font-medium text-content">{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Suggested Action --}}
            @if(!empty($result['suggested_action']))
                <div class="bg-brand-50 rounded-lg p-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    <p class="text-xs text-brand-700"><span class="font-semibold">Suggested action:</span> {{ $result['suggested_action'] }}</p>
                </div>
            @endif

            <div class="mt-3 text-right">
                <span class="text-[10px] text-content-muted">Model: {{ $result['model_used'] ?? 'unknown' }} | Processed in {{ $result['processing_ms'] ?? 0 }}ms</span>
            </div>
        </div>
    @endisset
</x-admin-layout>
