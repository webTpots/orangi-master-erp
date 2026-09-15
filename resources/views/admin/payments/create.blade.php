<x-admin-layout>
    <x-slot name="title">Record Payment</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.payments.index') }}" class="text-content-secondary hover:text-content transition-colors">Payments</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Record Payment</span>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <h1 class="font-display text-xl font-bold text-content mb-6">Record New Payment</h1>

        <div class="bg-white rounded-xl border border-surface-border-light p-6">
            <form method="POST" action="{{ route('admin.payments.store') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="payment_type" class="block text-sm font-medium text-content mb-1">Payment Type</label>
                        <select name="payment_type" id="payment_type" required
                                class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            <option value="">Select type...</option>
                            @foreach (\App\Models\Payment::PAYMENT_TYPE_LABELS as $val => $label)
                                <option value="{{ $val }}" @selected(old('payment_type') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('payment_type')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-content mb-1">Payment Method</label>
                        <select name="payment_method" id="payment_method" required
                                class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            <option value="">Select method...</option>
                            @foreach (\App\Models\Payment::PAYMENT_METHOD_LABELS as $val => $label)
                                <option value="{{ $val }}" @selected(old('payment_method') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('payment_method')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="amount" class="block text-sm font-medium text-content mb-1">Amount (INR)</label>
                        <input type="number" name="amount" id="amount" step="0.01" min="0.01" required
                               value="{{ old('amount') }}"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                               placeholder="0.00">
                        @error('amount')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="paid_at" class="block text-sm font-medium text-content mb-1">Payment Date</label>
                        <input type="date" name="paid_at" id="paid_at" required
                               value="{{ old('paid_at', now()->format('Y-m-d')) }}"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        @error('paid_at')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="settlement_id" class="block text-sm font-medium text-content mb-1">Linked Settlement (optional)</label>
                    <select name="settlement_id" id="settlement_id"
                            class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        <option value="">No linked settlement</option>
                        @foreach ($settlements as $settlement)
                            <option value="{{ $settlement->id }}" @selected(old('settlement_id') == $settlement->id)>
                                {{ $settlement->settlement_reference }} - {{ $settlement->marketplaceAccount?->account_name }} ({{ Number::currency($settlement->net_payable, 'INR') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="reference_number" class="block text-sm font-medium text-content mb-1">Reference Number</label>
                        <input type="text" name="reference_number" id="reference_number"
                               value="{{ old('reference_number') }}"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                               placeholder="e.g., UTR number">
                    </div>

                    <div>
                        <label for="bank_reference" class="block text-sm font-medium text-content mb-1">Bank Reference</label>
                        <input type="text" name="bank_reference" id="bank_reference"
                               value="{{ old('bank_reference') }}"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                               placeholder="Bank transaction ref">
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-content mb-1">Notes</label>
                    <textarea name="notes" id="notes" rows="3"
                              class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                              placeholder="Optional notes...">{{ old('notes') }}</textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-6 py-2.5 text-sm font-semibold transition-colors">
                        Record Payment
                    </button>
                    <a href="{{ route('admin.payments.index') }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-6 py-2.5 text-sm font-semibold transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
