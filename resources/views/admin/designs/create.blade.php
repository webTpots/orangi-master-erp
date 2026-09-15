<x-admin-layout>
    <x-slot name="title">Add Design</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.designs.index') }}" class="text-content-secondary hover:text-content transition-colors">Designs</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Add Design</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="mb-6">
        <h1 class="font-display text-xl font-bold text-content">Add Design</h1>
        <p class="text-xs text-content-secondary mt-0.5">Create a new design with variants and pricing</p>
    </div>

    {{-- Validation errors --}}
    @if($errors->any())
        <div class="mb-6 bg-danger-50 border border-danger-500/20 rounded-xl px-4 py-3">
            <div class="flex items-start gap-3">
                <svg class="w-4 h-4 text-danger-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <p class="text-sm font-medium text-danger-600">Please fix the following errors:</p>
                    <ul class="mt-1 list-disc list-inside text-sm text-danger-500 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.designs.store') }}" enctype="multipart/form-data"
          x-data="designForm()" class="max-w-4xl space-y-6">
        @csrf

        {{-- Design Info --}}
        <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-5">
            <h3 class="font-display text-base font-semibold text-content mb-4">Design Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Design Code <span class="text-danger-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" required class="input" placeholder="e.g. SKD-MOON">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Name <span class="text-danger-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="input" placeholder="e.g. SKD Moon">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Description</label>
                    <textarea name="description" rows="3" class="input">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Category</label>
                    <input type="text" name="category" value="{{ old('category') }}" class="input" placeholder="e.g. Kurti, Cord Set">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">HSN Code</label>
                    <input type="text" name="hsn_code" value="{{ old('hsn_code') }}" class="input" placeholder="e.g. 6104">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">GST Rate (%)</label>
                    <input type="number" name="gst_rate" value="{{ old('gst_rate', '5') }}" step="0.01" class="input" placeholder="5.00">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Primary Image</label>
                    <input type="file" name="image" accept="image/*" class="input">
                </div>
            </div>
        </div>

        {{-- Pricing --}}
        <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-5">
            <h3 class="font-display text-base font-semibold text-content mb-4">Default Pricing</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Selling Price</label>
                    <input type="number" name="selling_price" value="{{ old('selling_price') }}" step="0.01" class="input" placeholder="0.00">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Cost Price</label>
                    <input type="number" name="cost_price" value="{{ old('cost_price') }}" step="0.01" class="input" placeholder="0.00">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">MRP</label>
                    <input type="number" name="mrp" value="{{ old('mrp') }}" step="0.01" class="input" placeholder="0.00">
                </div>
            </div>
        </div>

        {{-- Variant Builder --}}
        <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-display text-base font-semibold text-content">Variants</h3>
                <button type="button" @click="addColor()" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Color
                </button>
            </div>

            <template x-if="variants.length === 0">
                <div class="py-8 text-center">
                    <div class="w-12 h-12 rounded-full bg-surface-secondary flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    </div>
                    <p class="text-sm text-content-secondary">No variants added</p>
                    <p class="text-xs text-content-muted mt-0.5">Click "Add Color" to start building variants</p>
                </div>
            </template>

            <div class="space-y-4">
                <template x-for="(variant, index) in variants" :key="index">
                    <div class="border border-surface-border-light rounded-lg p-4 bg-surface-secondary/30">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3 flex-1">
                                <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide">Color:</label>
                                <input type="text" x-model="variant.color"
                                       :name="'variants[' + index + '][color]'"
                                       class="input w-40" placeholder="e.g. Black">
                            </div>
                            <button type="button" @click="removeColor(index)" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-content-muted hover:text-danger-500 hover:bg-danger-50 transition-colors" title="Remove">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide self-center">Sizes:</label>
                            <template x-for="size in availableSizes" :key="size">
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox"
                                           :value="size"
                                           :name="'variants[' + index + '][sizes][]'"
                                           x-model="variant.sizes"
                                           class="rounded border-surface-border text-brand-500 focus:ring-brand-500">
                                    <span class="text-sm text-content" x-text="size"></span>
                                </label>
                            </template>
                            <button type="button" @click="selectAllSizes(index)" class="text-xs text-brand-500 hover:underline ml-2">Select All</button>
                        </div>

                        {{-- Per-variant pricing --}}
                        <div class="mt-3 flex flex-wrap gap-3">
                            <div>
                                <label class="text-xs text-content-muted">Selling Price</label>
                                <input type="number" :name="'variants[' + index + '][selling_price]'" step="0.01" class="input w-32" placeholder="Default">
                            </div>
                            <div>
                                <label class="text-xs text-content-muted">Cost Price</label>
                                <input type="number" :name="'variants[' + index + '][cost_price]'" step="0.01" class="input w-32" placeholder="Default">
                            </div>
                            <div>
                                <label class="text-xs text-content-muted">MRP</label>
                                <input type="number" :name="'variants[' + index + '][mrp]'" step="0.01" class="input w-32" placeholder="Default">
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- SKU Preview --}}
            <template x-if="variants.length > 0">
                <div class="mt-4 border-t border-surface-border-light pt-4">
                    <h4 class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-2">SKU Preview</h4>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="sku in skuPreview" :key="sku">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-brand-50 text-brand-600 font-mono" x-text="sku"></span>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Submit --}}
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold transition-colors">Create Design</button>
            <a href="{{ route('admin.designs.index') }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold transition-colors">Cancel</a>
        </div>
    </form>

    <script>
        function designForm() {
            return {
                availableSizes: ['S', 'M', 'L', 'XL', '2XL', '3XL'],
                variants: [],

                addColor() {
                    this.variants.push({ color: '', sizes: [] });
                },

                removeColor(index) {
                    this.variants.splice(index, 1);
                },

                selectAllSizes(index) {
                    this.variants[index].sizes = [...this.availableSizes];
                },

                get skuPreview() {
                    const code = document.querySelector('[name="code"]')?.value || 'CODE';
                    let skus = [];
                    this.variants.forEach(v => {
                        if (!v.color) return;
                        v.sizes.forEach(s => {
                            skus.push(`${code}-${v.color}-${s}`.toUpperCase().replace(/\s+/g, '-'));
                        });
                    });
                    return skus;
                }
            };
        }
    </script>
</x-admin-layout>
