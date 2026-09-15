<x-admin-layout>
    <x-slot name="title">Edit {{ $bankAccount->account_name }}</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.bank-accounts.index') }}" class="text-content-secondary hover:text-content transition-colors">Bank Accounts</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Edit</span>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <h1 class="font-display text-xl font-bold text-content mb-6">Edit Bank Account</h1>

        <div class="bg-white rounded-xl border border-surface-border-light p-6">
            <form method="POST" action="{{ route('admin.bank-accounts.update', $bankAccount) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="bank_name" class="block text-sm font-medium text-content mb-1">Bank Name</label>
                        <input type="text" name="bank_name" id="bank_name" required
                               value="{{ old('bank_name', $bankAccount->bank_name) }}"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        @error('bank_name')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="account_name" class="block text-sm font-medium text-content mb-1">Account Name</label>
                        <input type="text" name="account_name" id="account_name" required
                               value="{{ old('account_name', $bankAccount->account_name) }}"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        @error('account_name')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-content mb-1">Account Number</label>
                    <input type="text" disabled
                           value="{{ $bankAccount->masked_account_number }}"
                           class="w-full rounded-lg border-surface-border bg-surface-secondary text-sm text-content-secondary cursor-not-allowed">
                    <p class="mt-1 text-xs text-content-muted">Account number cannot be changed.</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="ifsc_code" class="block text-sm font-medium text-content mb-1">IFSC Code</label>
                        <input type="text" name="ifsc_code" id="ifsc_code"
                               value="{{ old('ifsc_code', $bankAccount->ifsc_code) }}"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        @error('ifsc_code')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="branch" class="block text-sm font-medium text-content mb-1">Branch</label>
                        <input type="text" name="branch" id="branch"
                               value="{{ old('branch', $bankAccount->branch) }}"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        @error('branch')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="account_type" class="block text-sm font-medium text-content mb-1">Account Type</label>
                        <select name="account_type" id="account_type" required
                                class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            @foreach (\App\Models\BankAccount::ACCOUNT_TYPE_LABELS as $val => $label)
                                <option value="{{ $val }}" @selected(old('account_type', $bankAccount->account_type) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-content mb-1">Status</label>
                        <select name="status" id="status" required
                                class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            @foreach (\App\Models\BankAccount::STATUS_LABELS as $val => $label)
                                <option value="{{ $val }}" @selected(old('status', $bankAccount->status) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_primary" value="1" class="sr-only peer" {{ old('is_primary', $bankAccount->is_primary) ? 'checked' : '' }}>
                        <div class="w-9 h-5 bg-surface-border rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-500"></div>
                    </label>
                    <span class="text-sm text-content">Set as primary account</span>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-6 py-2.5 text-sm font-semibold transition-colors">
                        Update Account
                    </button>
                    <a href="{{ route('admin.bank-accounts.show', $bankAccount) }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-6 py-2.5 text-sm font-semibold transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
