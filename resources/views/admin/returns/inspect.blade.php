<x-admin-layout>
    <x-slot name="title">Inspect Return</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.returns.index') }}" class="text-content-secondary hover:text-content transition-colors">Returns</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.returns.show', $return) }}" class="text-content-secondary hover:text-content transition-colors">RTN-{{ str_pad($return->id, 5, '0', STR_PAD_LEFT) }}</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Inspect</span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <h1 class="font-display text-xl font-bold text-content mb-6">Inspect Return RTN-{{ str_pad($return->id, 5, '0', STR_PAD_LEFT) }}</h1>

            <form method="POST" action="{{ route('admin.returns.store-inspection', $return) }}" class="space-y-6">
                @csrf

                {{-- Overall Condition --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Overall Inspection</h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-content">Overall Condition</label>
                            <select name="condition" required
                                    class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                <option value="">-- Select Condition --</option>
                                @foreach (\App\Models\ReturnInspection::CONDITION_LABELS as $val => $label)
                                    <option value="{{ $val }}" @selected(old('condition') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('condition') <p class="text-xs text-danger-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-end">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_resellable" value="1"
                                       class="rounded border-surface-border text-brand-500 focus:ring-brand-400"
                                       @checked(old('is_resellable'))>
                                <span class="text-sm font-medium text-content">Resellable</span>
                            </label>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-content">Inspection Notes</label>
                            <textarea name="inspection_notes" rows="3"
                                      class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400"
                                      placeholder="Describe the condition of the returned items...">{{ old('inspection_notes') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Item-Level Inspection --}}
                @if ($return->items->isNotEmpty())
                    <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Item Inspection</h3>

                        <div class="space-y-4">
                            @foreach ($return->items as $index => $item)
                                <div class="p-4 rounded-lg border border-surface-border-light bg-surface-secondary">
                                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">

                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <p class="text-sm font-medium text-content">{{ $item->sku->sku_code ?? 'Unknown SKU' }}</p>
                                            <p class="text-xs text-content-muted">Qty: {{ $item->quantity }}</p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-content-secondary">Condition</label>
                                            <select name="items[{{ $index }}][condition]" required
                                                    class="w-full rounded-lg border-surface-border bg-white text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                                @foreach (\App\Models\ReturnInspection::CONDITION_LABELS as $val => $label)
                                                    <option value="{{ $val }}" @selected($item->condition === $val)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-content-secondary">Restock Qty</label>
                                            <input type="number" name="items[{{ $index }}][restock_quantity]" value="{{ $item->restock_quantity }}" min="0" max="{{ $item->quantity }}"
                                                   class="w-full rounded-lg border-surface-border bg-white text-sm text-content text-center focus:border-brand-400 focus:ring-brand-400">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-content-secondary">Dispose Qty</label>
                                            <input type="number" name="items[{{ $index }}][dispose_quantity]" value="{{ $item->dispose_quantity }}" min="0" max="{{ $item->quantity }}"
                                                   class="w-full rounded-lg border-surface-border bg-white text-sm text-content text-center focus:border-brand-400 focus:ring-brand-400">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Photo Upload Area (UI only) --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Inspection Photos</h3>
                    <div class="border-2 border-dashed border-surface-border rounded-lg p-8 text-center">
                        <svg class="mx-auto h-10 w-10 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z"/></svg>
                        <p class="mt-2 text-sm text-content-muted">Drag and drop photos here, or click to browse</p>
                        <p class="text-xs text-content-muted mt-1">PNG, JPG up to 5MB each</p>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 transition">
                        Save Inspection
                    </button>
                    <a href="{{ route('admin.returns.show', $return) }}" class="rounded-lg border border-surface-border px-6 py-2.5 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
