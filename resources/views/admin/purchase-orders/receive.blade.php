<x-admin-layout>
    <x-slot name="title">Receive Goods - {{ $purchaseOrder->po_number }}</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.purchase-orders.index') }}" class="text-content-secondary hover:text-content transition-colors">Purchase Orders</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.purchase-orders.show', $purchaseOrder) }}" class="text-content-secondary hover:text-content transition-colors font-mono">{{ $purchaseOrder->po_number }}</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Receive Goods</span>
        </div>
    </x-slot>

    <div class="mb-6">
        <h1 class="font-display text-xl font-bold text-content">Receive Goods</h1>
        <p class="text-xs text-content-secondary mt-0.5">Record goods receipt for <span class="font-mono">{{ $purchaseOrder->po_number }}</span></p>
    </div>

            <form method="POST" action="{{ route('admin.purchase-orders.receive.store', $purchaseOrder) }}"
                  class="space-y-6">
                @csrf

                {{-- Vendor & Warehouse --}}
                <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs text-content-muted">Vendor</p>
                            <p class="text-sm font-semibold text-content">{{ $purchaseOrder->vendor->name }}</p>
                        </div>
                        <div>
                            <label for="warehouse_id" class="mb-1 block text-sm font-medium text-content">Receive at Warehouse <span class="text-danger-500">*</span></label>
                            <select name="warehouse_id" id="warehouse_id" required
                                    class="w-full rounded-lg border-surface-border text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" @selected($wh->is_default)>{{ $wh->name }} ({{ $wh->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Line Items to Receive --}}
                <div class="rounded-xl border border-surface-border bg-white shadow-card">
                    <div class="border-b border-surface-border px-6 py-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Items to Receive</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-surface-border">
                            <thead class="bg-surface-secondary">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">SKU</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Ordered</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Already Received</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Pending</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Qty Received</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Accepted</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Rejected</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Rejection Reason</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-border-light">
                                @foreach ($purchaseOrder->lines as $i => $line)
                                    @php
                                        $pending = max(0, $line->quantity_requested - $line->quantity_received);
                                    @endphp
                                    @if ($pending > 0 || $line->status !== 'received')
                                        <tr class="hover:bg-surface-secondary/50 transition"
                                            x-data="{ received: 0, accepted: 0, rejected: 0 }"
                                            x-init="$watch('received', val => { accepted = Math.max(0, val - rejected); }); $watch('rejected', val => { accepted = Math.max(0, received - val); })">
                                            <td class="px-4 py-3">
                                                <input type="hidden" name="lines[{{ $i }}][po_line_id]" value="{{ $line->id }}">
                                                <p class="text-sm font-mono font-medium text-content">{{ $line->sku->sku_code }}</p>
                                                <p class="text-xs text-content-secondary">{{ $line->sku->short_name }}</p>
                                            </td>
                                            <td class="px-4 py-3 text-right text-sm text-content">{{ $line->quantity_requested }}</td>
                                            <td class="px-4 py-3 text-right text-sm text-content-secondary">{{ $line->quantity_received }}</td>
                                            <td class="px-4 py-3 text-right text-sm font-medium {{ $pending > 0 ? 'text-warning-500' : 'text-success-500' }}">
                                                {{ $pending }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="number" name="lines[{{ $i }}][quantity_received]"
                                                       x-model.number="received"
                                                       min="0" max="{{ $pending }}" value="0"
                                                       class="w-20 mx-auto block rounded-lg border-surface-border text-center text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="number" name="lines[{{ $i }}][quantity_accepted]"
                                                       x-model.number="accepted"
                                                       min="0" :max="received" value="0"
                                                       class="w-20 mx-auto block rounded-lg border-surface-border text-center text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="number" name="lines[{{ $i }}][quantity_rejected]"
                                                       x-model.number="rejected"
                                                       min="0" :max="received" value="0"
                                                       class="w-20 mx-auto block rounded-lg border-surface-border text-center text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="text" name="lines[{{ $i }}][rejection_reason]"
                                                       placeholder="If rejected..."
                                                       class="w-full rounded-lg border-surface-border text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold transition-colors">Record Goods Receipt</button>
                    <a href="{{ route('admin.purchase-orders.show', $purchaseOrder) }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold transition-colors">Cancel</a>
                </div>
            </form>
</x-admin-layout>
