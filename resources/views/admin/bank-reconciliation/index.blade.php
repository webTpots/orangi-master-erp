<x-admin-layout>
    <x-slot name="title">Bank Reconciliation</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Bank Reconciliation</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">Bank Reconciliation</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.bank-reconciliation.transactions') }}"
               class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                All Transactions
            </a>
            <a href="{{ route('admin.bank-reconciliation.import') }}"
               class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import Statement
            </a>
        </div>
    </div>

    {{-- Account & Period Selector --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-4 mb-6">
        <form method="GET" action="{{ route('admin.bank-reconciliation.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="w-56">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Bank Account</label>
                <select name="bank_account_id"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected($selectedAccountId == $account->id)>{{ $account->account_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Period</label>
                <input type="month" name="period" value="{{ $period }}"
                       class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
            </div>
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">View</button>
        </form>
    </div>

    @if(!empty($summary))
    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Total Credits</p>
            <p class="mt-1 text-2xl font-semibold text-success-500">{{ Number::currency($summary['total_credits'] ?? 0, 'INR') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Matched Amount</p>
            <p class="mt-1 text-2xl font-semibold text-brand-500">{{ Number::currency($summary['matched_amount'] ?? 0, 'INR') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Unmatched Amount</p>
            <p class="mt-1 text-2xl font-semibold {{ ($summary['unmatched_amount'] ?? 0) > 0 ? 'text-warning-500' : 'text-content-muted' }}">{{ Number::currency($summary['unmatched_amount'] ?? 0, 'INR') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Match Rate</p>
            <p class="mt-1 text-2xl font-semibold {{ ($summary['match_rate'] ?? 0) >= 90 ? 'text-success-500' : (($summary['match_rate'] ?? 0) >= 50 ? 'text-warning-500' : 'text-danger-500') }}">{{ $summary['match_rate'] ?? 0 }}%</p>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
        <div class="bg-white rounded-lg border border-surface-border-light px-4 py-3 text-center">
            <p class="text-lg font-semibold text-content">{{ $summary['total_count'] ?? 0 }}</p>
            <p class="text-xs text-content-secondary">Total</p>
        </div>
        <div class="bg-white rounded-lg border border-surface-border-light px-4 py-3 text-center">
            <p class="text-lg font-semibold text-success-500">{{ $summary['matched_count'] ?? 0 }}</p>
            <p class="text-xs text-content-secondary">Matched</p>
        </div>
        <div class="bg-white rounded-lg border border-surface-border-light px-4 py-3 text-center">
            <p class="text-lg font-semibold text-warning-500">{{ $summary['unmatched_count'] ?? 0 }}</p>
            <p class="text-xs text-content-secondary">Unmatched</p>
        </div>
        <div class="bg-white rounded-lg border border-surface-border-light px-4 py-3 text-center">
            <p class="text-lg font-semibold text-danger-500">{{ $summary['disputed_count'] ?? 0 }}</p>
            <p class="text-xs text-content-secondary">Disputed</p>
        </div>
        <div class="bg-white rounded-lg border border-surface-border-light px-4 py-3 text-center">
            <p class="text-lg font-semibold text-neutral-400">{{ $summary['ignored_count'] ?? 0 }}</p>
            <p class="text-xs text-content-secondary">Ignored</p>
        </div>
    </div>

    {{-- Auto-Reconcile Action --}}
    @if(($summary['unmatched_count'] ?? 0) > 0 && $selectedAccount)
    <div class="bg-warning-50 border border-warning-500/20 rounded-xl p-4 mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-warning-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            <div>
                <p class="text-sm font-medium text-warning-700">{{ $summary['unmatched_count'] }} unmatched transactions found</p>
                <p class="text-xs text-warning-600">Run auto-reconciliation to match transactions against settlements and payments.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.bank-reconciliation.auto-reconcile') }}">
            @csrf
            <input type="hidden" name="bank_account_id" value="{{ $selectedAccountId }}">
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold transition-colors inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12.75l6 6 9-13.5"/></svg>
                Auto-Reconcile
            </button>
        </form>
    </div>
    @endif

    {{-- Unmatched Transactions Requiring Attention --}}
    @if($unmatchedTransactions->isNotEmpty())
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-display text-base font-semibold text-content">Unmatched Credits Requiring Attention</h2>
            <a href="{{ route('admin.bank-reconciliation.transactions', ['bank_account_id' => $selectedAccountId, 'match_status' => 'unmatched']) }}" class="text-brand-500 hover:text-brand-600 text-sm font-medium transition-colors">
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
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border-light">
                    @foreach($unmatchedTransactions as $txn)
                        <tr class="hover:bg-surface-secondary/50 transition">
                            <td class="px-4 py-3 text-sm text-content-secondary">{{ $txn->transaction_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-sm text-content max-w-[250px] truncate">{{ $txn->description }}</td>
                            <td class="px-4 py-3 text-sm font-mono text-xs text-content-secondary">{{ $txn->reference_number ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-success-500">{{ Number::currency($txn->amount, 'INR') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.bank-reconciliation.match', $txn) }}" class="text-brand-500 hover:text-brand-600 text-sm font-medium transition-colors">
                                    Match
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @else
    <div class="bg-white rounded-xl border border-surface-border-light p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-content-muted mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
        <p class="text-sm text-content-muted mb-2">No bank accounts configured.</p>
        <a href="{{ route('admin.bank-accounts.create') }}" class="text-brand-500 hover:underline text-sm">Add a bank account to get started.</a>
    </div>
    @endif
</x-admin-layout>
