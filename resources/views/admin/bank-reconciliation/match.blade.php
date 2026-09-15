<x-admin-layout>
    <x-slot name="title">Match Transaction</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.bank-reconciliation.index') }}" class="text-content-secondary hover:text-content transition-colors">Reconciliation</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.bank-reconciliation.transactions') }}" class="text-content-secondary hover:text-content transition-colors">Transactions</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Match</span>
        </div>
    </x-slot>

    <h1 class="font-display text-xl font-bold text-content mb-6">Manual Match</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Transaction Details (Left) --}}
        <div>
            <h2 class="font-display text-base font-semibold text-content mb-3">Bank Transaction</h2>
            <div class="bg-white rounded-xl border border-surface-border-light p-5">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-xs font-medium text-content-secondary uppercase tracking-wider">Date</span>
                        <span class="text-sm text-content">{{ $transaction->transaction_date->format('d M Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs font-medium text-content-secondary uppercase tracking-wider">Type</span>
                        <span class="text-sm font-medium {{ $transaction->transaction_type === 'credit' ? 'text-success-500' : 'text-danger-500' }}">
                            {{ ucfirst($transaction->transaction_type) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-xs font-medium text-content-secondary uppercase tracking-wider">Amount</span>
                        <span class="text-lg font-semibold {{ $transaction->transaction_type === 'credit' ? 'text-success-500' : 'text-danger-500' }}">
                            {{ Number::currency($transaction->amount, 'INR') }}
                        </span>
                    </div>
                    <div class="border-t border-surface-border-light pt-3">
                        <p class="text-xs font-medium text-content-secondary uppercase tracking-wider mb-1">Description</p>
                        <p class="text-sm text-content">{{ $transaction->description }}</p>
                    </div>
                    @if($transaction->reference_number)
                    <div>
                        <p class="text-xs font-medium text-content-secondary uppercase tracking-wider mb-1">Reference Number</p>
                        <p class="text-sm font-mono text-content">{{ $transaction->reference_number }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-xs font-medium text-content-secondary uppercase tracking-wider mb-1">Bank Account</p>
                        <p class="text-sm text-content">{{ $transaction->bankAccount?->account_name ?? '-' }}</p>
                    </div>
                    @if($transaction->running_balance !== null)
                    <div class="flex justify-between">
                        <span class="text-xs font-medium text-content-secondary uppercase tracking-wider">Running Balance</span>
                        <span class="text-sm text-content">{{ Number::currency($transaction->running_balance, 'INR') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Suggested Matches (Right) --}}
        <div>
            <h2 class="font-display text-base font-semibold text-content mb-3">Suggested Matches</h2>

            @if(count($suggestions) > 0)
                <div class="space-y-3">
                    @foreach($suggestions as $suggestion)
                        @php
                            $confidenceColors = [
                                'high'   => 'bg-success-50 text-success-600 border-success-200',
                                'medium' => 'bg-warning-50 text-warning-600 border-warning-200',
                                'low'    => 'bg-neutral-50 text-neutral-500 border-neutral-200',
                            ];
                        @endphp
                        <div class="bg-white rounded-xl border border-surface-border-light p-4 hover:shadow-card transition-shadow">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-semibold uppercase tracking-wider text-content-secondary">{{ $suggestion['entity_type'] }}</span>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium border {{ $confidenceColors[$suggestion['confidence']] ?? 'bg-neutral-50 text-neutral-500' }}">
                                            {{ ucfirst($suggestion['confidence']) }} confidence
                                        </span>
                                    </div>
                                    <p class="text-sm font-medium text-content">{{ $suggestion['label'] }}</p>
                                    <p class="text-xs text-content-secondary mt-0.5">{{ $suggestion['date'] }}</p>
                                </div>
                                <p class="text-sm font-semibold text-content">{{ Number::currency($suggestion['amount'], 'INR') }}</p>
                            </div>

                            @php
                                $diff = abs($transaction->amount - $suggestion['amount']);
                            @endphp
                            @if($diff > 0)
                                <p class="text-xs text-warning-500 mb-2">
                                    <svg class="inline w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/></svg>
                                    Amount difference: {{ Number::currency($diff, 'INR') }}
                                </p>
                            @endif

                            <form method="POST" action="{{ route('admin.bank-reconciliation.match', $transaction) }}">
                                @csrf
                                <input type="hidden" name="entity_type" value="{{ $suggestion['entity_type'] }}">
                                <input type="hidden" name="entity_id" value="{{ $suggestion['entity_id'] }}">
                                <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold transition-colors inline-flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.06a4.5 4.5 0 00-6.364-6.364L4.5 8.257"/></svg>
                                    Match to {{ $suggestion['entity_type'] }}
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-xl border border-surface-border-light p-8 text-center">
                    <svg class="mx-auto h-10 w-10 text-content-muted mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <p class="text-sm text-content-muted">No suggested matches found for this transaction.</p>
                </div>
            @endif

            {{-- Ignore Option --}}
            <div class="mt-4 bg-white rounded-xl border border-surface-border-light p-4">
                <h3 class="text-sm font-semibold text-content mb-2">No match applicable?</h3>
                <form method="POST" action="{{ route('admin.bank-reconciliation.ignore', $transaction) }}" class="flex gap-2">
                    @csrf
                    <input type="text" name="reason" required placeholder="Reason to ignore..."
                           class="flex-1 rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    <button type="submit" class="rounded-lg bg-neutral-500 hover:bg-neutral-600 text-white px-4 py-2 text-sm font-medium transition-colors">
                        Ignore
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('admin.bank-reconciliation.transactions') }}" class="text-content-secondary hover:text-content text-sm transition-colors inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Transactions
        </a>
    </div>
</x-admin-layout>
