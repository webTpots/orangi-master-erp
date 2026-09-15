<x-admin-layout>
    <x-slot name="title">Physical Count</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.inventory.index') }}" class="text-content-secondary hover:text-content">Inventory</a>
            <svg class="w-4 h-4 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span>Physical Count</span>
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- Warehouse Selector --}}
        <div class="card p-6">
            <form method="GET" action="{{ route('admin.inventory.physical-count') }}" class="flex items-end gap-4">
                <div class="flex-1 max-w-xs">
                    <label for="warehouse_id" class="mb-1 block text-sm font-medium text-content">Select Warehouse</label>
                    <select name="warehouse_id" id="warehouse_id" required
                            class="w-full rounded-lg border-surface-border text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        <option value="">Choose warehouse...</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">
                    Load Stock
                </button>
            </form>
        </div>

        @if($warehouseId && $items->count() > 0)
            {{-- Count Form --}}
            <form method="POST" action="{{ route('admin.inventory.physical-count.store') }}"
                  x-data="physicalCountForm()" class="space-y-6">
                @csrf
                <input type="hidden" name="warehouse_id" value="{{ $warehouseId }}">

                <div class="card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">
                            Enter Actual Counts
                        </h3>
                        <div class="text-xs text-content-muted">
                            <span x-text="adjustmentCount"></span> adjustment(s) detected
                        </div>
                    </div>

                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>SKU Code</th>
                                    <th>Design</th>
                                    <th>Color</th>
                                    <th>Size</th>
                                    <th class="text-right">System Stock</th>
                                    <th class="text-right w-32">Actual Count</th>
                                    <th class="text-right">Difference</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $index => $item)
                                    @php
                                        $skuCode = $item->sku->sku_code ?? 'N/A';
                                        $design = $item->sku->variant?->product?->design?->name ?? '-';
                                        $color = $item->sku->variant?->color ?? '-';
                                        $size = $item->sku->variant?->size ?? '-';
                                    @endphp
                                    <tr>
                                        <td class="font-mono text-xs font-medium text-brand-600">{{ $skuCode }}</td>
                                        <td class="text-content-secondary">{{ $design }}</td>
                                        <td class="text-content-secondary">{{ $color }}</td>
                                        <td>{{ $size }}</td>
                                        <td class="text-right font-medium">{{ $item->physical_stock }}</td>
                                        <td class="text-right">
                                            <input type="hidden" name="counts[{{ $index }}][sku_id]" value="{{ $item->sku_id }}">
                                            <input type="number" name="counts[{{ $index }}][actual]" min="0"
                                                   value="{{ $item->physical_stock }}"
                                                   x-model.number="counts[{{ $index }}]"
                                                   data-system="{{ $item->physical_stock }}"
                                                   class="w-24 rounded-lg border-surface-border text-right text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                        </td>
                                        <td class="text-right">
                                            <span x-show="counts[{{ $index }}] !== undefined"
                                                  :class="{
                                                      'text-success-500': counts[{{ $index }}] > {{ $item->physical_stock }},
                                                      'text-danger-500': counts[{{ $index }}] < {{ $item->physical_stock }},
                                                      'text-content-muted': counts[{{ $index }}] === {{ $item->physical_stock }}
                                                  }"
                                                  class="font-medium"
                                                  x-text="counts[{{ $index }}] !== undefined ? (counts[{{ $index }}] - {{ $item->physical_stock }}) : 0">
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
                            onclick="return confirm('This will create adjustment entries for all differences. Continue?')">
                        Apply Physical Count
                    </button>
                </div>
            </form>
        @elseif($warehouseId)
            <div class="card p-12 text-center">
                <p class="text-sm text-content-secondary">No inventory items found for this warehouse.</p>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function physicalCountForm() {
            const counts = {};
            @foreach($items as $index => $item)
                counts[{{ $index }}] = {{ $item->physical_stock }};
            @endforeach

            return {
                counts,
                get adjustmentCount() {
                    let count = 0;
                    @foreach($items as $index => $item)
                        if (this.counts[{{ $index }}] !== {{ $item->physical_stock }}) count++;
                    @endforeach
                    return count;
                }
            };
        }
    </script>
    @endpush
</x-admin-layout>
