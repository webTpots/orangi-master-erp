<x-admin-layout>
    <x-slot name="title">Add Vendor</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.vendors.index') }}" class="text-content-secondary hover:text-content transition-colors">Vendors</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Add Vendor</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="mb-6">
        <h1 class="font-display text-xl font-bold text-content">Add Vendor</h1>
        <p class="text-xs text-content-secondary mt-0.5">Register a new vendor for procurement</p>
    </div>

    {{-- Validation errors --}}
    @if($errors->any())
        <div class="mb-6 bg-danger-50 border border-danger-500/20 rounded-xl px-4 py-3 max-w-3xl">
            <div class="flex items-start gap-3">
                <svg class="w-4 h-4 text-danger-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <ul class="list-disc list-inside text-sm text-danger-500 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.vendors.store') }}" class="max-w-3xl space-y-6">
        @csrf

        {{-- Business Details --}}
        <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-5">
            <h3 class="font-display text-base font-semibold text-content mb-4">Business Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Vendor Name <span class="text-danger-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="input">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Contact Person</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person') }}" class="input">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="input">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="input">
                </div>
            </div>
        </div>

        {{-- Tax Details --}}
        <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-5">
            <h3 class="font-display text-base font-semibold text-content mb-4">Tax Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">GSTIN</label>
                    <input type="text" name="gstin" value="{{ old('gstin') }}" maxlength="15" class="input uppercase">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">PAN</label>
                    <input type="text" name="pan" value="{{ old('pan') }}" maxlength="10" class="input uppercase">
                </div>
            </div>
        </div>

        {{-- Address --}}
        <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-5">
            <h3 class="font-display text-base font-semibold text-content mb-4">Address</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Address</label>
                    <textarea name="address" rows="2" class="input">{{ old('address') }}</textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">City</label>
                    <input type="text" name="city" value="{{ old('city') }}" class="input">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">State</label>
                    <input type="text" name="state" value="{{ old('state') }}" class="input">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Pincode</label>
                    <input type="text" name="pincode" value="{{ old('pincode') }}" maxlength="6" class="input">
                </div>
            </div>
        </div>

        {{-- Terms --}}
        <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-5">
            <h3 class="font-display text-base font-semibold text-content mb-4">Terms</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Payment Terms</label>
                    <input type="text" name="payment_terms" value="{{ old('payment_terms') }}" placeholder="e.g. Net 30, Advance, COD" class="input">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Lead Time (days)</label>
                    <input type="number" name="lead_time_days" value="{{ old('lead_time_days', 7) }}" min="0" class="input">
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold transition-colors">Save Vendor</button>
            <a href="{{ route('admin.vendors.index') }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold transition-colors">Cancel</a>
        </div>
    </form>
</x-admin-layout>
