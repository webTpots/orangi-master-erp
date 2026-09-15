<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.manifests.index') }}" class="text-content-muted hover:text-content transition">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            </a>
            <h2 class="text-xl font-display font-semibold text-content">
                Manifest: {{ \Illuminate\Support\Str::limit($manifest->file_name, 30) }}
            </h2>
            @php
                $statusColor = match($manifest->status) {
                    'completed' => 'bg-success-50 text-success-500',
                    'processing' => 'bg-warning-50 text-warning-500',
                    'failed' => 'bg-danger-50 text-danger-500',
                    default => 'bg-neutral-50 text-neutral-500',
                };
            @endphp
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $statusColor }}">
                {{ ucfirst($manifest->status) }}
            </span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Manifest Info --}}
            <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                <div class="grid grid-cols-2 gap-6 sm:grid-cols-4 text-sm">
                    <div>
                        <dt class="text-content-muted">Marketplace</dt>
                        <dd class="font-medium text-content">{{ $manifest->marketplace->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-content-muted">Manifest Date</dt>
                        <dd class="font-medium text-content">{{ $manifest->manifest_date?->format('d M Y') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-content-muted">Supplier</dt>
                        <dd class="font-medium text-content">{{ $manifest->supplier_name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-content-muted">Imported</dt>
                        <dd class="font-medium text-content">{{ $manifest->created_at->format('d M Y, h:i A') }}</dd>
                    </div>
                </div>
            </div>

            {{-- Reconciliation Stats (shown on reconcile view) --}}
            @if (isset($stats))
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
                    <div class="rounded-xl border border-surface-border bg-white p-4 shadow-card">
                        <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Total Lines</p>
                        <p class="mt-1 text-2xl font-semibold text-content">{{ $stats['total'] }}</p>
                    </div>
                    <div class="rounded-xl border border-surface-border bg-white p-4 shadow-card">
                        <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Fully Matched</p>
                        <p class="mt-1 text-2xl font-semibold text-success-500">{{ $stats['fully_matched'] }}</p>
                    </div>
                    <div class="rounded-xl border border-surface-border bg-white p-4 shadow-card">
                        <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Order Only</p>
                        <p class="mt-1 text-2xl font-semibold text-brand-500">{{ $stats['matched_order'] }}</p>
                    </div>
                    <div class="rounded-xl border border-surface-border bg-white p-4 shadow-card">
                        <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Label Only</p>
                        <p class="mt-1 text-2xl font-semibold text-warning-500">{{ $stats['matched_label'] }}</p>
                    </div>
                    <div class="rounded-xl border border-surface-border bg-white p-4 shadow-card">
                        <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Unmatched</p>
                        <p class="mt-1 text-2xl font-semibold text-danger-500">{{ $stats['unmatched'] }}</p>
                    </div>
                </div>
            @endif

            {{-- Reconciliation Table (shown on reconcile view) --}}
            @if (isset($reconciliation))
                <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
                    <div class="px-6 py-4 border-b border-surface-border">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Reconciliation</h3>
                    </div>
                    <table class="min-w-full divide-y divide-surface-border">
                        <thead class="bg-surface-secondary">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">S.No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Sub Order #</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">AWB</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Courier</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">SKU</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Order</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Label</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Match</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border-light">
                            @foreach ($reconciliation as $i => $entry)
                                @php
                                    $matchColor = match($entry['status']) {
                                        'fully_matched' => 'bg-success-50 text-success-500',
                                        'matched_order' => 'bg-brand-50 text-brand-500',
                                        'matched_label' => 'bg-warning-50 text-warning-500',
                                        default => 'bg-danger-50 text-danger-500',
                                    };
                                    $matchLabel = match($entry['status']) {
                                        'fully_matched' => 'Full',
                                        'matched_order' => 'Order',
                                        'matched_label' => 'Label',
                                        default => 'None',
                                    };
                                @endphp
                                <tr class="hover:bg-surface-secondary/50 transition">
                                    <td class="px-4 py-3 text-sm text-content-muted">{{ $entry['shipment_line']->serial_number ?? $i + 1 }}</td>
                                    <td class="px-4 py-3 text-xs font-mono text-content">{{ $entry['shipment_line']->sub_order_number ? \Illuminate\Support\Str::limit($entry['shipment_line']->sub_order_number, 20) : '-' }}</td>
                                    <td class="px-4 py-3 text-xs font-mono text-content">{{ $entry['shipment_line']->awb ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-content-secondary">{{ $entry['shipment_line']->courier ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-content">{{ $entry['shipment_line']->sku ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($entry['order'])
                                            <a href="{{ route('admin.orders.show', $entry['order']) }}" class="text-brand-500 hover:underline text-xs">
                                                #{{ $entry['order']->id }}
                                            </a>
                                        @else
                                            <span class="text-content-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($entry['label'])
                                            <a href="{{ route('admin.labels.show', $entry['label']) }}" class="text-brand-500 hover:underline text-xs">
                                                #{{ $entry['label']->id }}
                                            </a>
                                        @else
                                            <span class="text-content-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $matchColor }}">
                                            {{ $matchLabel }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Picklist --}}
            <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
                <div class="px-6 py-4 border-b border-surface-border">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Picklist ({{ $manifest->picklistLines->count() }} items)</h3>
                </div>
                <table class="min-w-full divide-y divide-surface-border">
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">SKU</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Color</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Size</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Quantity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border-light">
                        @forelse ($manifest->picklistLines as $line)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-content">{{ $line->sku ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-content-secondary">{{ $line->color ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-sm text-content">{{ $line->size ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-sm font-medium text-content">{{ $line->quantity }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-content-muted">No picklist lines parsed.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($manifest->picklistLines->isNotEmpty())
                        <tfoot class="bg-surface-secondary">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Total</td>
                                <td class="px-4 py-3 text-center text-sm font-bold text-content">{{ $manifest->picklistLines->sum('quantity') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            {{-- Shipment Lines --}}
            <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
                <div class="px-6 py-4 border-b border-surface-border flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Courier Shipments ({{ $manifest->shipmentLines->count() }} lines)</h3>
                    @if (!isset($reconciliation))
                        <a href="{{ route('admin.manifests.reconcile', $manifest) }}"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-brand-500 px-3 py-1.5 text-xs font-medium text-brand-500 hover:bg-brand-50 transition">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                            Reconcile
                        </a>
                    @endif
                </div>
                <table class="min-w-full divide-y divide-surface-border">
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">S.No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Courier</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Sub Order #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">AWB</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">SKU</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Size</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Qty</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Packed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border-light">
                        @forelse ($manifest->shipmentLines as $line)
                            <tr class="hover:bg-surface-secondary/50 transition">
                                <td class="px-4 py-3 text-sm text-content-muted">{{ $line->serial_number ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-content">{{ $line->courier ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs font-mono text-content">{{ $line->sub_order_number ? \Illuminate\Support\Str::limit($line->sub_order_number, 20) : '-' }}</td>
                                <td class="px-4 py-3 text-xs font-mono text-content">{{ $line->awb ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-content">{{ $line->sku ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-sm text-content">{{ $line->size ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-sm text-content">{{ $line->quantity }}</td>
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $packedColor = match($line->packed_status) {
                                            'packed' => 'bg-success-50 text-success-500',
                                            'not_packed' => 'bg-danger-50 text-danger-500',
                                            'partially_packed' => 'bg-warning-50 text-warning-500',
                                            default => 'bg-neutral-50 text-neutral-400',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $packedColor }}">
                                        {{ ucfirst(str_replace('_', ' ', $line->packed_status)) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-6 text-center text-sm text-content-muted">No shipment lines parsed.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-admin-layout>
