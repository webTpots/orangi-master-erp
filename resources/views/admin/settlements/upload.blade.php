<x-admin-layout>
    <x-slot name="title">Import Settlement</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.settlements.index') }}" class="text-content-secondary hover:text-content transition-colors">Settlements</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Import</span>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <h1 class="font-display text-xl font-bold text-content mb-6">Import Settlement File</h1>

        <div class="bg-white rounded-xl border border-surface-border-light p-6">
            <form method="POST" action="{{ route('admin.settlements.import') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div>
                    <label for="marketplace_account_id" class="block text-sm font-medium text-content mb-1">Marketplace Account</label>
                    <select name="marketplace_account_id" id="marketplace_account_id" required
                            class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        <option value="">Select marketplace account...</option>
                        @foreach ($marketplaceAccounts as $ma)
                            <option value="{{ $ma->id }}" @selected(old('marketplace_account_id') == $ma->id)>
                                {{ $ma->account_name }} ({{ $ma->marketplace->name ?? 'Unknown' }})
                            </option>
                        @endforeach
                    </select>
                    @error('marketplace_account_id')
                        <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="file" class="block text-sm font-medium text-content mb-1">Settlement File</label>
                    <input type="file" name="file" id="file" accept=".csv,.txt,.xlsx,.xls" required
                           class="w-full rounded-lg border border-surface-border bg-surface text-sm text-content file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-600 hover:file:bg-brand-100 focus:outline-none">
                    <p class="mt-1 text-xs text-content-muted">Accepts CSV, TXT, XLS, or XLSX files. Max 10MB.</p>
                    @error('file')
                        <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2">
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-6 py-2.5 text-sm font-semibold transition-colors inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Import Settlement
                    </button>
                </div>
            </form>
        </div>

        {{-- Instructions --}}
        <div class="mt-6 bg-white rounded-xl border border-surface-border-light p-6">
            <h2 class="text-sm font-semibold text-content mb-3">Upload Instructions</h2>
            <div class="space-y-3 text-sm text-content-secondary">
                <div>
                    <p class="font-medium text-content">Meesho</p>
                    <p>Download the payment/settlement file from Meesho Supplier Panel > Payments > Download Statement. The CSV should contain order IDs, amounts, commissions, and deductions.</p>
                </div>
                <div>
                    <p class="font-medium text-content">Flipkart</p>
                    <p>Download from Flipkart Seller Dashboard > Payments > Settlement Reports. Export as CSV with all columns included.</p>
                </div>
                <div class="bg-surface-secondary rounded-lg p-3 text-xs text-content-muted">
                    <p class="font-medium text-content-secondary mb-1">Expected columns:</p>
                    <p>Order ID, Product Name, SKU, Quantity, Selling Price, Shipping Fee, Commission, TCS, TDS, Penalty, Net Amount</p>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
