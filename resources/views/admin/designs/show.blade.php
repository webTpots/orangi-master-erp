<x-admin-layout>
    <x-slot name="title">{{ $design->name }}</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.designs.index') }}" class="text-content-secondary hover:text-content transition-colors">Designs</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">{{ $design->name }}</span>
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- Design Info Card --}}
        <div class="card p-6">
            <div class="flex items-start justify-between">
                <div class="flex gap-6">
                    @if($design->primary_image_path)
                        <div class="w-32 h-32 rounded-lg overflow-hidden bg-surface-secondary flex-shrink-0">
                            <img src="{{ Storage::url($design->primary_image_path) }}" alt="{{ $design->name }}" class="w-full h-full object-cover">
                        </div>
                    @endif
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-xl font-display font-semibold text-content">{{ $design->name }}</h3>
                            <span class="font-mono text-sm text-brand-600 bg-brand-50 px-2 py-0.5 rounded">{{ $design->code }}</span>
                            @if($design->status === 'active')
                                <span class="badge-success">Active</span>
                            @else
                                <span class="badge-neutral">Inactive</span>
                            @endif
                        </div>
                        @if($design->description)
                            <p class="text-sm text-content-secondary mb-3">{{ $design->description }}</p>
                        @endif
                        <div class="flex gap-6 text-sm">
                            <div>
                                <span class="text-content-muted">Category:</span>
                                <span class="text-content font-medium">{{ $design->category ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-content-muted">HSN:</span>
                                <span class="text-content font-mono">{{ $design->hsn_code ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-content-muted">GST:</span>
                                <span class="text-content font-medium">{{ $design->gst_rate }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.designs.edit', $design) }}" class="btn-secondary btn-sm">Edit</a>
                    <form method="POST" action="{{ route('admin.designs.destroy', $design) }}" onsubmit="return confirm('Deactivate this design?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-ghost btn-sm text-danger-500">Deactivate</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Variants Grid (Color x Size Matrix) --}}
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-display font-semibold text-content">Variants Matrix</h3>
            </div>

            @if(count($colorSizeMatrix) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-surface-border">
                                <th class="text-left px-3 py-2 text-xs font-semibold text-content-secondary uppercase">Color</th>
                                @foreach($sizes as $size)
                                    <th class="text-center px-3 py-2 text-xs font-semibold text-content-secondary uppercase">{{ $size }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($colorSizeMatrix as $color => $sizeData)
                                <tr class="border-b border-surface-border-light">
                                    <td class="px-3 py-3 font-medium text-content">{{ $color }}</td>
                                    @foreach($sizes as $size)
                                        <td class="text-center px-3 py-3">
                                            @if(isset($sizeData[$size]))
                                                @php $cell = $sizeData[$size]; @endphp
                                                <div class="inline-flex flex-col items-center">
                                                    <span class="text-sm font-medium {{ $cell['stock'] > 0 ? 'text-success-500' : 'text-danger-500' }}">
                                                        {{ $cell['stock'] }}
                                                    </span>
                                                    <span class="text-[10px] text-content-muted font-mono">
                                                        {{ $cell['sku']->sku_code ?? '-' }}
                                                    </span>
                                                    @if($cell['mappings'] > 0)
                                                        <span class="badge-brand text-[10px] mt-0.5">{{ $cell['mappings'] }} mapped</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-content-muted">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-content-secondary text-center py-4">No variants created yet.</p>
            @endif
        </div>

        {{-- SKU List with Mappings --}}
        <div class="card p-6">
            <h3 class="text-base font-display font-semibold text-content mb-4">SKUs</h3>
            @if($skus->count() > 0)
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>SKU Code</th>
                                <th>Color</th>
                                <th>Size</th>
                                <th>Selling Price</th>
                                <th>Cost Price</th>
                                <th>MRP</th>
                                <th>Mappings</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($skus as $sku)
                                <tr class="cursor-pointer" onclick="window.location='{{ route('admin.skus.show', $sku) }}'">
                                    <td class="font-mono text-xs font-medium text-brand-600">{{ $sku->sku_code }}</td>
                                    <td>{{ $sku->variant->color ?? '-' }}</td>
                                    <td>{{ $sku->variant->size ?? '-' }}</td>
                                    <td>{{ number_format($sku->selling_price, 2) }}</td>
                                    <td>{{ number_format($sku->cost_price, 2) }}</td>
                                    <td>{{ number_format($sku->mrp, 2) }}</td>
                                    <td>
                                        @if($sku->skuMappings->count() > 0)
                                            <span class="badge-success">{{ $sku->skuMappings->count() }}</span>
                                        @else
                                            <span class="badge-warning">Unmapped</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="{{ $sku->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ ucfirst($sku->status) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-content-secondary text-center py-4">No SKUs found.</p>
            @endif
        </div>

        {{-- Image Gallery --}}
        @if($design->images->count() > 0)
            <div class="card p-6">
                <h3 class="text-base font-display font-semibold text-content mb-4">Image Gallery</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-4">
                    @foreach($design->images as $image)
                        <div class="aspect-square rounded-lg overflow-hidden bg-surface-secondary border border-surface-border">
                            <img src="{{ Storage::url($image->image_path) }}" alt="{{ $image->alt_text ?? $design->name }}" class="w-full h-full object-cover">
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
