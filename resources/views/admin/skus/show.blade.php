<x-admin-layout>
    <x-slot name="title">SKU: {{ $sku->sku_code }}</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.skus.index') }}" class="text-content-secondary hover:text-content">SKUs</a>
            <svg class="w-4 h-4 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-mono">{{ $sku->sku_code }}</span>
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- SKU Info --}}
        <div class="card p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <p class="stat-label">SKU Code</p>
                    <p class="text-lg font-mono font-semibold text-brand-600">{{ $sku->sku_code }}</p>
                </div>
                <div>
                    <p class="stat-label">Design</p>
                    <p class="text-lg font-medium text-content">
                        <a href="{{ route('admin.designs.show', $sku->variant->product->design) }}" class="hover:text-brand-500">
                            {{ $sku->variant->product->design->name }}
                        </a>
                    </p>
                </div>
                <div>
                    <p class="stat-label">Color</p>
                    <p class="text-lg font-medium text-content">{{ $sku->variant->color ?? '-' }}</p>
                </div>
                <div>
                    <p class="stat-label">Size</p>
                    <p class="text-lg font-medium text-content">{{ $sku->variant->size ?? '-' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-6 pt-6 border-t border-surface-border">
                <div>
                    <p class="stat-label">Selling Price</p>
                    <p class="stat-value">{{ number_format($sku->selling_price, 2) }}</p>
                </div>
                <div>
                    <p class="stat-label">Cost Price</p>
                    <p class="stat-value">{{ number_format($sku->cost_price, 2) }}</p>
                </div>
                <div>
                    <p class="stat-label">MRP</p>
                    <p class="stat-value">{{ number_format($sku->mrp, 2) }}</p>
                </div>
                <div>
                    <p class="stat-label">Status</p>
                    <span class="{{ $sku->status === 'active' ? 'badge-success' : 'badge-neutral' }} mt-1">{{ ucfirst($sku->status) }}</span>
                </div>
            </div>
        </div>

        {{-- Inventory --}}
        <div class="card p-6">
            <h3 class="text-base font-display font-semibold text-content mb-4">Inventory</h3>
            @if($sku->inventoryItems->count() > 0)
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Warehouse</th>
                                <th class="text-right">Physical</th>
                                <th class="text-right">Reserved</th>
                                <th class="text-right">Available</th>
                                <th class="text-right">Damaged</th>
                                <th class="text-right">In Transit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sku->inventoryItems as $inv)
                                <tr>
                                    <td class="font-medium">{{ $inv->warehouse->name ?? '-' }}</td>
                                    <td class="text-right">{{ $inv->physical_stock }}</td>
                                    <td class="text-right">{{ $inv->reserved_stock }}</td>
                                    <td class="text-right font-medium {{ $inv->available_stock > 0 ? 'text-success-500' : 'text-danger-500' }}">{{ $inv->available_stock }}</td>
                                    <td class="text-right">{{ $inv->damaged_stock }}</td>
                                    <td class="text-right">{{ $inv->in_transit_stock }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-content-secondary text-center py-4">No inventory records.</p>
            @endif
        </div>

        {{-- Mappings --}}
        <div class="card p-6">
            <h3 class="text-base font-display font-semibold text-content mb-4">SKU Mappings</h3>
            @if($sku->skuMappings->count() > 0)
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Marketplace</th>
                                <th>External SKU</th>
                                <th>Confidence</th>
                                <th>Mapped By</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sku->skuMappings as $mapping)
                                <tr>
                                    <td>{{ $mapping->marketplaceAccount?->marketplace?->name ?? '-' }}</td>
                                    <td class="font-mono text-xs">{{ $mapping->marketplace_sku }}</td>
                                    <td>
                                        <span class="badge-{{ $mapping->confidence_score >= 0.9 ? 'success' : ($mapping->confidence_score >= 0.7 ? 'warning' : 'neutral') }}">
                                            {{ number_format($mapping->confidence_score * 100) }}%
                                        </span>
                                    </td>
                                    <td class="text-content-secondary">{{ $mapping->mapper?->name ?? 'System' }}</td>
                                    <td><span class="{{ $mapping->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ ucfirst($mapping->status) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-content-secondary text-center py-4">No mappings configured.</p>
            @endif
        </div>

        {{-- Vendor Products --}}
        @if($sku->vendorProducts->count() > 0)
            <div class="card p-6">
                <h3 class="text-base font-display font-semibold text-content mb-4">Vendors</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Vendor</th>
                                <th>Vendor SKU</th>
                                <th class="text-right">Cost Price</th>
                                <th class="text-right">Lead Time</th>
                                <th>Preferred</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sku->vendorProducts as $vp)
                                <tr>
                                    <td class="font-medium">{{ $vp->vendor->name }}</td>
                                    <td class="font-mono text-xs">{{ $vp->vendor_sku_code ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($vp->cost_price, 2) }}</td>
                                    <td class="text-right">{{ $vp->lead_time_days ?? '-' }} days</td>
                                    <td>
                                        @if($vp->is_preferred)
                                            <span class="badge-success">Yes</span>
                                        @else
                                            <span class="badge-neutral">No</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
