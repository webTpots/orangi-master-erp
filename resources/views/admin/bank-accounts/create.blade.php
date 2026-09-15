<x-admin-layout>
    <x-slot name="title">Add Bank Account</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.bank-accounts.index') }}" class="text-content-secondary hover:text-content transition-colors">Bank Accounts</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Add Account</span>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <h1 class="font-display text-xl font-bold text-content mb-6">Add Bank Account</h1>

        <div class="bg-white rounded-xl border border-surface-border-light p-6">
            <form method="POST" action="{{ route('admin.bank-accounts.store') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="bank_name" class="block text-sm font-medium text-content mb-1">Bank Name</label>
                        <input type="text" name="bank_name" id="bank_name" required
                               value="{{ old('bank_name') }}"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                               placeholder="e.g., HDFC Bank">
                        @error('bank_name')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="account_name" class="block text-sm font-medium text-content mb-1">Account Name</label>
                        <input type="text" name="account_name" id="account_name" required
                               value="{{ old('account_name') }}"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                               placeholder="e.g., HDFC Current Account">
                        @error('account_name')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="account_number" class="block text-sm font-medium text-content mb-1">Account Number</label>
                        <input type="text" name="account_number" id="account_number" required
                               value="{{ old('account_number') }}"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                               placeholder="Enter account number">
                        @error('account_number')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="ifsc_code" class="block text-sm font-medium text-content mb-1">IFSC Code</label>
                        <input type="text" name="ifsc_code" id="ifsc_code"
                               value="{{ old('ifsc_code') }}"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                               placeholder="e.g., HDFC0001234">
                        @error('ifsc_code')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="branch" class="block text-sm font-medium text-content mb-1">Branch</label>
                        <input type="text" name="branch" id="branch"
                               value="{{ old('branch') }}"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                               placeholder="Branch name">
                        @error('branch')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="account_type" class="block text-sm font-medium text-content mb-1">Account Type</label>
                        <select name="account_type" id="account_type" required
                                class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            @foreach (\App\Models\BankAccount::ACCOUNT_TYPE_LABELS as $val => $label)
                                <option value="{{ $val }}" @selected(old('account_type', 'current') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('account_type')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="opening_balance" class="block text-sm font-medium text-content mb-1">Opening Balance (INR)</label>
                    <input type="number" name="opening_balance" id="opening_balance" step="0.01" min="0" required
                           value="{{ old('opening_balance', '0.00') }}"
                           class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                           placeholder="0.00">
                    @error('opening_balance')
                        <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_primary" value="1" class="sr-only peer" {{ old('is_primary') ? 'checked' : '' }}>
                        <div class="w-9 h-5 bg-surface-border rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-500"></div>
                    </label>
                    <span class="text-sm text-content">Set as primary account</span>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-6 py-2.5 text-sm font-semibold transition-colors">
                        Add Account
                    </button>
                    <a href="{{ route('admin.bank-accounts.index') }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-6 py-2.5 text-sm font-semibold transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
