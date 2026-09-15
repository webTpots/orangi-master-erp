<x-admin-layout>
    <x-slot name="title">Inventory</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <span>Inventory</span>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.inventory.export') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-surface-border px-3 py-1.5 text-xs font-medium text-content-secondary hover:bg-surface-secondary transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Export CSV
                </a>
                <a href="{{ route('admin.inventory.adjust.create') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-white shadow-brand hover:bg-brand-600 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Adjust Stock
                </a>
            </div>
        </div>
    </x-slot>

    {{-- KPI Row --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="card p-4">
            <p class="stat-label">Total SKUs</p>
            <p class="text-2xl font-bold text-content">{{ number_format($kpis['total_skus']) }}</p>
        </div>
        <div class="card p-4">
            <p class="stat-label">Total Stock</p>
            <p class="text-2xl font-bold text-content">{{ number_format($kpis['total_stock']) }}</p>
        </div>
        <div class="card p-4">
            <p class="stat-label">Stock Value</p>
            <p class="text-2xl font-bold text-brand-600"><span class="text-sm font-normal text-content-secondary">&#8377;</span>{{ number_format($kpis['stock_value'], 2) }}</p>
        </div>
        <div class="card p-4">
            <p class="stat-label">Low Stock</p>
            <p class="text-2xl font-bold text-warning-500">{{ number_format($kpis['low_stock']) }}</p>
        </div>
        <div class="card p-4">
            <p class="stat-label">Out of Stock</p>
            <p class="text-2xl font-bold text-danger-500">{{ number_format($kpis['out_of_stock']) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="flex flex-wrap gap-3 flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search SKU, design, color..."
                   class="input flex-1 max-w-xs">

            <select name="warehouse_id" class="input w-auto">
                <option value="">All Warehouses</option>
                @foreach($warehouses as $wh)
                    <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                @endforeach
            </select>

            <select name="design_id" class="input w-auto">
                <option value="">All Designs</option>
                @foreach($designs as $design)
                    <option value="{{ $design->id }}" {{ request('design_id') == $design->id ? 'selected' : '' }}>{{ $design->name }}</option>
                @endforeach
            </select>

            <select name="stock_level" class="input w-auto">
                <option value="">All Stock Levels</option>
                <option value="in" {{ request('stock_level') === 'in' ? 'selected' : '' }}>In Stock</option>
                <option value="low" {{ request('stock_level') === 'low' ? 'selected' : '' }}>Low Stock</option>
                <option value="out" {{ request('stock_level') === 'out' ? 'selected' : '' }}>Out of Stock</option>
            </select>

            <button type="submit" class="btn-secondary btn-sm">Filter</button>
            @if(request()->hasAny(['search', 'warehouse_id', 'design_id', 'stock_level', 'color', 'size']))
                <a href="{{ route('admin.inventory.index') }}" class="btn-ghost btn-sm">Clear</a>
            @endif
        </form>
    </div>

    {{-- Quick Links --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('admin.inventory.transfer.create') }}" class="inline-flex items-center gap-1 text-xs text-content-secondary hover:text-brand-500 transition">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
            Transfer Stock
        </a>
        <span class="text-content-muted">|</span>
        <a href="{{ route('admin.inventory.opening-stock') }}" class="inline-flex items-center gap-1 text-xs text-content-secondary hover:text-brand-500 transition">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
            Import Opening Stock
        </a>
        <span class="text-content-muted">|</span>
        <a href="{{ route('admin.inventory.physical-count') }}" class="inline-flex items-center gap-1 text-xs text-content-secondary hover:text-brand-500 transition">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/></svg>
            Physical Count
        </a>
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
                    <th class="text-right">Physical</th>
                    <th class="text-right">Reserved</th>
                    <th class="text-right">Available</th>
                    <th class="text-right">Damaged</th>
                    <th class="text-right">Value</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($skus as $sku)
                    @php
                        $physical  = $sku->inventoryItems->sum('physical_stock');
                        $reserved  = $sku->inventoryItems->sum('reserved_stock');
                        $available = $sku->inventoryItems->sum('available_stock');
                        $damaged   = $sku->inventoryItems->sum('damaged_stock');
                        $value     = $sku->inventoryItems->sum('total_value');
                        $minStock  = $sku->minimum_stock_level ?? 5;

                        if ($available <= 0) {
                            $statusClass = 'bg-danger-50 text-danger-500';
                            $statusLabel = 'Out';
                        } elseif ($available <= $minStock) {
                            $statusClass = 'bg-warning-50 text-warning-600';
                            $statusLabel = 'Low';
                        } else {
                            $statusClass = 'bg-success-50 text-success-600';
                            $statusLabel = 'OK';
                        }
                    @endphp
                    <tr class="cursor-pointer hover:bg-surface-secondary/50 transition" onclick="window.location='{{ route('admin.inventory.show', $sku) }}'">
                        <td class="font-mono text-xs font-medium text-brand-600">{{ $sku->sku_code }}</td>
                        <td>
                            <span class="text-content font-medium">{{ $sku->variant?->product?->design?->name ?? '-' }}</span>
                        </td>
                        <td class="text-content-secondary">{{ $sku->variant?->color ?? '-' }}</td>
                        <td>{{ $sku->variant?->size ?? '-' }}</td>
                        <td class="text-right font-medium">{{ $physical }}</td>
                        <td class="text-right text-content-secondary">{{ $reserved }}</td>
                        <td class="text-right font-medium {{ $available > 0 ? ($available <= $minStock ? 'text-warning-600' : 'text-success-500') : 'text-danger-500' }}">
                            {{ $available }}
                        </td>
                        <td class="text-right text-content-secondary">{{ $damaged }}</td>
                        <td class="text-right font-mono text-xs">&#8377;{{ number_format($value, 2) }}</td>
                        <td class="text-center">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-8 text-content-secondary">
                            No inventory records found.
                            <a href="{{ route('admin.inventory.opening-stock') }}" class="text-brand-500 hover:underline">Import opening stock.</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $skus->links() }}
    </div>
</x-admin-layout>
