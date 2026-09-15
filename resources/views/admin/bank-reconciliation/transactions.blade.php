<x-admin-layout>
    <x-slot name="title">Bank Transactions</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.bank-reconciliation.index') }}" class="text-content-secondary hover:text-content transition-colors">Reconciliation</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Transactions</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">Bank Transactions</h1>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-4 mb-6">
        <form method="GET" action="{{ route('admin.bank-reconciliation.transactions') }}" class="flex flex-wrap items-end gap-4">
            <div class="w-48">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Bank Account</label>
                <select name="bank_account_id"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    <option value="">All Accounts</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected(request('bank_account_id') == $account->id)>{{ $account->account_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Match Status</label>
                <select name="match_status"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    <option value="">All</option>
                    @foreach (\App\Models\BankTransaction::MATCH_STATUS_LABELS as $val => $label)
                        <option value="{{ $val }}" @selected(request('match_status') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-44">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Category</label>
                <select name="category"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    <option value="">All</option>
                    @foreach (\App\Models\BankTransaction::CATEGORY_LABELS as $val => $label)
                        <option value="{{ $val }}" @selected(request('category') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="mb-1 block text-xs font-medium text-content-secondary">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}"
                       class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
            </div>
            <div class="w-36">
                <label class="mb-1 block text-xs font-medium text-content-secondary">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}"
                       class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
            </div>
            <div class="w-48">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ref# or description..."
                       class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">Filter</button>
                <a href="{{ route('admin.bank-reconciliation.transactions') }}" class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">Clear</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-surface-border">
                <thead class="bg-surface-secondary">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Ref #</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Credit</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Debit</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Balance</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Category</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Match Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Actions</th>
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
                            $categoryColors = [
                                'marketplace_settlement' => 'bg-brand-50 text-brand-600',
                                'vendor_payment'         => 'bg-purple-50 text-purple-600',
                                'refund'                 => 'bg-warning-50 text-warning-600',
                                'expense'                => 'bg-danger-50 text-danger-600',
                                'salary'                 => 'bg-blue-50 text-blue-600',
                                'tax'                    => 'bg-neutral-50 text-neutral-600',
                                'transfer'               => 'bg-cyan-50 text-cyan-600',
                                'other'                  => 'bg-neutral-50 text-neutral-500',
                                'uncategorized'          => 'bg-neutral-50 text-neutral-400',
                            ];
                        @endphp
                        <tr class="hover:bg-surface-secondary/50 transition">
                            <td class="px-4 py-3 text-sm text-content-secondary whitespace-nowrap">{{ $txn->transaction_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-sm text-content max-w-[220px] truncate" title="{{ $txn->description }}">{{ $txn->description }}</td>
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
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium {{ $categoryColors[$txn->category] ?? 'bg-neutral-50 text-neutral-500' }}">
                                    {{ $txn->category_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $matchColors[$txn->match_status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                    {{ $txn->match_status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if($txn->match_status === 'unmatched')
                                    <a href="{{ route('admin.bank-reconciliation.match', $txn) }}" class="text-brand-500 hover:text-brand-600 text-xs font-medium transition-colors mr-2" title="Match">
                                        <svg class="inline h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.06a4.5 4.5 0 00-6.364-6.364L4.5 8.257"/></svg>
                                    </a>
                                @endif
                                @if(in_array($txn->match_status, ['auto_matched', 'manually_matched']))
                                    <form method="POST" action="{{ route('admin.bank-reconciliation.unmatch', $txn) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-warning-500 hover:text-warning-600 text-xs font-medium transition-colors mr-2" title="Unmatch" onclick="return confirm('Remove this match?')">
                                            <svg class="inline h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.181 8.68a4.503 4.503 0 011.903 6.405m-9.768-2.782L3.56 14.06a4.5 4.5 0 006.364 6.364l3.501-3.501m2.772-9.218L17.954 9.96a4.5 4.5 0 00-6.364-6.364l-3.501 3.501"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18"/></svg>
                                        </button>
                                    </form>
                                @endif
                                @if($txn->match_status === 'unmatched')
                                    <span x-data="{ open: false }" class="relative inline-block">
                                        <button @click="open = !open" class="text-content-muted hover:text-content text-xs transition-colors" title="Ignore">
                                            <svg class="inline h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                        </button>
                                        <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-1 w-64 bg-white rounded-xl border border-surface-border shadow-lg p-3 z-50">
                                            <form method="POST" action="{{ route('admin.bank-reconciliation.ignore', $txn) }}">
                                                @csrf
                                                <label class="block text-xs font-medium text-content mb-1">Reason</label>
                                                <input type="text" name="reason" required placeholder="Why ignore?" class="w-full rounded-lg border-surface-border bg-surface text-xs text-content focus:border-brand-400 focus:ring-brand-400 mb-2">
                                                <button type="submit" class="w-full rounded-lg bg-neutral-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-neutral-600 transition">Ignore Transaction</button>
                                            </form>
                                        </div>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-sm text-content-muted">
                                No transactions found. <a href="{{ route('admin.bank-reconciliation.import') }}" class="text-brand-500 hover:underline">Import a bank statement.</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $transactions->links() }}
    </div>
</x-admin-layout>
