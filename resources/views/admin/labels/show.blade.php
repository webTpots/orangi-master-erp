<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.labels.index') }}" class="text-content-muted hover:text-content transition">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            </a>
            <h2 class="text-xl font-display font-semibold text-content">Label #{{ $label->id }}</h2>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $label->status === 'linked' ? 'bg-success-50 text-success-500' : ($label->status === 'error' ? 'bg-danger-50 text-danger-500' : 'bg-warning-50 text-warning-500') }}">
                {{ ucfirst($label->status) }}
            </span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                {{-- Shipping Info --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Shipping Details</h3>
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-content-muted">AWB Number</dt><dd class="font-mono font-medium text-content">{{ $label->awb_number ?? '-' }}</dd></div>
                        <div><dt class="text-content-muted">Courier Partner</dt><dd class="font-medium text-content">{{ $label->courier_partner ?? '-' }}</dd></div>
                        <div><dt class="text-content-muted">Tracking Number</dt><dd class="font-mono text-content">{{ $label->tracking_number ?? '-' }}</dd></div>
                        <div><dt class="text-content-muted">Payment Type</dt><dd class="font-medium text-content">{{ $label->payment_type ? strtoupper($label->payment_type) : '-' }}</dd></div>
                        <div><dt class="text-content-muted">Sub Order #</dt><dd class="font-mono text-content">{{ $label->sub_order_number ?? '-' }}</dd></div>
                    </dl>
                </div>

                {{-- Customer Info --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Customer</h3>
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-content-muted">Name</dt><dd class="font-medium text-content">{{ $label->customer_name ?? '-' }}</dd></div>
                        <div><dt class="text-content-muted">City</dt><dd class="text-content">{{ $label->customer_city ?? '-' }}</dd></div>
                        <div><dt class="text-content-muted">State</dt><dd class="text-content">{{ $label->customer_state ?? '-' }}</dd></div>
                        <div><dt class="text-content-muted">Pincode</dt><dd class="font-mono text-content">{{ $label->customer_pincode ?? '-' }}</dd></div>
                    </dl>
                </div>

                {{-- Product Info --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Product</h3>
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-content-muted">SKU</dt><dd class="font-mono font-medium text-content">{{ $label->sku ?? '-' }}</dd></div>
                        <div><dt class="text-content-muted">Size</dt><dd class="font-medium text-content">{{ $label->size ?? '-' }}</dd></div>
                        <div><dt class="text-content-muted">Quantity</dt><dd class="font-medium text-content">{{ $label->quantity }}</dd></div>
                        <div><dt class="text-content-muted">Color</dt><dd class="text-content">{{ $label->color ?? '-' }}</dd></div>
                    </dl>
                </div>

                {{-- Invoice Info --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Invoice</h3>
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-content-muted">Invoice Number</dt><dd class="font-mono font-medium text-content">{{ $label->invoice_number ?? '-' }}</dd></div>
                        <div><dt class="text-content-muted">Invoice Date</dt><dd class="font-medium text-content">{{ $label->invoice_date?->format('d M Y') ?? '-' }}</dd></div>
                        <div><dt class="text-content-muted">Invoice Amount</dt><dd class="font-medium text-content">{{ $label->invoice_amount ? 'Rs. ' . number_format($label->invoice_amount, 2) : '-' }}</dd></div>
                        <div><dt class="text-content-muted">Taxable Value</dt><dd class="text-content">{{ $label->taxable_value ? 'Rs. ' . number_format($label->taxable_value, 2) : '-' }}</dd></div>
                        <div><dt class="text-content-muted">Tax Amount</dt><dd class="text-content">{{ $label->tax_amount ? 'Rs. ' . number_format($label->tax_amount, 2) : '-' }}</dd></div>
                    </dl>
                </div>
            </div>

            {{-- Linked Order --}}
            @if ($label->order)
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Linked Order</h3>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('admin.orders.show', $label->order) }}" class="text-brand-500 hover:underline font-mono">
                            {{ $label->order->marketplace_order_id }}
                        </a>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-brand-50 text-brand-600">
                            {{ $label->order->status_label }}
                        </span>
                    </div>
                </div>
            @endif

            {{-- Source File --}}
            @if ($label->labelFile)
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Source File</h3>
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-4">
                        <div><dt class="text-content-muted">File Name</dt><dd class="text-content">{{ $label->labelFile->file_name }}</dd></div>
                        <div><dt class="text-content-muted">Page</dt><dd class="text-content">{{ $label->page_number ?? '-' }}</dd></div>
                        <div><dt class="text-content-muted">Marketplace</dt><dd class="text-content">{{ $label->marketplace->name ?? '-' }}</dd></div>
                        <div><dt class="text-content-muted">Imported</dt><dd class="text-content">{{ $label->labelFile->created_at->diffForHumans() }}</dd></div>
                    </dl>
                </div>
            @endif

        </div>
    </div>
</x-admin-layout>
