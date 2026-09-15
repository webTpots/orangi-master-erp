<x-admin-layout>
    <x-slot name="title">{{ $bankAccount->account_name }}</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.bank-accounts.index') }}" class="text-content-secondary hover:text-content transition-colors">Bank Accounts</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">{{ $bankAccount->account_name }}</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">{{ $bankAccount->account_name }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.bank-reconciliation.import') }}"
               class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import Statement
            </a>
            <a href="{{ route('admin.bank-accounts.edit', $bankAccount) }}"
               class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                Edit
            </a>
        </div>
    </div>

    {{-- Account Info Card --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-6 mb-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Bank</p>
                <p class="mt-1 text-sm font-semibold text-content">{{ $bankAccount->bank_name }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Account Number</p>
                <p class="mt-1 text-sm font-mono text-content">{{ $bankAccount->masked_account_number }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">IFSC</p>
                <p class="mt-1 text-sm font-mono text-content">{{ $bankAccount->ifsc_code ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Type</p>
                <p class="mt-1 text-sm text-content">{{ $bankAccount->account_type_label }}</p>
            </div>
        </div>
        <div class="border-t border-surface-border-light mt-4 pt-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Opening Balance</p>
                    <p class="mt-1 text-lg font-semibold text-content">{{ Number::currency($bankAccount->opening_balance, 'INR') }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Current Balance</p>
                    <p class="mt-1 text-lg font-semibold text-success-500">{{ Number::currency($bankAccount->current_balance, 'INR') }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Status</p>
                    @php
                        $statusColors = [
                            'active'   => 'bg-success-50 text-success-600',
                            'inactive' => 'bg-warning-50 text-warning-600',
                            'closed'   => 'bg-neutral-50 text-neutral-500',
                        ];
                    @endphp
                    <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$bankAccount->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                        {{ $bankAccount->status_label }}
                    </span>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Branch</p>
                    <p class="mt-1 text-sm text-content">{{ $bankAccount->branch ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Transactions --}}
    <div class="mb-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-display text-base font-semibold text-content">Recent Transactions</h2>
            <a href="{{ route('admin.bank-reconciliation.transactions', ['bank_account_id' => $bankAccount->id]) }}" class="text-brand-500 hover:text-brand-600 text-sm font-medium transition-colors">
                View All
            </a>
        </div>

        <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
            <table class="min-w-full divide-y divide-surface-border">
                <thead class="bg-surface-secondary">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Ref #</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Credit</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Debit</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Balance</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border-light">
                    @forelse ($transactions as $txn)
                        @php
                            $matchColors = [
                                'unmatched'        => 'bg-warning-50 text-warning-600',
                                'auto_matched'     => 'bg-blue-50 text-blue-600',
                                'manually_matched' => 'bg-success-50 text-success-600',
                                'disputed'         => 'bg-danger-50 text-danger-600',
                                'ignored'          => 'bg-neutral-50 text-neutral-500',
                            ];
                        @endphp
                        <tr class="hover:bg-surface-secondary/50 transition">
                            <td class="px-4 py-3 text-sm text-content-secondary">{{ $txn->transaction_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-sm text-content max-w-[250px] truncate">{{ $txn->description }}</td>
                            <td class="px-4 py-3 text-sm font-mono text-xs text-content-secondary">{{ $txn->reference_number ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-sm font-medium {{ $txn->transaction_type === 'credit' ? 'text-success-500' : 'text-content-muted' }}">
                                {{ $txn->transaction_type === 'credit' ? Number::currency($txn->amount, 'INR') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium {{ $txn->transaction_type === 'debit' ? 'text-danger-500' : 'text-content-muted' }}">
                                {{ $txn->transaction_type === 'debit' ? Number::currency($txn->amount, 'INR') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-content">
                                {{ $txn->running_balance !== null ? Number::currency($txn->running_balance, 'INR') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $matchColors[$txn->match_status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                    {{ $txn->match_status_label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-content-muted">
                                No transactions found. <a href="{{ route('admin.bank-reconciliation.import') }}" class="text-brand-500 hover:underline">Import a bank statement.</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    </div>

    {{-- Recent Imports --}}
    @if($imports->isNotEmpty())
    <div>
        <h2 class="font-display text-base font-semibold text-content mb-3">Recent Statement Imports</h2>
        <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
            <table class="min-w-full divide-y divide-surface-border">
                <thead class="bg-surface-secondary">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">File</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Records</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Imported</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Date</th>
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
                            <td class="px-4 py-3 text-center text-sm text-content">{{ $import->total_records }}</td>
                            <td class="px-4 py-3 text-center text-sm text-content">{{ $import->imported_records }}</td>
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
