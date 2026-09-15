<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.orders.index') }}" class="text-content-muted hover:text-content transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                </a>
                <h2 class="text-xl font-display font-semibold text-content">Order <span class="font-mono text-base">{{ \Illuminate\Support\Str::limit($order->marketplace_order_id, 22) }}</span></h2>
                @php
                    $statusColors = [
                        'new' => 'bg-brand-50 text-brand-600',
                        'accepted' => 'bg-brand-50 text-brand-700',
                        'label_ready' => 'bg-warning-50 text-warning-600',
                        'picking' => 'bg-warning-50 text-warning-700',
                        'packed' => 'bg-success-50 text-success-500',
                        'ready_to_ship' => 'bg-success-50 text-success-600',
                        'scanned' => 'bg-success-50 text-success-700',
                        'handed_over' => 'bg-brand-50 text-brand-600',
                        'in_transit' => 'bg-brand-50 text-brand-500',
                        'delivered' => 'bg-success-50 text-success-600',
                        'cancelled' => 'bg-danger-50 text-danger-500',
                        'return' => 'bg-danger-50 text-danger-600',
                        'rto' => 'bg-danger-50 text-danger-700',
                    ];
                @endphp
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $statusColors[$order->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                    {{ $order->status_label }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                @foreach ($availableTransitions as $transition)
                    <form method="POST" action="{{ route('admin.orders.change-status', $order) }}" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="{{ $transition }}">
                        <button type="submit"
                                onclick="return confirm('Change status to {{ \App\Models\Order::STATUS_LABELS[$transition] ?? ucfirst($transition) }}?')"
                                class="inline-flex items-center gap-1.5 rounded-lg {{ $transition === 'cancelled' ? 'border border-danger-500 text-danger-500 hover:bg-danger-50' : 'bg-brand-500 text-white hover:bg-brand-600' }} px-3 py-1.5 text-sm font-medium transition">
                            {{ \App\Models\Order::STATUS_LABELS[$transition] ?? ucfirst($transition) }}
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-lg border border-success-500/20 bg-success-50 p-4 text-sm text-success-600">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-lg border border-danger-border bg-danger-50 p-4 text-sm text-danger-500">{{ session('error') }}</div>
            @endif

            {{-- Status Progress Bar --}}
            <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                @php
                    $steps = [
                        'new' => 'New',
                        'accepted' => 'Accepted',
                        'label_ready' => 'Label Ready',
                        'picking' => 'Picking',
                        'packed' => 'Packed',
                        'ready_to_ship' => 'Ready',
                        'scanned' => 'Scanned',
                        'handed_over' => 'Handed Over',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                    ];
                    $statusKeys = array_keys($steps);
                    $currentIndex = array_search($order->status, $statusKeys);
                    if ($currentIndex === false) $currentIndex = -1;
                @endphp
                <div class="flex items-center justify-between overflow-x-auto">
                    @foreach ($steps as $key => $label)
                        @php $stepIndex = array_search($key, $statusKeys); @endphp
                        <div class="flex flex-col items-center flex-1 min-w-[60px]">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold
                                {{ $stepIndex < $currentIndex ? 'bg-success-500 text-white' : ($stepIndex === $currentIndex ? 'bg-brand-500 text-white ring-4 ring-brand-100' : 'bg-surface-secondary text-content-muted') }}">
                                @if ($stepIndex < $currentIndex)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                @else
                                    {{ $stepIndex + 1 }}
                                @endif
                            </div>
                            <span class="mt-1 text-[10px] font-medium {{ $stepIndex <= $currentIndex ? 'text-content' : 'text-content-muted' }}">{{ $label }}</span>
                        </div>
                        @if (! $loop->last)
                            <div class="flex-1 h-0.5 mx-1 {{ $stepIndex < $currentIndex ? 'bg-success-500' : 'bg-surface-border' }}"></div>
                        @endif
                    @endforeach
                </div>
                @if (in_array($order->status, ['cancelled', 'return', 'rto']))
                    <div class="mt-3 text-center">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $statusColors[$order->status] }}">
                            {{ $order->status_label }}
                        </span>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Main Content --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Order Info --}}
                    <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Order Details</h3>
                        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div><dt class="text-content-muted">Marketplace Order ID</dt><dd class="font-mono font-medium text-content">{{ $order->marketplace_order_id }}</dd></div>
                            <div><dt class="text-content-muted">Marketplace</dt><dd class="font-medium text-content">{{ $order->marketplace->name ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">Order Date</dt><dd class="font-medium text-content">{{ $order->order_date->format('d M Y') }}</dd></div>
                            <div><dt class="text-content-muted">Payment Type</dt><dd class="font-medium text-content">{{ $order->payment_type ? strtoupper($order->payment_type) : '-' }}</dd></div>
                            <div><dt class="text-content-muted">Courier Partner</dt><dd class="font-medium text-content">{{ $order->courier_partner ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">AWB Number</dt><dd class="font-mono font-medium text-content">{{ $order->awb_number ?? '-' }}</dd></div>
                        </dl>
                    </div>

                    {{-- Sub-Orders Table --}}
                    <div class="rounded-xl border border-surface-border bg-white shadow-card">
                        <div class="px-6 py-4 border-b border-surface-border">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Sub-Orders ({{ $order->subOrders->count() }})</h3>
                        </div>
                        <table class="min-w-full divide-y divide-surface-border">
                            <thead class="bg-surface-secondary">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Sub Order #</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">SKU</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Product</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Size</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Qty</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Stock</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Processing</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-border-light">
                                @forelse ($order->subOrders as $sub)
                                    @php
                                        $stockColors = [
                                            'pending' => 'bg-neutral-50 text-neutral-500',
                                            'in_stock' => 'bg-success-50 text-success-500',
                                            'partial' => 'bg-warning-50 text-warning-500',
                                            'out_of_stock' => 'bg-danger-50 text-danger-500',
                                            'not_mapped' => 'bg-neutral-50 text-neutral-400',
                                        ];
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3 text-xs font-mono text-content">{{ \Illuminate\Support\Str::limit($sub->sub_order_number, 20) }}</td>
                                        <td class="px-4 py-3 text-sm text-content">{{ $sub->marketplace_sku ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-content max-w-[200px] truncate">{{ \Illuminate\Support\Str::limit($sub->product_name, 40) ?? '-' }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-content">{{ $sub->size ?? '-' }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-content">{{ $sub->quantity }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $stockColors[$sub->stock_status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                                {{ $sub->stock_status_label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-xs text-content-secondary capitalize">{{ $sub->processing_status }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="px-4 py-6 text-center text-sm text-content-muted">No sub-orders.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Linked Labels --}}
                    @if ($order->labels->isNotEmpty())
                        <div class="rounded-xl border border-surface-border bg-white shadow-card">
                            <div class="px-6 py-4 border-b border-surface-border">
                                <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Linked Labels ({{ $order->labels->count() }})</h3>
                            </div>
                            <table class="min-w-full divide-y divide-surface-border">
                                <thead class="bg-surface-secondary">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">AWB</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Courier</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Invoice #</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Amount</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">File</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-surface-border-light">
                                    @foreach ($order->labels as $label)
                                        <tr>
                                            <td class="px-4 py-3 text-xs font-mono text-content">{{ $label->awb_number ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-content">{{ $label->courier_partner ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-content">{{ $label->invoice_number ?? '-' }}</td>
                                            <td class="px-4 py-3 text-right text-sm font-medium text-content">{{ $label->invoice_amount ? number_format($label->invoice_amount, 2) : '-' }}</td>
                                            <td class="px-4 py-3 text-xs text-content-muted">{{ $label->labelFile?->file_name ?? '-' }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $label->status === 'linked' ? 'bg-success-50 text-success-500' : ($label->status === 'error' ? 'bg-danger-50 text-danger-500' : 'bg-neutral-50 text-neutral-500') }}">
                                                    {{ ucfirst($label->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="space-y-6">

                    {{-- Customer Info --}}
                    <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Customer</h3>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-content-muted">Name</dt><dd class="font-medium text-content">{{ $order->customer_name ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">Phone</dt><dd class="font-medium text-content">{{ $order->customer_phone ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">Address</dt><dd class="text-content">{{ $order->customer_address ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">City</dt><dd class="font-medium text-content">{{ $order->customer_city ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">State</dt><dd class="font-medium text-content">{{ $order->customer_state ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">Pincode</dt><dd class="font-mono font-medium text-content">{{ $order->customer_pincode ?? '-' }}</dd></div>
                        </dl>
                    </div>

                    {{-- Invoice / Financial Card --}}
                    <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Invoice / Financials</h3>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-content-muted">Invoice Number</dt><dd class="font-mono font-medium text-content">{{ $order->invoice_number ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">Invoice Date</dt><dd class="font-medium text-content">{{ $order->invoice_date?->format('d M Y') ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">Invoice Amount</dt><dd class="font-medium text-content">{{ $order->invoice_amount ? 'Rs. ' . number_format($order->invoice_amount, 2) : '-' }}</dd></div>
                            <div><dt class="text-content-muted">HSN Code</dt><dd class="font-mono text-content">{{ $order->hsn_code ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">Taxable Value</dt><dd class="text-content">{{ $order->taxable_value ? 'Rs. ' . number_format($order->taxable_value, 2) : '-' }}</dd></div>
                            @if ($order->igst)
                                <div><dt class="text-content-muted">IGST</dt><dd class="text-content">Rs. {{ number_format($order->igst, 2) }}</dd></div>
                            @endif
                            @if ($order->sgst)
                                <div><dt class="text-content-muted">SGST</dt><dd class="text-content">Rs. {{ number_format($order->sgst, 2) }}</dd></div>
                            @endif
                            @if ($order->cgst)
                                <div><dt class="text-content-muted">CGST</dt><dd class="text-content">Rs. {{ number_format($order->cgst, 2) }}</dd></div>
                            @endif
                            @if ($order->other_charges)
                                <div><dt class="text-content-muted">Other Charges</dt><dd class="text-content">Rs. {{ number_format($order->other_charges, 2) }}</dd></div>
                            @endif
                            <div class="border-t border-surface-border pt-2">
                                <dt class="text-content-muted">Total Amount</dt>
                                <dd class="text-lg font-semibold text-content">Rs. {{ number_format($order->total_amount, 2) }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Status History Timeline --}}
                    <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Status History</h3>
                        @if ($order->statusHistory->isNotEmpty())
                            <div class="space-y-4">
                                @foreach ($order->statusHistory as $entry)
                                    <div class="flex gap-3">
                                        <div class="flex flex-col items-center">
                                            <div class="w-2.5 h-2.5 rounded-full bg-brand-500 mt-1.5"></div>
                                            @if (! $loop->last)
                                                <div class="w-px flex-1 bg-surface-border"></div>
                                            @endif
                                        </div>
                                        <div class="pb-4">
                                            <div class="text-sm font-medium text-content">
                                                @if ($entry->from_status)
                                                    {{ \App\Models\Order::STATUS_LABELS[$entry->from_status] ?? $entry->from_status }}
                                                    <svg class="inline w-3 h-3 text-content-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                                @endif
                                                {{ \App\Models\Order::STATUS_LABELS[$entry->to_status] ?? $entry->to_status }}
                                            </div>
                                            @if ($entry->notes)
                                                <p class="text-xs text-content-muted mt-0.5">{{ $entry->notes }}</p>
                                            @endif
                                            <p class="text-xs text-content-muted mt-0.5">
                                                {{ $entry->changed_at->format('d M Y, h:i A') }}
                                                @if ($entry->user)
                                                    by {{ $entry->user->name }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-content-muted">No status changes recorded.</p>
                        @endif
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-admin-layout>
