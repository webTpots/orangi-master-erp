<x-admin-layout>
    <x-slot name="title">{{ $purchaseOrder->po_number }}</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.purchase-orders.index') }}" class="text-content-secondary hover:text-content transition-colors">Purchase Orders</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content font-mono">{{ $purchaseOrder->po_number }}</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <h1 class="font-display text-xl font-bold text-content font-mono">{{ $purchaseOrder->po_number }}</h1>
            @php
                $colorMap = [
                    'draft' => 'bg-neutral-50 text-neutral-500',
                    'sent' => 'bg-warning-50 text-warning-500',
                    'partially_confirmed' => 'bg-warning-50 text-warning-600',
                    'confirmed' => 'bg-success-50 text-success-500',
                    'partially_received' => 'bg-brand-50 text-brand-500',
                    'received' => 'bg-success-50 text-success-600',
                    'closed' => 'bg-neutral-50 text-neutral-500',
                    'cancelled' => 'bg-danger-50 text-danger-500',
                ];
            @endphp
            <span class="text-xs font-semibold px-2 py-0.5 rounded-md {{ $colorMap[$purchaseOrder->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                {{ $purchaseOrder->status_label }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            @if ($purchaseOrder->canBeApproved())
                <form method="POST" action="{{ route('admin.purchase-orders.approve', $purchaseOrder) }}" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('Approve this PO and mark as Sent?')"
                            class="bg-success-500 hover:bg-success-600 text-white rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Approve & Send
                    </button>
                </form>
            @endif
            @if ($purchaseOrder->canReceiveGoods())
                <a href="{{ route('admin.purchase-orders.receive', $purchaseOrder) }}"
                   class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                    Receive Goods
                </a>
            @endif
            @if ($purchaseOrder->canBeCancelled())
                <button type="button" x-data x-on:click="$dispatch('open-cancel-modal')"
                        class="border border-danger-500 text-danger-500 hover:bg-danger-50 rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                    Cancel PO
                </button>
            @endif
        </div>
    </div>

    <div class="space-y-6">

            {{-- Status Timeline --}}
            <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                @php
                    $steps = ['draft' => 'Draft', 'sent' => 'Sent', 'confirmed' => 'Confirmed', 'received' => 'Received', 'closed' => 'Closed'];
                    $currentIndex = array_search(
                        in_array($purchaseOrder->status, ['partially_confirmed']) ? 'confirmed' :
                        (in_array($purchaseOrder->status, ['partially_received']) ? 'received' : $purchaseOrder->status),
                        array_keys($steps)
                    );
                    if ($currentIndex === false) $currentIndex = -1;
                @endphp
                <div class="flex items-center justify-between">
                    @foreach ($steps as $key => $label)
                        @php $stepIndex = array_search($key, array_keys($steps)); @endphp
                        <div class="flex flex-col items-center flex-1">
                            <div class="flex items-center w-full">
                                @if ($stepIndex > 0)
                                    <div class="flex-1 h-0.5 {{ $stepIndex <= $currentIndex ? 'bg-brand-500' : 'bg-surface-border' }}"></div>
                                @else
                                    <div class="flex-1"></div>
                                @endif
                                <div class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold
                                    {{ $stepIndex < $currentIndex ? 'bg-brand-500 text-white' : ($stepIndex === $currentIndex ? 'bg-brand-500 text-white ring-4 ring-brand-100' : 'bg-surface-secondary text-content-muted border border-surface-border') }}">
                                    @if ($stepIndex < $currentIndex)
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                    @else
                                        {{ $stepIndex + 1 }}
                                    @endif
                                </div>
                                @if ($stepIndex < count($steps) - 1)
                                    <div class="flex-1 h-0.5 {{ $stepIndex < $currentIndex ? 'bg-brand-500' : 'bg-surface-border' }}"></div>
                                @else
                                    <div class="flex-1"></div>
                                @endif
                            </div>
                            <span class="mt-2 text-xs font-medium {{ $stepIndex <= $currentIndex ? 'text-brand-500' : 'text-content-muted' }}">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
                @if ($purchaseOrder->isCancelled())
                    <div class="mt-4 rounded-lg border border-danger-border bg-danger-50 p-3 text-sm text-danger-500">
                        <strong>Cancelled</strong> on {{ $purchaseOrder->cancelled_at?->format('d M Y H:i') }}
                        @if ($purchaseOrder->cancelled_reason)
                            &mdash; {{ $purchaseOrder->cancelled_reason }}
                        @endif
                    </div>
                @endif
            </div>

            {{-- PO Info --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-content-secondary">Vendor</h4>
                    <p class="text-sm font-semibold text-content">{{ $purchaseOrder->vendor->name }}</p>
                    <p class="mt-1 text-sm text-content-secondary">{{ $purchaseOrder->vendor->contact_person }}</p>
                    <p class="text-sm text-content-secondary">{{ $purchaseOrder->vendor->phone }}</p>
                    <p class="text-sm text-content-secondary">{{ $purchaseOrder->vendor->email }}</p>
                </div>
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-content-secondary">Order Details</h4>
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between"><span class="text-content-secondary">Order Date</span><span class="font-medium text-content">{{ $purchaseOrder->order_date->format('d M Y') }}</span></div>
                        <div class="flex justify-between"><span class="text-content-secondary">Expected Delivery</span><span class="font-medium text-content">{{ $purchaseOrder->expected_delivery_date?->format('d M Y') ?? '-' }}</span></div>
                        <div class="flex justify-between"><span class="text-content-secondary">Total Amount</span><span class="font-bold text-brand-500">{{ number_format($purchaseOrder->total_amount, 2) }}</span></div>
                    </div>
                </div>
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-content-secondary">Activity</h4>
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between"><span class="text-content-secondary">Created by</span><span class="font-medium text-content">{{ $purchaseOrder->creator->name ?? '-' }}</span></div>
                        <div class="flex justify-between"><span class="text-content-secondary">Created at</span><span class="font-medium text-content">{{ $purchaseOrder->created_at->format('d M Y H:i') }}</span></div>
                        @if ($purchaseOrder->approver)
                            <div class="flex justify-between"><span class="text-content-secondary">Approved by</span><span class="font-medium text-content">{{ $purchaseOrder->approver->name }}</span></div>
                            <div class="flex justify-between"><span class="text-content-secondary">Approved at</span><span class="font-medium text-content">{{ $purchaseOrder->approved_at?->format('d M Y H:i') }}</span></div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            @if ($purchaseOrder->notes)
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h4 class="mb-2 text-xs font-semibold uppercase tracking-wider text-content-secondary">Notes</h4>
                    <p class="text-sm text-content">{{ $purchaseOrder->notes }}</p>
                </div>
            @endif

            {{-- Lines Table --}}
            <div class="rounded-xl border border-surface-border bg-white shadow-card">
                <div class="border-b border-surface-border px-6 py-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Order Lines</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-surface-border">
                        <thead class="bg-surface-secondary">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">SKU</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Requested</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Confirmed</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Received</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Shortage</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Unit Cost</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Line Total</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border-light">
                            @foreach ($purchaseOrder->lines as $i => $line)
                                @php
                                    $shortage = $shortages[$i]['shortage'] ?? 0;
                                    $lineColorMap = [
                                        'pending' => 'bg-neutral-50 text-neutral-500',
                                        'confirmed' => 'bg-success-50 text-success-500',
                                        'partial' => 'bg-warning-50 text-warning-500',
                                        'received' => 'bg-success-50 text-success-600',
                                        'cancelled' => 'bg-danger-50 text-danger-500',
                                    ];
                                @endphp
                                <tr class="hover:bg-surface-secondary/50 transition">
                                    <td class="px-4 py-3 text-sm text-content-muted">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-mono font-medium text-content">{{ $line->sku->sku_code }}</p>
                                        <p class="text-xs text-content-secondary">{{ $line->sku->short_name }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-content">{{ $line->quantity_requested }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-content">{{ $line->quantity_confirmed }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-content">{{ $line->quantity_received }}</td>
                                    <td class="px-4 py-3 text-right text-sm {{ $shortage > 0 ? 'font-semibold text-danger-500' : 'text-content-muted' }}">
                                        {{ $shortage > 0 ? $shortage : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-content">{{ number_format($line->unit_cost, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-medium text-content">{{ number_format($line->line_total, 2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $lineColorMap[$line->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                            {{ ucfirst($line->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-surface-secondary">
                                <td colspan="7" class="px-4 py-3 text-right text-sm font-semibold text-content">Grand Total</td>
                                <td class="px-4 py-3 text-right text-sm font-bold text-brand-500">{{ number_format($purchaseOrder->total_amount, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Goods Receipts --}}
            <div class="rounded-xl border border-surface-border bg-white shadow-card">
                <div class="border-b border-surface-border px-6 py-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Goods Receipts</h3>
                    @if ($purchaseOrder->canReceiveGoods())
                        <a href="{{ route('admin.purchase-orders.receive', $purchaseOrder) }}"
                           class="text-sm font-medium text-brand-500 hover:text-brand-600 transition">
                            + Add Receipt
                        </a>
                    @endif
                </div>
                @if ($purchaseOrder->receipts->count() > 0)
                    <div class="divide-y divide-surface-border">
                        @foreach ($purchaseOrder->receipts as $receipt)
                            <div class="px-6 py-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <span class="text-sm font-mono font-medium text-content">{{ $receipt->receipt_number }}</span>
                                        <span class="ml-2 text-xs text-content-muted">{{ $receipt->receipt_date->format('d M Y') }}</span>
                                    </div>
                                    <div class="text-xs text-content-secondary">
                                        Received by {{ $receipt->receiver->name ?? '-' }} at {{ $receipt->warehouse->name ?? '-' }}
                                    </div>
                                </div>
                                @if ($receipt->lines->count())
                                    <table class="min-w-full text-xs">
                                        <thead>
                                            <tr class="text-content-muted">
                                                <th class="pb-1 text-left font-medium">SKU</th>
                                                <th class="pb-1 text-right font-medium">Received</th>
                                                <th class="pb-1 text-right font-medium">Accepted</th>
                                                <th class="pb-1 text-right font-medium">Rejected</th>
                                                <th class="pb-1 text-left font-medium">Reason</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-content-secondary">
                                            @foreach ($receipt->lines as $rl)
                                                <tr>
                                                    <td class="py-0.5 font-mono">{{ $rl->sku->sku_code ?? $rl->sku_id }}</td>
                                                    <td class="py-0.5 text-right">{{ $rl->quantity_received }}</td>
                                                    <td class="py-0.5 text-right text-success-500">{{ $rl->quantity_accepted }}</td>
                                                    <td class="py-0.5 text-right {{ $rl->quantity_rejected > 0 ? 'text-danger-500' : '' }}">{{ $rl->quantity_rejected }}</td>
                                                    <td class="py-0.5">{{ $rl->rejection_reason ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-6 py-8 text-center text-sm text-content-muted">No goods receipts recorded yet.</div>
                @endif
            </div>

    </div>

    {{-- Cancel Modal --}}
    @if ($purchaseOrder->canBeCancelled())
        <div x-data="{ open: false }" x-on:open-cancel-modal.window="open = true">
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                <div @click.outside="open = false" class="w-full max-w-md rounded-xl bg-white p-6 shadow-modal">
                    <h3 class="text-lg font-semibold text-content">Cancel Purchase Order</h3>
                    <p class="mt-1 text-sm text-content-secondary">This action cannot be undone. Please provide a reason.</p>
                    <form method="POST" action="{{ route('admin.purchase-orders.cancel', $purchaseOrder) }}" class="mt-4">
                        @csrf
                        <textarea name="reason" rows="3" required placeholder="Reason for cancellation..."
                                  class="w-full rounded-lg border-surface-border text-sm text-content focus:border-brand-400 focus:ring-brand-400"></textarea>
                        <div class="mt-4 flex justify-end gap-2">
                            <button type="button" @click="open = false"
                                    class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">
                                Keep PO
                            </button>
                            <button type="submit"
                                    class="rounded-lg bg-danger-500 px-4 py-2 text-sm font-medium text-white hover:bg-danger-600 transition">
                                Cancel PO
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</x-admin-layout>
