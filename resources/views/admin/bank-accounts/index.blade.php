<x-admin-layout>
    <x-slot name="title">Bank Accounts</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Bank Accounts</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">Bank Accounts</h1>
        <a href="{{ route('admin.bank-accounts.create') }}"
           class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Bank Account
        </a>
    </div>

    {{-- Total Balance --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-4 mb-6">
        <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Total Balance (Active Accounts)</p>
        <p class="mt-1 text-2xl font-semibold text-success-500">{{ Number::currency($totalBalance, 'INR') }}</p>
    </div>

    {{-- Account Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($accounts as $account)
            @php
                $statusColors = [
                    'active'   => 'bg-success-50 text-success-600',
                    'inactive' => 'bg-warning-50 text-warning-600',
                    'closed'   => 'bg-neutral-50 text-neutral-500',
                ];
            @endphp
            <div class="bg-white rounded-xl border border-surface-border-light p-5 hover:shadow-card transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-brand-500/10 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-content">{{ $account->account_name }}</h3>
                            <p class="text-xs text-content-secondary">{{ $account->bank_name }}</p>
                        </div>
                    </div>
                    @if($account->is_primary)
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-brand-50 text-brand-600">Primary</span>
                    @endif
                </div>

                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-content-secondary">Account No.</span>
                        <span class="font-mono text-content text-xs">{{ $account->masked_account_number }}</span>
                    </div>
                    @if($account->ifsc_code)
                    <div class="flex justify-between text-sm">
                        <span class="text-content-secondary">IFSC</span>
                        <span class="font-mono text-content text-xs">{{ $account->ifsc_code }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <span class="text-content-secondary">Type</span>
                        <span class="text-content">{{ $account->account_type_label }}</span>
                    </div>
                </div>

                <div class="border-t border-surface-border-light pt-3 mb-3">
                    <p class="text-xs text-content-secondary mb-1">Current Balance</p>
                    <p class="text-lg font-semibold text-content">{{ Number::currency($account->current_balance, 'INR') }}</p>
                </div>

                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$account->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                        {{ $account->status_label }}
                    </span>
                    <a href="{{ route('admin.bank-accounts.show', $account) }}" class="text-brand-500 hover:text-brand-600 text-sm font-medium transition-colors">
                        View Details
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-surface-border-light p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-content-muted mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                <p class="text-sm text-content-muted mb-2">No bank accounts found.</p>
                <a href="{{ route('admin.bank-accounts.create') }}" class="text-brand-500 hover:underline text-sm">Add your first bank account.</a>
            </div>
        @endforelse
    </div>
</x-admin-layout>
