<x-vendor-layout :title="'PO ' . $po->po_number">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('vendor.purchase-orders') }}" class="text-content-secondary hover:text-content transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h1 class="text-lg font-display font-semibold text-content">{{ $po->po_number }}</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-{{ $po->status_color }}-50 text-{{ $po->status_color }}-600">
                    {{ $po->status_label }}
                </span>
            </div>
            <p class="text-sm text-content-secondary mt-1 ml-8">Ordered on {{ $po->order_date->format('d M Y') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Line Items --}}
            <div class="bg-white rounded-xl border border-surface-border">
                <div class="px-5 py-4 border-b border-surface-border">
                    <h2 class="text-sm font-semibold text-content">Line Items</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-surface-border-light">
                                <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">SKU</th>
                                <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Product</th>
                                <th class="text-right px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Qty</th>
                                <th class="text-right px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Unit Cost</th>
                                <th class="text-right px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border-light">
                            @foreach($po->lines as $line)
                                <tr>
                                    <td class="px-5 py-3 font-mono text-xs">{{ $line->sku->sku_code }}</td>
                                    <td class="px-5 py-3">{{ $line->sku->short_name ?? $line->sku->sku_code }}</td>
                                    <td class="px-5 py-3 text-right">{{ $line->quantity_requested }}</td>
                                    <td class="px-5 py-3 text-right">{{ number_format($line->unit_cost, 2) }}</td>
                                    <td class="px-5 py-3 text-right font-medium">{{ number_format($line->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-surface-border">
                                <td colspan="4" class="px-5 py-3 text-right font-semibold text-content">Total</td>
                                <td class="px-5 py-3 text-right font-semibold text-content">{{ number_format($po->total_amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sidebar Actions --}}
        <div class="space-y-6">
            {{-- PO Details --}}
            <div class="bg-white rounded-xl border border-surface-border p-5">
                <h3 class="text-sm font-semibold text-content mb-3">Order Details</h3>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-content-secondary">Order Date</dt>
                        <dd class="font-medium text-content">{{ $po->order_date->format('d M Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-content-secondary">Expected Delivery</dt>
                        <dd class="font-medium text-content">{{ $po->expected_delivery_date?->format('d M Y') ?? 'Not set' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-content-secondary">Status</dt>
                        <dd>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-{{ $po->status_color }}-50 text-{{ $po->status_color }}-600">
                                {{ $po->status_label }}
                            </span>
                        </dd>
                    </div>
                    @if($po->notes)
                        <div class="pt-2 border-t border-surface-border-light">
                            <dt class="text-content-secondary mb-1">Notes</dt>
                            <dd class="text-content">{{ $po->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Confirm Order --}}
            @if($po->status === 'sent')
                <div class="bg-white rounded-xl border border-surface-border p-5">
                    <h3 class="text-sm font-semibold text-content mb-3">Confirm Order</h3>
                    <p class="text-xs text-content-secondary mb-4">Confirm that you can fulfill this purchase order.</p>
                    <form method="POST" action="{{ route('vendor.purchase-orders.confirm', $po) }}">
                        @csrf
                        <button type="submit" class="w-full bg-success-500 text-white rounded-lg px-4 py-2.5 text-sm font-medium hover:bg-success-600 transition-colors">
                            Confirm Order
                        </button>
                    </form>
                </div>
            @endif

            {{-- Update Delivery Date --}}
            @if(!in_array($po->status, ['cancelled', 'closed', 'received']))
                <div class="bg-white rounded-xl border border-surface-border p-5">
                    <h3 class="text-sm font-semibold text-content mb-3">Update Delivery Date</h3>
                    <form method="POST" action="{{ route('vendor.purchase-orders.delivery', $po) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="expected_delivery_date" class="block text-xs font-medium text-content-secondary mb-1">Expected Delivery</label>
                            <input
                                type="date"
                                name="expected_delivery_date"
                                id="expected_delivery_date"
                                value="{{ $po->expected_delivery_date?->format('Y-m-d') ?? '' }}"
                                min="{{ now()->format('Y-m-d') }}"
                                class="w-full rounded-lg border border-surface-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500"
                                required
                            >
                        </div>
                        <button type="submit" class="w-full bg-brand-500 text-white rounded-lg px-4 py-2.5 text-sm font-medium hover:bg-brand-600 transition-colors">
                            Update Date
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-vendor-layout>
