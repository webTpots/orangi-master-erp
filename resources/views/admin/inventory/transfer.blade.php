<x-admin-layout>
    <x-slot name="title">Stock Transfer</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.inventory.index') }}" class="text-content-secondary hover:text-content">Inventory</a>
            <svg class="w-4 h-4 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span>Transfer Stock</span>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <form method="POST" action="{{ route('admin.inventory.transfer.store') }}" class="space-y-6">
            @csrf

            <div class="card p-6 space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Transfer Details</h3>

                {{-- SKU Select --}}
                <div>
                    <label for="sku_id" class="mb-1 block text-sm font-medium text-content">SKU <span class="text-danger-500">*</span></label>
                    <select name="sku_id" id="sku_id" required
                            class="w-full rounded-lg border-surface-border text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        <option value="">Select SKU</option>
                        @foreach($skus as $sku)
                            <option value="{{ $sku->id }}" @selected(old('sku_id') == $sku->id)>
                                {{ $sku->sku_code }} - {{ $sku->variant?->product?->design?->name }} {{ $sku->variant?->color }} {{ $sku->variant?->size }}
                                (Avail: {{ $sku->inventoryItems->sum('available_stock') }})
                            </option>
                        @endforeach
                    </select>
                    @error('sku_id') <p class="mt-1 text-xs text-danger-500">{{ $message }}</p> @enderror
                </div>

                {{-- From Warehouse --}}
                <div>
                    <label for="from_warehouse_id" class="mb-1 block text-sm font-medium text-content">From Warehouse <span class="text-danger-500">*</span></label>
                    <select name="from_warehouse_id" id="from_warehouse_id" required
                            class="w-full rounded-lg border-surface-border text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        <option value="">Select Source Warehouse</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('from_warehouse_id') == $wh->id)>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                    @error('from_warehouse_id') <p class="mt-1 text-xs text-danger-500">{{ $message }}</p> @enderror
                </div>

                {{-- To Warehouse --}}
                <div>
                    <label for="to_warehouse_id" class="mb-1 block text-sm font-medium text-content">To Warehouse <span class="text-danger-500">*</span></label>
                    <select name="to_warehouse_id" id="to_warehouse_id" required
                            class="w-full rounded-lg border-surface-border text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        <option value="">Select Destination Warehouse</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('to_warehouse_id') == $wh->id)>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                    @error('to_warehouse_id') <p class="mt-1 text-xs text-danger-500">{{ $message }}</p> @enderror
                </div>

                {{-- Quantity --}}
                <div>
                    <label for="quantity" class="mb-1 block text-sm font-medium text-content">Quantity <span class="text-danger-500">*</span></label>
                    <input type="number" name="quantity" id="quantity" min="1" required value="{{ old('quantity') }}"
                           class="w-full rounded-lg border-surface-border text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    @error('quantity') <p class="mt-1 text-xs text-danger-500">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.inventory.index') }}"
                   class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">
                    Cancel
                </a>
                <button type="submit"
                        class="rounded-lg bg-brand-500 px-6 py-2 text-sm font-medium text-white shadow-brand hover:bg-brand-600 transition"
                        onclick="return confirm('Confirm stock transfer?')">
                    Transfer Stock
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
