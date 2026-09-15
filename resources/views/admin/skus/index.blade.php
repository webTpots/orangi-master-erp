<x-admin-layout>
    <x-slot name="title">SKUs</x-slot>
    <x-slot name="header">SKUs</x-slot>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <form method="GET" action="{{ route('admin.skus.index') }}" class="flex flex-wrap gap-3 flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search SKU code, color, design..." class="input flex-1 max-w-xs">

            <select name="design_id" class="input w-auto">
                <option value="">All Designs</option>
                @foreach($designs as $design)
                    <option value="{{ $design->id }}" {{ request('design_id') == $design->id ? 'selected' : '' }}>
                        {{ $design->code }} - {{ $design->name }}
                    </option>
                @endforeach
            </select>

            <select name="stock" class="input w-auto">
                <option value="">All Stock Levels</option>
                <option value="in_stock" {{ request('stock') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                <option value="low_stock" {{ request('stock') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                <option value="out_of_stock" {{ request('stock') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
            </select>

            <select name="status" class="input w-auto">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>

            <button type="submit" class="btn-secondary btn-sm">Filter</button>
            @if(request()->hasAny(['search', 'design_id', 'stock', 'status', 'color', 'size']))
                <a href="{{ route('admin.skus.index') }}" class="btn-ghost btn-sm">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>SKU Code</th>
                    <th>Design</th>
                    <th>Color</th>
                    <th>Size</th>
                    <th class="text-right">Stock</th>
                    <th class="text-right">Price</th>
                    <th class="text-center">Mappings</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($skus as $sku)
                    <tr class="cursor-pointer" onclick="window.location='{{ route('admin.skus.show', $sku) }}'">
                        <td class="font-mono text-xs font-medium text-brand-600">{{ $sku->sku_code }}</td>
                        <td>
                            <span class="text-content font-medium">{{ $sku->variant?->product?->design?->name ?? '-' }}</span>
                            <span class="text-content-muted text-xs block">{{ $sku->variant?->product?->design?->code ?? '' }}</span>
                        </td>
                        <td class="text-content-secondary">{{ $sku->variant?->color ?? '-' }}</td>
                        <td>{{ $sku->variant?->size ?? '-' }}</td>
                        <td class="text-right">
                            @php $stock = $sku->inventoryItems->sum('available_stock'); @endphp
                            <span class="{{ $stock > 0 ? 'text-success-500' : 'text-danger-500' }} font-medium">
                                {{ $stock }}
                            </span>
                        </td>
                        <td class="text-right font-mono text-xs">{{ number_format($sku->selling_price, 2) }}</td>
                        <td class="text-center">
                            @if($sku->skuMappings->count() > 0)
                                <span class="badge-success">{{ $sku->skuMappings->count() }}</span>
                            @else
                                <span class="badge-warning">0</span>
                            @endif
                        </td>
                        <td>
                            <span class="{{ $sku->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ ucfirst($sku->status) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-8 text-content-secondary">No SKUs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $skus->links() }}
    </div>
</x-admin-layout>
