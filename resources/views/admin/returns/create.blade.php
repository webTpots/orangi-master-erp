<x-admin-layout>
    <x-slot name="title">Initiate Return</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.returns.index') }}" class="text-content-secondary hover:text-content transition-colors">Returns</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Initiate Return</span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <h1 class="font-display text-xl font-bold text-content mb-6">Initiate Return</h1>

            <form method="POST" action="{{ route('admin.returns.store') }}" class="space-y-6">
                @csrf

                {{-- Order Selection --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Order</h3>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-content">Select Order</label>
                        <select name="order_id" required
                                class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            <option value="">-- Select an Order --</option>
                            @foreach ($orders as $o)
                                <option value="{{ $o->id }}" @selected(old('order_id', $order?->id) == $o->id)>
                                    {{ $o->marketplace_order_id }} - {{ $o->customer_name ?? 'N/A' }} ({{ $o->order_date->format('d M Y') }})
                                </option>
                            @endforeach
                        </select>
                        @error('order_id') <p class="text-xs text-danger-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Return Details --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Return Details</h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-content">Return Type</label>
                            <select name="return_type" required
                                    class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                <option value="">-- Select Type --</option>
                                @foreach (\App\Models\ReturnOrder::TYPE_LABELS as $val => $label)
                                    <option value="{{ $val }}" @selected(old('return_type') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('return_type') <p class="text-xs text-danger-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-content">Reason Category</label>
                            <select name="reason_category" required
                                    class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                <option value="">-- Select Reason --</option>
                                @foreach (\App\Models\ReturnOrder::REASON_LABELS as $val => $label)
                                    <option value="{{ $val }}" @selected(old('reason_category') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('reason_category') <p class="text-xs text-danger-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-content">Reason Detail</label>
                            <textarea name="reason_detail" rows="3"
                                      class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400"
                                      placeholder="Additional details about the return reason...">{{ old('reason_detail') }}</textarea>
                            @error('reason_detail') <p class="text-xs text-danger-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Shipping Info --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Shipping Information</h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-content">Marketplace Return ID</label>
                            <input type="text" name="marketplace_return_id" value="{{ old('marketplace_return_id') }}"
                                   placeholder="Marketplace reference..."
                                   class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-content">Tracking Number</label>
                            <input type="text" name="tracking_number" value="{{ old('tracking_number') }}"
                                   placeholder="Return shipment tracking..."
                                   class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-content">Courier</label>
                            <input type="text" name="courier_name" value="{{ old('courier_name') }}"
                                   placeholder="Courier partner..."
                                   class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">
                        </div>
                    </div>
                </div>

                {{-- Items to Return (if order is pre-selected) --}}
                @if ($order && $order->subOrders->isNotEmpty())
                    <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Items to Return</h3>
                        <div class="space-y-3">
                            @foreach ($order->subOrders as $index => $sub)
                                @if ($sub->sku_id)
                                    <div class="flex items-center gap-4 p-3 rounded-lg bg-surface-secondary">
                                        <input type="hidden" name="items[{{ $index }}][sku_id]" value="{{ $sub->sku_id }}">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-content">{{ $sub->sku->sku_code ?? $sub->marketplace_sku }}</p>
                                            <p class="text-xs text-content-muted">{{ $sub->product_name ?? '' }} {{ $sub->size ? '/ ' . $sub->size : '' }}</p>
                                        </div>
                                        <div class="w-20">
                                            <input type="number" name="items[{{ $index }}][quantity]" value="{{ $sub->quantity }}" min="1" max="{{ $sub->quantity }}"
                                                   class="w-full rounded-lg border-surface-border bg-white text-sm text-content text-center focus:border-brand-400 focus:ring-brand-400">
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Submit --}}
                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 transition">
                        Initiate Return
                    </button>
                    <a href="{{ route('admin.returns.index') }}" class="rounded-lg border border-surface-border px-6 py-2.5 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
