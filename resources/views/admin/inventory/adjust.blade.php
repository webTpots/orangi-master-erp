<x-admin-layout>
    <x-slot name="title">Stock Adjustment</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.inventory.index') }}" class="text-content-secondary hover:text-content">Inventory</a>
            <svg class="w-4 h-4 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span>Adjust Stock</span>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <form method="POST" action="{{ route('admin.inventory.adjust.store') }}"
              x-data="adjustForm()" class="space-y-6">
            @csrf

            <div class="card p-6 space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Adjustment Details</h3>

                {{-- SKU Select --}}
                <div>
                    <label for="sku_id" class="mb-1 block text-sm font-medium text-content">SKU <span class="text-danger-500">*</span></label>
                    <select name="sku_id" id="sku_id" required x-model="skuId" @change="updateStock()"
                            class="w-full rounded-lg border-surface-border text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        <option value="">Select SKU</option>
                        @foreach($skus as $sku)
                            <option value="{{ $sku->id }}" @selected(old('sku_id') == $sku->id)
                                    data-stock="{{ $sku->inventoryItems->sum('available_stock') }}"
                                    data-physical="{{ $sku->inventoryItems->sum('physical_stock') }}">
                                {{ $sku->sku_code }} - {{ $sku->variant?->product?->design?->name }} {{ $sku->variant?->color }} {{ $sku->variant?->size }}
                            </option>
                        @endforeach
                    </select>
                    @error('sku_id') <p class="mt-1 text-xs text-danger-500">{{ $message }}</p> @enderror
                </div>

                {{-- Warehouse Select --}}
                <div>
                    <label for="warehouse_id" class="mb-1 block text-sm font-medium text-content">Warehouse <span class="text-danger-500">*</span></label>
                    <select name="warehouse_id" id="warehouse_id" required
                            class="w-full rounded-lg border-surface-border text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        <option value="">Select Warehouse</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('warehouse_id') == $wh->id)>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                    @error('warehouse_id') <p class="mt-1 text-xs text-danger-500">{{ $message }}</p> @enderror
                </div>

                {{-- Adjustment Type --}}
                <div>
                    <label for="adjustment_type" class="mb-1 block text-sm font-medium text-content">Adjustment Type <span class="text-danger-500">*</span></label>
                    <select name="adjustment_type" id="adjustment_type" required x-model="adjustmentType"
                            class="w-full rounded-lg border-surface-border text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        <option value="add" @selected(old('adjustment_type') === 'add')>Add Stock</option>
                        <option value="remove" @selected(old('adjustment_type') === 'remove')>Remove Stock</option>
                        <option value="damage" @selected(old('adjustment_type') === 'damage')>Mark as Damaged</option>
                        <option value="block" @selected(old('adjustment_type') === 'block')>Block Stock</option>
                        <option value="unblock" @selected(old('adjustment_type') === 'unblock')>Unblock Stock</option>
                    </select>
                    @error('adjustment_type') <p class="mt-1 text-xs text-danger-500">{{ $message }}</p> @enderror
                </div>

                {{-- Quantity --}}
                <div>
                    <label for="quantity" class="mb-1 block text-sm font-medium text-content">Quantity <span class="text-danger-500">*</span></label>
                    <input type="number" name="quantity" id="quantity" min="1" required x-model.number="quantity"
                           value="{{ old('quantity') }}"
                           class="w-full rounded-lg border-surface-border text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    @error('quantity') <p class="mt-1 text-xs text-danger-500">{{ $message }}</p> @enderror
                </div>

                {{-- Reason --}}
                <div>
                    <label for="reason" class="mb-1 block text-sm font-medium text-content">Reason <span class="text-danger-500">*</span></label>
                    <textarea name="reason" id="reason" rows="3" required
                              class="w-full rounded-lg border-surface-border text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400"
                              placeholder="Explain the reason for this adjustment (required for audit trail)...">{{ old('reason') }}</textarea>
                    @error('reason') <p class="mt-1 text-xs text-danger-500">{{ $message }}</p> @enderror
                </div>

                {{-- Preview --}}
                <div x-show="skuId && quantity > 0" class="rounded-lg bg-surface-secondary p-4 border border-surface-border">
                    <p class="text-xs font-semibold uppercase tracking-wider text-content-secondary mb-2">Preview</p>
                    <div class="flex items-center gap-4 text-sm">
                        <div>
                            <span class="text-content-secondary">Current Stock:</span>
                            <span class="font-medium" x-text="currentStock"></span>
                        </div>
                        <svg class="h-4 w-4 text-content-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                        <div>
                            <span class="text-content-secondary">After Adjustment:</span>
                            <span class="font-bold" :class="afterStock >= 0 ? 'text-success-500' : 'text-danger-500'" x-text="afterStock"></span>
                        </div>
                    </div>
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
                        onclick="return confirm('Are you sure you want to apply this stock adjustment?')">
                    Apply Adjustment
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function adjustForm() {
            return {
                skuId: '{{ old('sku_id', '') }}',
                adjustmentType: '{{ old('adjustment_type', 'add') }}',
                quantity: {{ old('quantity', 0) }},
                currentStock: 0,
                physicalStock: 0,

                get afterStock() {
                    if (['add', 'unblock'].includes(this.adjustmentType)) {
                        return this.physicalStock + this.quantity;
                    }
                    return this.physicalStock - this.quantity;
                },

                updateStock() {
                    const option = document.querySelector(`#sku_id option[value="${this.skuId}"]`);
                    this.currentStock = option ? parseInt(option.dataset.stock || 0) : 0;
                    this.physicalStock = option ? parseInt(option.dataset.physical || 0) : 0;
                }
            };
        }
    </script>
    @endpush
</x-admin-layout>
