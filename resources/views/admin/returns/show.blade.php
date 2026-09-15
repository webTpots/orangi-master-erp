<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.returns.index') }}" class="text-content-muted hover:text-content transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                </a>
                <h2 class="text-xl font-display font-semibold text-content">Return <span class="font-mono text-base">RTN-{{ str_pad($return->id, 5, '0', STR_PAD_LEFT) }}</span></h2>
                @php
                    $statusColors = [
                        'initiated' => 'bg-warning-50 text-warning-600',
                        'in_transit' => 'bg-brand-50 text-brand-500',
                        'received' => 'bg-brand-50 text-brand-600',
                        'inspecting' => 'bg-warning-50 text-warning-700',
                        'inspection_complete' => 'bg-brand-50 text-brand-700',
                        'restocked' => 'bg-success-50 text-success-500',
                        'rejected' => 'bg-danger-50 text-danger-500',
                        'disposed' => 'bg-danger-50 text-danger-600',
                        'claim_filed' => 'bg-warning-50 text-warning-600',
                        'claim_settled' => 'bg-success-50 text-success-600',
                        'closed' => 'bg-neutral-50 text-neutral-500',
                    ];
                    $typeColors = [
                        'customer_return' => 'bg-brand-50 text-brand-600',
                        'rto' => 'bg-danger-50 text-danger-600',
                        'exchange' => 'bg-warning-50 text-warning-600',
                        'replacement' => 'bg-success-50 text-success-600',
                    ];
                @endphp
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $statusColors[$return->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                    {{ $return->status_label }}
                </span>
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $typeColors[$return->return_type] ?? 'bg-neutral-50 text-neutral-500' }}">
                    {{ $return->type_label }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                @if (in_array('received', $availableTransitions) && in_array($return->status, ['initiated', 'in_transit']))
                    <form method="POST" action="{{ route('admin.returns.receive', $return) }}" class="inline">
                        @csrf
                        <button type="submit" onclick="return confirm('Mark this return as received?')"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 text-white hover:bg-brand-600 px-3 py-1.5 text-sm font-medium transition">
                            Mark Received
                        </button>
                    </form>
                @endif

                @if (in_array($return->status, ['received', 'inspecting']))
                    <a href="{{ route('admin.returns.inspect', $return) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 text-white hover:bg-brand-600 px-3 py-1.5 text-sm font-medium transition">
                        Inspect
                    </a>
                @endif

                @if (in_array('restocked', $availableTransitions))
                    <form method="POST" action="{{ route('admin.returns.restock', $return) }}" class="inline">
                        @csrf
                        <button type="submit" onclick="return confirm('Restock all eligible items?')"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-success-500 text-white hover:bg-success-600 px-3 py-1.5 text-sm font-medium transition">
                            Restock
                        </button>
                    </form>
                @endif

                @if (in_array('closed', $availableTransitions))
                    <form method="POST" action="{{ route('admin.returns.close', $return) }}" class="inline">
                        @csrf
                        <button type="submit" onclick="return confirm('Close this return?')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-surface-border text-content hover:bg-surface-secondary px-3 py-1.5 text-sm font-medium transition">
                            Close Return
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Main Content --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Order Details Card --}}
                    <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Linked Order</h3>
                        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div>
                                <dt class="text-content-muted">Order ID</dt>
                                <dd class="font-mono font-medium text-content">
                                    <a href="{{ route('admin.orders.show', $return->order) }}" class="text-brand-500 hover:underline">
                                        {{ $return->order->marketplace_order_id }}
                                    </a>
                                </dd>
                            </div>
                            <div><dt class="text-content-muted">Marketplace</dt><dd class="font-medium text-content">{{ $return->order->marketplace->name ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">Customer</dt><dd class="font-medium text-content">{{ $return->order->customer_name ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">Total Amount</dt><dd class="font-medium text-content">Rs. {{ number_format($return->order->total_amount, 2) }}</dd></div>
                            <div><dt class="text-content-muted">Courier</dt><dd class="font-medium text-content">{{ $return->courier_name ?? $return->order->courier_partner ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">Tracking #</dt><dd class="font-mono font-medium text-content">{{ $return->tracking_number ?? '-' }}</dd></div>
                        </dl>
                    </div>

                    {{-- Return Items Table --}}
                    <div class="rounded-xl border border-surface-border bg-white shadow-card">
                        <div class="px-6 py-4 border-b border-surface-border">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Return Items ({{ $return->items->count() }})</h3>
                        </div>
                        <table class="min-w-full divide-y divide-surface-border">
                            <thead class="bg-surface-secondary">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">SKU</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Qty</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Condition</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Restock</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Dispose</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-border-light">
                                @forelse ($return->items as $item)
                                    @php
                                        $conditionColors = [
                                            'like_new' => 'bg-success-50 text-success-600',
                                            'good' => 'bg-success-50 text-success-500',
                                            'minor_damage' => 'bg-warning-50 text-warning-600',
                                            'major_damage' => 'bg-danger-50 text-danger-500',
                                            'unsellable' => 'bg-danger-50 text-danger-600',
                                            'missing' => 'bg-neutral-50 text-neutral-500',
                                        ];
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-content">
                                            {{ $item->sku->sku_code ?? '-' }}
                                            @if ($item->variant)
                                                <span class="text-content-muted text-xs"> / {{ $item->variant->name ?? '' }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-content">{{ $item->quantity }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $conditionColors[$item->condition] ?? 'bg-neutral-50 text-neutral-500' }}">
                                                {{ \App\Models\ReturnItem::CONDITION_LABELS[$item->condition] ?? ucfirst($item->condition) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-success-600 font-medium">{{ $item->restock_quantity }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-danger-500 font-medium">{{ $item->dispose_quantity }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-content-muted">No items recorded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Inspection History --}}
                    @if ($return->inspections->isNotEmpty())
                        <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Inspection History</h3>
                            <div class="space-y-4">
                                @foreach ($return->inspections as $inspection)
                                    <div class="flex gap-3">
                                        <div class="flex flex-col items-center">
                                            <div class="w-2.5 h-2.5 rounded-full bg-brand-500 mt-1.5"></div>
                                            @if (! $loop->last)
                                                <div class="w-px flex-1 bg-surface-border"></div>
                                            @endif
                                        </div>
                                        <div class="pb-4">
                                            <div class="text-sm font-medium text-content">
                                                Condition: {{ \App\Models\ReturnInspection::CONDITION_LABELS[$inspection->condition] ?? $inspection->condition }}
                                                @if ($inspection->is_resellable)
                                                    <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-success-50 text-success-500">Resellable</span>
                                                @else
                                                    <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-danger-50 text-danger-500">Not Resellable</span>
                                                @endif
                                            </div>
                                            @if ($inspection->inspection_notes)
                                                <p class="text-xs text-content-muted mt-0.5">{{ $inspection->inspection_notes }}</p>
                                            @endif
                                            <p class="text-xs text-content-muted mt-0.5">
                                                {{ $inspection->inspected_at->format('d M Y, h:i A') }}
                                                @if ($inspection->inspector)
                                                    by {{ $inspection->inspector->name }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Linked Claims --}}
                    @if ($return->claims->isNotEmpty())
                        <div class="rounded-xl border border-surface-border bg-white shadow-card">
                            <div class="px-6 py-4 border-b border-surface-border">
                                <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Linked Claims ({{ $return->claims->count() }})</h3>
                            </div>
                            <table class="min-w-full divide-y divide-surface-border">
                                <thead class="bg-surface-secondary">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Claim #</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Type</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-surface-border-light">
                                    @foreach ($return->claims as $claim)
                                        <tr>
                                            <td class="px-4 py-3 text-sm">
                                                <a href="{{ route('admin.claims.show', $claim) }}" class="font-mono text-brand-500 hover:underline text-xs">CLM-{{ str_pad($claim->id, 5, '0', STR_PAD_LEFT) }}</a>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-content">{{ $claim->type_label }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-brand-50 text-brand-600">{{ $claim->status_label }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-right text-sm font-medium text-content">Rs. {{ number_format($claim->claimed_amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="space-y-6">

                    {{-- Return Info --}}
                    <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Return Details</h3>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-content-muted">Return Type</dt><dd class="font-medium text-content">{{ $return->type_label }}</dd></div>
                            <div><dt class="text-content-muted">Reason</dt><dd class="font-medium text-content">{{ $return->reason_label }}</dd></div>
                            @if ($return->reason_detail)
                                <div><dt class="text-content-muted">Detail</dt><dd class="text-content">{{ $return->reason_detail }}</dd></div>
                            @endif
                            @if ($return->marketplace_return_id)
                                <div><dt class="text-content-muted">Marketplace Return ID</dt><dd class="font-mono text-content">{{ $return->marketplace_return_id }}</dd></div>
                            @endif
                            <div class="border-t border-surface-border pt-2">
                                <dt class="text-content-muted">Initiated</dt>
                                <dd class="font-medium text-content">{{ $return->initiated_at?->format('d M Y, h:i A') ?? '-' }}</dd>
                            </div>
                            <div><dt class="text-content-muted">Received</dt><dd class="font-medium text-content">{{ $return->received_at?->format('d M Y, h:i A') ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">Inspected</dt><dd class="font-medium text-content">{{ $return->inspected_at?->format('d M Y, h:i A') ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">Closed</dt><dd class="font-medium text-content">{{ $return->closed_at?->format('d M Y, h:i A') ?? '-' }}</dd></div>
                        </dl>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Quick Actions</h3>
                        <div class="space-y-2">
                            @if (in_array('claim_filed', $availableTransitions))
                                <a href="{{ route('admin.claims.create', ['return_id' => $return->id]) }}"
                                   class="block w-full text-center rounded-lg border border-surface-border bg-white hover:bg-surface-secondary text-content px-4 py-2 text-sm font-medium transition">
                                    File a Claim
                                </a>
                            @endif
                            <a href="{{ route('admin.orders.show', $return->order) }}"
                               class="block w-full text-center rounded-lg border border-surface-border bg-white hover:bg-surface-secondary text-content px-4 py-2 text-sm font-medium transition">
                                View Order
                            </a>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-admin-layout>
