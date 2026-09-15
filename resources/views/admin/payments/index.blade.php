<x-admin-layout>
    <x-slot name="title">Payments</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Payments</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">Payment Management</h1>
        <a href="{{ route('admin.payments.create') }}"
           class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Record Payment
        </a>
    </div>

    {{-- KPI Row --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Total Received</p>
            <p class="mt-1 text-2xl font-semibold text-success-500">{{ Number::currency($kpis['total_received'], 'INR') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Pending</p>
            <p class="mt-1 text-2xl font-semibold {{ $kpis['pending'] > 0 ? 'text-warning-500' : 'text-content-muted' }}">{{ Number::currency($kpis['pending'], 'INR') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">This Month</p>
            <p class="mt-1 text-2xl font-semibold text-brand-500">{{ Number::currency($kpis['this_month'], 'INR') }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-4 mb-6">
        <form method="GET" action="{{ route('admin.payments.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Type</label>
                <select name="payment_type"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    <option value="">All</option>
                    @foreach (\App\Models\Payment::PAYMENT_TYPE_LABELS as $val => $label)
                        <option value="{{ $val }}" @selected(request('payment_type') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Method</label>
                <select name="payment_method"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    <option value="">All</option>
                    @foreach (\App\Models\Payment::PAYMENT_METHOD_LABELS as $val => $label)
                        <option value="{{ $val }}" @selected(request('payment_method') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-32">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Status</label>
                <select name="status"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    <option value="">All</option>
                    @foreach (\App\Models\Payment::STATUS_LABELS as $val => $label)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
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
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">Filter</button>
                <a href="{{ route('admin.payments.index') }}" class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">Clear</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
        <table class="min-w-full divide-y divide-surface-border">
            <thead class="bg-surface-secondary">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Ref #</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Method</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Date</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-border-light">
                @forelse ($payments as $payment)
                    @php
                        $statusColors = [
                            'pending'   => 'bg-warning-50 text-warning-600',
                            'received'  => 'bg-brand-50 text-brand-600',
                            'confirmed' => 'bg-success-50 text-success-600',
                            'bounced'   => 'bg-danger-50 text-danger-600',
                            'reversed'  => 'bg-danger-50 text-danger-500',
                        ];
                    @endphp
                    <tr class="hover:bg-surface-secondary/50 transition">
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('admin.payments.show', $payment) }}" class="font-mono text-brand-500 hover:underline text-xs">
                                {{ $payment->reference_number ?? 'PAY-' . $payment->id }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-content">{{ $payment->payment_type_label }}</td>
                        <td class="px-4 py-3 text-sm text-content-secondary">{{ $payment->payment_method_label }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-content">{{ Number::currency($payment->amount, 'INR') }}</td>
                        <td class="px-4 py-3 text-sm text-content-secondary">{{ $payment->paid_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$payment->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                {{ $payment->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.payments.show', $payment) }}" class="text-content-muted hover:text-brand-500 transition" title="View">
                                <svg class="inline h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-sm text-content-muted">
                            No payments found. <a href="{{ route('admin.payments.create') }}" class="text-brand-500 hover:underline">Record a payment.</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $payments->links() }}
    </div>
</x-admin-layout>
