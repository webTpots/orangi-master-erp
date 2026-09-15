<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.shipments.index') }}" class="text-content-muted hover:text-content transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                </a>
                <h2 class="text-xl font-display font-semibold text-content">Shipment <span class="font-mono text-base">{{ \Illuminate\Support\Str::limit($shipment->tracking_number, 22) }}</span></h2>
                @php
                    $statusColors = [
                        'created'          => 'bg-neutral-50 text-neutral-500',
                        'picked_up'        => 'bg-warning-50 text-warning-600',
                        'in_transit'       => 'bg-brand-50 text-brand-500',
                        'out_for_delivery' => 'bg-brand-50 text-brand-600',
                        'delivered'        => 'bg-success-50 text-success-600',
                        'rto_initiated'    => 'bg-danger-50 text-danger-500',
                        'rto_in_transit'   => 'bg-danger-50 text-danger-600',
                        'rto_delivered'    => 'bg-danger-50 text-danger-700',
                        'lost'             => 'bg-danger-50 text-danger-500',
                        'damaged'          => 'bg-warning-50 text-warning-700',
                    ];
                @endphp
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $statusColors[$shipment->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                    {{ $shipment->status_label }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                @if ($shipment->status === 'created')
                    <form method="POST" action="{{ route('admin.shipments.dispatch', $shipment) }}" class="inline">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('Mark this shipment as dispatched?')"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 text-white hover:bg-brand-600 px-3 py-1.5 text-sm font-medium transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Dispatch
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Shipment Info Card --}}
        <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Shipment Details</h3>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm lg:grid-cols-4">
                <div><dt class="text-content-muted">Tracking Number</dt><dd class="font-mono font-medium text-content">{{ $shipment->tracking_number }}</dd></div>
                <div><dt class="text-content-muted">AWB Number</dt><dd class="font-mono font-medium text-content">{{ $shipment->awb_number ?? '-' }}</dd></div>
                <div><dt class="text-content-muted">Courier</dt><dd class="font-medium text-content">{{ $shipment->courier_name }}</dd></div>
                <div><dt class="text-content-muted">Courier Code</dt><dd class="font-mono text-content">{{ $shipment->courier_code ?? '-' }}</dd></div>
                <div><dt class="text-content-muted">Shipped At</dt><dd class="font-medium text-content">{{ $shipment->shipped_at?->format('d M Y, h:i A') ?? '-' }}</dd></div>
                <div><dt class="text-content-muted">Delivered At</dt><dd class="font-medium text-content">{{ $shipment->delivered_at?->format('d M Y, h:i A') ?? '-' }}</dd></div>
                <div><dt class="text-content-muted">Weight</dt><dd class="font-medium text-content">{{ $shipment->weight_grams ? $shipment->weight_grams . 'g' : '-' }}</dd></div>
                <div><dt class="text-content-muted">Est. Delivery</dt><dd class="font-medium text-content">{{ $shipment->estimated_delivery_at?->format('d M Y') ?? '-' }}</dd></div>
            </dl>
            @if ($shipment->notes)
                <div class="mt-4 pt-4 border-t border-surface-border">
                    <dt class="text-xs text-content-muted mb-1">Notes</dt>
                    <dd class="text-sm text-content">{{ $shipment->notes }}</dd>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Scan Timeline --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-6">Tracking Timeline</h3>
                    @if ($timeline->isNotEmpty())
                        <div class="relative">
                            @foreach ($timeline as $scan)
                                @php
                                    $scanIcons = [
                                        'pickup'           => ['icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4', 'color' => 'bg-brand-500'],
                                        'in_transit'       => ['icon' => 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0', 'color' => 'bg-brand-500'],
                                        'hub'              => ['icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'color' => 'bg-brand-400'],
                                        'out_for_delivery' => ['icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z', 'color' => 'bg-brand-600'],
                                        'delivered'        => ['icon' => 'M5 13l4 4L19 7', 'color' => 'bg-success-500'],
                                        'rto_initiated'    => ['icon' => 'M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3', 'color' => 'bg-danger-500'],
                                        'rto_pickup'       => ['icon' => 'M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3', 'color' => 'bg-danger-400'],
                                        'rto_in_transit'   => ['icon' => 'M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3', 'color' => 'bg-danger-500'],
                                        'rto_delivered'    => ['icon' => 'M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3', 'color' => 'bg-danger-600'],
                                        'exception'        => ['icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'color' => 'bg-warning-500'],
                                        'damaged'          => ['icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'color' => 'bg-danger-500'],
                                        'lost'             => ['icon' => 'M6 18L18 6M6 6l12 12', 'color' => 'bg-danger-500'],
                                    ];
                                    $scanInfo = $scanIcons[$scan->scan_type] ?? ['icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'bg-neutral-400'];
                                @endphp
                                <div class="flex gap-4 {{ !$loop->last ? 'pb-6' : '' }}">
                                    <div class="flex flex-col items-center">
                                        <div class="w-8 h-8 rounded-full {{ $scanInfo['color'] }} flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $scanInfo['icon'] }}"/></svg>
                                        </div>
                                        @if (!$loop->last)
                                            <div class="w-px flex-1 bg-surface-border mt-1"></div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0 {{ !$loop->last ? 'pb-2' : '' }}">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-medium text-content">{{ $scan->scan_type_label }}</p>
                                            <p class="text-xs text-content-muted">{{ $scan->scanned_at->format('d M Y, h:i A') }}</p>
                                        </div>
                                        @if ($scan->status_description)
                                            <p class="text-sm text-content-secondary mt-0.5">{{ $scan->status_description }}</p>
                                        @endif
                                        @if ($scan->location || $scan->city)
                                            <p class="text-xs text-content-muted mt-0.5">
                                                <svg class="inline w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                                {{ collect([$scan->location, $scan->city, $scan->state])->filter()->implode(', ') }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-content-muted">No tracking scans recorded.</p>
                    @endif
                </div>

                {{-- Order Details --}}
                @if ($shipment->order)
                    <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Linked Order</h3>
                            <a href="{{ route('admin.orders.show', $shipment->order) }}" class="text-xs text-brand-500 hover:underline">View Order</a>
                        </div>
                        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div><dt class="text-content-muted">Order ID</dt><dd class="font-mono font-medium text-content">{{ $shipment->order->marketplace_order_id }}</dd></div>
                            <div><dt class="text-content-muted">Customer</dt><dd class="font-medium text-content">{{ $shipment->order->customer_name ?? '-' }}</dd></div>
                            <div><dt class="text-content-muted">Order Date</dt><dd class="font-medium text-content">{{ $shipment->order->order_date->format('d M Y') }}</dd></div>
                            <div><dt class="text-content-muted">Payment</dt><dd class="font-medium text-content">{{ $shipment->order->payment_type ? strtoupper($shipment->order->payment_type) : '-' }}</dd></div>
                            <div><dt class="text-content-muted">Total Amount</dt><dd class="font-semibold text-content">Rs. {{ number_format($shipment->order->total_amount, 2) }}</dd></div>
                            <div>
                                <dt class="text-content-muted">Order Status</dt>
                                <dd>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-brand-50 text-brand-600">
                                        {{ $shipment->order->status_label }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">

                {{-- Pickup Address --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Pickup Address</h3>
                    @if ($shipment->pickup_address_json)
                        <dl class="space-y-2 text-sm">
                            @foreach ($shipment->pickup_address_json as $key => $value)
                                @if ($value)
                                    <div><dt class="text-content-muted capitalize">{{ str_replace('_', ' ', $key) }}</dt><dd class="font-medium text-content">{{ $value }}</dd></div>
                                @endif
                            @endforeach
                        </dl>
                    @else
                        <p class="text-sm text-content-muted">Not available</p>
                    @endif
                </div>

                {{-- Delivery Address --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Delivery Address</h3>
                    @if ($shipment->delivery_address_json)
                        <dl class="space-y-2 text-sm">
                            @foreach ($shipment->delivery_address_json as $key => $value)
                                @if ($value)
                                    <div><dt class="text-content-muted capitalize">{{ str_replace('_', ' ', $key) }}</dt><dd class="font-medium text-content">{{ $value }}</dd></div>
                                @endif
                            @endforeach
                        </dl>
                    @else
                        <p class="text-sm text-content-muted">Not available</p>
                    @endif
                </div>

                {{-- Last Scan Info --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Last Scan</h3>
                    <dl class="space-y-2 text-sm">
                        <div><dt class="text-content-muted">Time</dt><dd class="font-medium text-content">{{ $shipment->last_scan_at?->format('d M Y, h:i A') ?? '-' }}</dd></div>
                        <div><dt class="text-content-muted">Location</dt><dd class="font-medium text-content">{{ $shipment->last_scan_location ?? '-' }}</dd></div>
                        <div><dt class="text-content-muted">Status</dt><dd class="font-medium text-content">{{ $shipment->last_scan_status ?? '-' }}</dd></div>
                    </dl>
                </div>

            </div>
        </div>
    </div>
</x-admin-layout>
