<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.flipkart.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Flipkart</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">SKU Mapping</span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-lg border border-success-200 bg-success-50 p-4 text-sm text-success-700">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-lg border border-danger-border bg-danger-50 p-4 text-sm text-danger-500">{{ session('error') }}</div>
            @endif

            {{-- Add Mapping Form --}}
            <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                <h3 class="text-sm font-semibold text-content mb-4">Add Flipkart SKU Mapping</h3>
                <form method="POST" action="{{ route('admin.flipkart.store-mapping') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    @csrf
                    <div>
                        <label for="external_identifier" class="block text-xs font-medium text-content-secondary mb-1">Flipkart SKU</label>
                        <input type="text" name="external_identifier" id="external_identifier" required
                               placeholder="e.g. SELLER-SKU-001"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                               value="{{ old('external_identifier') }}">
                        @error('external_identifier')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="sku_id" class="block text-xs font-medium text-content-secondary mb-1">Internal SKU</label>
                        <select name="sku_id" id="sku_id" required
                                class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            <option value="">Select internal SKU...</option>
                            @foreach ($internalSkus as $sku)
                                <option value="{{ $sku->id }}" @selected(old('sku_id') == $sku->id)>
                                    {{ $sku->sku_code }} ({{ $sku->variant?->product?->design?->name ?? 'No Design' }})
                                </option>
                            @endforeach
                        </select>
                        @error('sku_id')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="external_name" class="block text-xs font-medium text-content-secondary mb-1">Flipkart Product Name (optional)</label>
                        <input type="text" name="external_name" id="external_name"
                               placeholder="Product title on Flipkart"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                               value="{{ old('external_name') }}">
                    </div>
                    <div>
                        <button type="submit"
                                class="w-full rounded-lg text-white px-4 py-2 text-sm font-medium hover:opacity-90 transition inline-flex items-center justify-center gap-2" style="background-color: #2874F0;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Add Mapping
                        </button>
                    </div>
                </form>
            </div>

            {{-- Unmapped SKUs --}}
            @if ($unmappedSkus->isNotEmpty())
                <div class="rounded-xl border border-warning-200 bg-warning-50 p-6">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-warning-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        <div>
                            <h4 class="text-sm font-semibold text-warning-800">Unmapped Flipkart SKUs ({{ $unmappedSkus->count() }})</h4>
                            <p class="text-xs text-warning-600 mt-1">These SKUs appear in Flipkart orders but are not mapped to any internal SKU.</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($unmappedSkus as $sku)
                                    <span class="inline-flex items-center rounded-full bg-warning-100 px-3 py-1 text-xs font-medium text-warning-800">{{ $sku }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Existing Mappings --}}
            <div class="rounded-xl border border-surface-border bg-white shadow-card">
                <div class="px-6 py-4 border-b border-surface-border flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-content">Flipkart SKU Mappings</h3>
                    <span class="text-xs text-content-muted">{{ $mappings->total() }} mapping(s)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-surface-border bg-surface-secondary/50">
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-content-muted uppercase tracking-wider">Flipkart SKU</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-content-muted uppercase tracking-wider">Flipkart Name</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-content-muted uppercase tracking-wider">Internal SKU</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-content-muted uppercase tracking-wider">Design</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-content-muted uppercase tracking-wider">Status</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-content-muted uppercase tracking-wider">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border">
                            @forelse ($mappings as $mapping)
                                <tr class="hover:bg-surface-secondary/30 transition">
                                    <td class="px-4 py-3 font-medium text-content">{{ $mapping->external_identifier }}</td>
                                    <td class="px-4 py-3 text-content-secondary">{{ $mapping->external_name ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="font-mono text-xs bg-surface-secondary rounded px-2 py-0.5">{{ $mapping->sku->sku_code ?? '-' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-content-secondary">{{ $mapping->sku?->variant?->product?->design?->name ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                            {{ $mapping->mapping_status === 'confirmed' ? 'bg-success-50 text-success-700' : 'bg-warning-50 text-warning-700' }}">
                                            {{ ucfirst($mapping->mapping_status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-content-muted">{{ $mapping->created_at->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-content-muted text-sm">No Flipkart SKU mappings yet. Add your first mapping above.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($mappings->hasPages())
                    <div class="px-6 py-4 border-t border-surface-border">
                        {{ $mappings->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-admin-layout>
