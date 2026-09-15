<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.flipkart.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Flipkart</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Import Settlement</span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('error'))
                <div class="rounded-lg border border-danger-border bg-danger-50 p-4 text-sm text-danger-500">{{ session('error') }}</div>
            @endif

            <div class="rounded-xl border border-surface-border bg-white p-8 shadow-card">
                <div class="mb-6">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: #2874F0;">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <h3 class="text-lg font-semibold text-content">Import Flipkart Settlement</h3>
                    </div>
                    <p class="mt-1 text-sm text-content-secondary">
                        Upload a Flipkart settlement/payment report CSV. The system will parse order-level
                        financials including commissions, fees, and net settlement amounts.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.flipkart.process-settlement') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <label for="marketplace_account_id" class="block text-sm font-medium text-content mb-1">Flipkart Account</label>
                        <select name="marketplace_account_id" id="marketplace_account_id" required
                                class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            <option value="">Select Flipkart account...</option>
                            @foreach ($marketplaceAccounts as $ma)
                                <option value="{{ $ma->id }}" @selected(old('marketplace_account_id') == $ma->id)>
                                    {{ $ma->account_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('marketplace_account_id')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror

                        @if ($marketplaceAccounts->isEmpty())
                            <p class="mt-2 text-xs text-warning-600">No Flipkart accounts configured. Please add a marketplace account first.</p>
                        @endif
                    </div>

                    <div>
                        <label for="file" class="block text-sm font-medium text-content mb-1">Settlement File</label>
                        <input type="file" name="file" id="file" accept=".csv,.txt,.xlsx,.xls" required
                               class="w-full rounded-lg border border-surface-border bg-surface text-sm text-content file:mr-4 file:rounded-lg file:border-0 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:opacity-90 focus:outline-none" style="--tw-file-bg: #2874F0;">
                        <p class="mt-1 text-xs text-content-muted">Accepts CSV, TXT, XLS, or XLSX files. Max 10MB.</p>
                        @error('file')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-surface-border pt-6">
                        <a href="{{ route('admin.flipkart.dashboard') }}"
                           class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">
                            Cancel
                        </a>
                        <button type="submit"
                                class="rounded-lg text-white px-6 py-2 text-sm font-medium hover:opacity-90 transition inline-flex items-center gap-2" style="background-color: #2874F0;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Import Settlement
                        </button>
                    </div>
                </form>
            </div>

            {{-- Instructions --}}
            <div class="rounded-xl border border-surface-border bg-surface-secondary p-6">
                <h4 class="text-sm font-semibold text-content mb-3">How to download from Flipkart</h4>
                <ol class="text-sm text-content-secondary space-y-2 list-decimal list-inside">
                    <li>Log in to Flipkart Seller Dashboard</li>
                    <li>Navigate to <span class="font-medium text-content">Payments</span> > <span class="font-medium text-content">Settlement Reports</span></li>
                    <li>Select the date range and click <span class="font-medium text-content">Download</span></li>
                    <li>Export as CSV with all columns included</li>
                </ol>
                <div class="mt-4 bg-white rounded-lg p-3 text-xs text-content-muted border border-surface-border">
                    <p class="font-medium text-content-secondary mb-1">Expected Flipkart columns:</p>
                    <p>Order ID, Order Item ID, SKU, Product Title, Quantity, Selling Price, Shipping Fee, Marketplace Fee, Commission, Collection Fee, Tax on Commission, Settlement Value, Payment Type, NEFT UTR</p>
                </div>
            </div>

        </div>
    </div>
</x-admin-layout>
