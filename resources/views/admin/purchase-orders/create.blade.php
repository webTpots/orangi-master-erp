<x-admin-layout>
    <x-slot name="title">Create Purchase Order</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.purchase-orders.index') }}" class="text-content-secondary hover:text-content transition-colors">Purchase Orders</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Create</span>
        </div>
    </x-slot>

    <div class="mb-6">
        <h1 class="font-display text-xl font-bold text-content">Create Purchase Order</h1>
        <p class="text-xs text-content-secondary mt-0.5">Add a new purchase order for a vendor</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 bg-danger-50 border border-danger-500/20 rounded-xl px-4 py-3 max-w-5xl">
            <div class="flex items-start gap-3">
                <svg class="w-4 h-4 text-danger-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <ul class="list-disc list-inside text-sm text-danger-500 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.purchase-orders.store') }}"
                  x-data="poForm()"
                  class="space-y-6">
                @csrf

                {{-- PO Header --}}
                <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-5">
                    <h3 class="font-display text-base font-semibold text-content mb-4">Order Details</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="vendor_id" class="mb-1 block text-sm font-medium text-content">Vendor <span class="text-danger-500">*</span></label>
                            <select name="vendor_id" id="vendor_id" required
                                    class="w-full rounded-lg border-surface-border text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                <option value="">Select Vendor</option>
                                @foreach ($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected(old('vendor_id', request('vendor_id')) == $vendor->id)>
                                        {{ $vendor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="order_date" class="mb-1 block text-sm font-medium text-content">Order Date <span class="text-danger-500">*</span></label>
                            <input type="date" name="order_date" id="order_date" value="{{ old('order_date', now()->toDateString()) }}" required
                                   class="w-full rounded-lg border-surface-border text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        </div>
                        <div>
                            <label for="expected_delivery_date" class="mb-1 block text-sm font-medium text-content">Expected Delivery</label>
                            <input type="date" name="expected_delivery_date" id="expected_delivery_date" value="{{ old('expected_delivery_date') }}"
                                   class="w-full rounded-lg border-surface-border text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        </div>
                        <div class="sm:col-span-3">
                            <label for="notes" class="mb-1 block text-sm font-medium text-content">Notes</label>
                            <textarea name="notes" id="notes" rows="2"
                                      class="w-full rounded-lg border-surface-border text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400"
                                      placeholder="Any notes for this purchase order...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Line Items --}}
                <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="font-display text-base font-semibold text-content">Line Items</h3>
                        <button type="button" @click="addLine()"
                                class="inline-flex items-center gap-1 rounded-lg border border-brand-500 px-3 py-1.5 text-xs font-medium text-brand-500 hover:bg-brand-50 transition">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Add Line
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-surface-border">
                                    <th class="pb-2 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary w-5">#</th>
                                    <th class="pb-2 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">SKU</th>
                                    <th class="pb-2 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary w-28">Qty</th>
                                    <th class="pb-2 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary w-32">Unit Cost</th>
                                    <th class="pb-2 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary w-32">Line Total</th>
                                    <th class="pb-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(line, index) in lines" :key="index">
                                    <tr class="border-b border-surface-border-light">
                                        <td class="py-2 text-sm text-content-muted" x-text="index + 1"></td>
                                        <td class="py-2 pr-2">
                                            <select :name="`lines[${index}][sku_id]`" x-model="line.sku_id" required
                                                    @change="updateCost(index)"
                                                    class="w-full rounded-lg border-surface-border text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                                <option value="">Select SKU</option>
                                                @foreach ($skus as $sku)
                                                    <option value="{{ $sku['id'] }}" data-cost="{{ $sku['cost_price'] }}">
                                                        {{ $sku['sku_code'] }} - {{ $sku['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="py-2 pr-2">
                                            <input type="number" :name="`lines[${index}][quantity]`"
                                                   x-model.number="line.quantity" min="1" required
                                                   @input="calcLineTotal(index)"
                                                   class="w-full rounded-lg border-surface-border text-right text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                        </td>
                                        <td class="py-2 pr-2">
                                            <input type="number" :name="`lines[${index}][unit_cost]`"
                                                   x-model.number="line.unit_cost" min="0" step="0.01" required
                                                   @input="calcLineTotal(index)"
                                                   class="w-full rounded-lg border-surface-border text-right text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                        </td>
                                        <td class="py-2 text-right text-sm font-medium text-content" x-text="formatCurrency(line.line_total)"></td>
                                        <td class="py-2 text-center">
                                            <button type="button" @click="removeLine(index)" x-show="lines.length > 1"
                                                    class="text-content-muted hover:text-danger-500 transition">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="pt-4 text-right text-sm font-semibold text-content">Grand Total</td>
                                    <td class="pt-4 text-right text-lg font-bold text-brand-500" x-text="formatCurrency(grandTotal)"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold transition-colors">Save as Draft</button>
                    <a href="{{ route('admin.purchase-orders.index') }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold transition-colors">Cancel</a>
                </div>
            </form>

    <script>
        function poForm() {
            return {
                lines: [{ sku_id: '', quantity: 1, unit_cost: 0, line_total: 0 }],

                skuCosts: @json($skus->pluck('cost_price', 'id')),

                addLine() {
                    this.lines.push({ sku_id: '', quantity: 1, unit_cost: 0, line_total: 0 });
                },

                removeLine(index) {
                    this.lines.splice(index, 1);
                },

                updateCost(index) {
                    const skuId = this.lines[index].sku_id;
                    if (skuId && this.skuCosts[skuId]) {
                        this.lines[index].unit_cost = parseFloat(this.skuCosts[skuId]);
                    }
                    this.calcLineTotal(index);
                },

                calcLineTotal(index) {
                    const line = this.lines[index];
                    line.line_total = (line.quantity || 0) * (line.unit_cost || 0);
                },

                get grandTotal() {
                    return this.lines.reduce((sum, line) => sum + (line.line_total || 0), 0);
                },

                formatCurrency(val) {
                    return new Intl.NumberFormat('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val || 0);
                }
            };
        }
    </script>
</x-admin-layout>
