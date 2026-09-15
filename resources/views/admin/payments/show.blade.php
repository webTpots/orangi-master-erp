<x-admin-layout>
    <x-slot name="title">Payment Detail</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.payments.index') }}" class="text-content-secondary hover:text-content transition-colors">Payments</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">{{ $payment->reference_number ?? 'PAY-' . $payment->id }}</span>
        </div>
    </x-slot>

    @php
        $statusColors = [
            'pending'   => 'bg-warning-50 text-warning-600',
            'received'  => 'bg-brand-50 text-brand-600',
            'confirmed' => 'bg-success-50 text-success-600',
            'bounced'   => 'bg-danger-50 text-danger-600',
            'reversed'  => 'bg-danger-50 text-danger-500',
        ];
    @endphp

    <div class="max-w-3xl">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="font-display text-xl font-bold text-content">{{ $payment->reference_number ?? 'PAY-' . $payment->id }}</h1>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium mt-1 {{ $statusColors[$payment->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                    {{ $payment->status_label }}
                </span>
            </div>
            @if ($payment->status === 'pending' || $payment->status === 'received')
                <form method="POST" action="{{ route('admin.payments.confirm', $payment) }}">
                    @csrf
                    <button type="submit" class="bg-success-500 hover:bg-success-600 text-white rounded-lg px-4 py-2 text-sm font-semibold transition-colors">
                        Confirm Payment
                    </button>
                </form>
            @endif
        </div>

        {{-- Detail Card --}}
        <div class="bg-white rounded-xl border border-surface-border-light p-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <p class="text-xs font-medium text-content-muted uppercase tracking-wider">Amount</p>
                    <p class="mt-1 text-2xl font-bold text-content">{{ Number::currency($payment->amount, 'INR') }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-content-muted uppercase tracking-wider">Payment Date</p>
                    <p class="mt-1 text-lg font-medium text-content">{{ $payment->paid_at->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-content-muted uppercase tracking-wider">Payment Type</p>
                    <p class="mt-1 text-sm text-content">{{ $payment->payment_type_label }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-content-muted uppercase tracking-wider">Payment Method</p>
                    <p class="mt-1 text-sm text-content">{{ $payment->payment_method_label }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-content-muted uppercase tracking-wider">Reference Number</p>
                    <p class="mt-1 text-sm font-mono text-content">{{ $payment->reference_number ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-content-muted uppercase tracking-wider">Bank Reference</p>
                    <p class="mt-1 text-sm font-mono text-content">{{ $payment->bank_reference ?? '-' }}</p>
                </div>
                @if ($payment->settlement)
                    <div class="col-span-2">
                        <p class="text-xs font-medium text-content-muted uppercase tracking-wider">Linked Settlement</p>
                        <a href="{{ route('admin.settlements.show', $payment->settlement) }}" class="mt-1 text-sm text-brand-500 hover:underline">
                            {{ $payment->settlement->settlement_reference }} ({{ $payment->settlement->marketplaceAccount?->account_name }})
                        </a>
                    </div>
                @endif
                @if ($payment->notes)
                    <div class="col-span-2">
                        <p class="text-xs font-medium text-content-muted uppercase tracking-wider">Notes</p>
                        <p class="mt-1 text-sm text-content-secondary">{{ $payment->notes }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
