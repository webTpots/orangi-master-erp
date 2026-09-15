<x-admin-layout>
    <x-slot name="title">Import Bank Statement</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.bank-reconciliation.index') }}" class="text-content-secondary hover:text-content transition-colors">Reconciliation</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Import Statement</span>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <h1 class="font-display text-xl font-bold text-content mb-6">Import Bank Statement</h1>

        <div class="bg-white rounded-xl border border-surface-border-light p-6 mb-6">
            <form method="POST" action="{{ route('admin.bank-reconciliation.process-import') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div>
                    <label for="bank_account_id" class="block text-sm font-medium text-content mb-1">Bank Account</label>
                    <select name="bank_account_id" id="bank_account_id" required
                            class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        <option value="">Select bank account...</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" @selected(old('bank_account_id') == $account->id)>{{ $account->account_name }} ({{ $account->bank_name }})</option>
                        @endforeach
                    </select>
                    @error('bank_account_id')
                        <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="format" class="block text-sm font-medium text-content mb-1">File Format</label>
                    <select name="format" id="format" required
                            class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        <option value="csv" @selected(old('format') === 'csv')>CSV</option>
                        <option value="excel" @selected(old('format') === 'excel')>Excel (XLSX/XLS)</option>
                    </select>
                    @error('format')
                        <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="file" class="block text-sm font-medium text-content mb-1">Statement File</label>
                    <input type="file" name="file" id="file" required accept=".csv,.txt,.xlsx,.xls"
                           class="w-full rounded-lg border border-surface-border bg-surface text-sm text-content file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-600 hover:file:bg-brand-100 focus:border-brand-400 focus:ring-brand-400">
                    <p class="mt-1 text-xs text-content-muted">Accepted formats: CSV, TXT, XLSX, XLS. Max file size: 10MB.</p>
                    @error('file')
                        <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="bg-surface-secondary rounded-lg p-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-content-secondary mb-2">Expected Columns</h3>
                    <p class="text-xs text-content-muted">The file should contain columns such as: Date, Description/Narration, Reference/UTR, Credit, Debit, Balance. Column headers are automatically normalized.</p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-6 py-2.5 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Import Statement
                    </button>
                    <a href="{{ route('admin.bank-reconciliation.index') }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-6 py-2.5 text-sm font-semibold transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Import History --}}
    @if($imports->isNotEmpty())
    <div class="max-w-4xl">
        <h2 class="font-display text-base font-semibold text-content mb-3">Import History</h2>
        <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
            <table class="min-w-full divide-y divide-surface-border">
                <thead class="bg-surface-secondary">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">File</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Account</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Imported</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Duplicates</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Imported At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border-light">
                    @foreach($imports as $import)
                        @php
                            $importStatusColors = [
                                'uploaded'            => 'bg-brand-50 text-brand-600',
                                'processing'          => 'bg-warning-50 text-warning-600',
                                'completed'           => 'bg-success-50 text-success-600',
                                'failed'              => 'bg-danger-50 text-danger-600',
                                'partially_completed' => 'bg-warning-50 text-warning-600',
                            ];
                        @endphp
                        <tr class="hover:bg-surface-secondary/50 transition">
                            <td class="px-4 py-3 text-sm text-content">{{ $import->file_name }}</td>
                            <td class="px-4 py-3 text-sm text-content-secondary">{{ $import->bankAccount?->account_name ?? '-' }}</td>
                            <td class="px-4 py-3 text-center text-sm text-content">{{ $import->total_records }}</td>
                            <td class="px-4 py-3 text-center text-sm text-success-500 font-medium">{{ $import->imported_records }}</td>
                            <td class="px-4 py-3 text-center text-sm {{ $import->duplicate_records > 0 ? 'text-warning-500' : 'text-content-muted' }}">{{ $import->duplicate_records }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $importStatusColors[$import->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                    {{ $import->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-content-secondary">{{ $import->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</x-admin-layout>
