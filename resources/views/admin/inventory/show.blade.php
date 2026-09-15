<x-admin-layout>
    <x-slot name="title">Inventory: {{ $sku->sku_code }}</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.inventory.index') }}" class="text-content-secondary hover:text-content">Inventory</a>
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
                    <p class="text-lg font-medium text-content">{{ $sku->variant?->product?->design?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="stat-label">Color</p>
                    <p class="text-lg font-medium text-content">{{ $sku->variant?->color ?? '-' }}</p>
                </div>
                <div>
                    <p class="stat-label">Size</p>
                    <p class="text-lg font-medium text-content">{{ $sku->variant?->size ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Stock Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="card p-4">
                <p class="stat-label">Physical Stock</p>
                <p class="text-2xl font-bold text-content">{{ $balance['physical'] }}</p>
            </div>
            <div class="card p-4">
                <p class="stat-label">Reserved</p>
                <p class="text-2xl font-bold text-warning-600">{{ $balance['reserved'] }}</p>
            </div>
            <div class="card p-4">
                <p class="stat-label">Available</p>
                <p class="text-2xl font-bold {{ $balance['available'] > 0 ? 'text-success-500' : 'text-danger-500' }}">{{ $balance['available'] }}</p>
            </div>
            <div class="card p-4">
                <p class="stat-label">Damaged</p>
                <p class="text-2xl font-bold {{ $balance['damaged'] > 0 ? 'text-danger-500' : 'text-content-muted' }}">{{ $balance['damaged'] }}</p>
            </div>
            <div class="card p-4">
                <p class="stat-label">Blocked</p>
                <p class="text-2xl font-bold {{ $balance['blocked'] > 0 ? 'text-warning-600' : 'text-content-muted' }}">{{ $balance['blocked'] }}</p>
            </div>
        </div>

        {{-- Stock by Warehouse --}}
        <div class="card p-6">
            <h3 class="text-base font-display font-semibold text-content mb-4">Stock by Warehouse</h3>
            @if($stockByWarehouse->count() > 0)
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Warehouse</th>
                                <th class="text-right">Physical</th>
                                <th class="text-right">Reserved</th>
                                <th class="text-right">Available</th>
                                <th class="text-right">Damaged</th>
                                <th class="text-right">Blocked</th>
                                <th class="text-right">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockByWarehouse as $row)
                                <tr>
                                    <td class="font-medium">{{ $row['warehouse'] }}</td>
                                    <td class="text-right">{{ $row['physical'] }}</td>
                                    <td class="text-right">{{ $row['reserved'] }}</td>
                                    <td class="text-right font-medium {{ $row['available'] > 0 ? 'text-success-500' : 'text-danger-500' }}">{{ $row['available'] }}</td>
                                    <td class="text-right">{{ $row['damaged'] }}</td>
                                    <td class="text-right">{{ $row['blocked'] }}</td>
                                    <td class="text-right font-mono text-xs">{{ number_format($row['value'] ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-content-secondary text-center py-4">No inventory records for this SKU.</p>
            @endif
        </div>

        {{-- Ledger History --}}
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-display font-semibold text-content">Ledger History</h3>
                <span class="text-xs text-content-muted">Immutable audit trail (BR-INV-002)</span>
            </div>
            @if($ledger->count() > 0)
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Before</th>
                                <th class="text-right">After</th>
                                <th>Reference</th>
                                <th>Reason</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ledger as $entry)
                                @php
                                    $typeLabel = str_replace('_', ' ', ucfirst($entry->transaction_type));
                                    $isPositive = $entry->quantity > 0;
                                @endphp
                                <tr>
                                    <td class="text-xs text-content-secondary whitespace-nowrap">{{ $entry->created_at->format('d M Y H:i') }}</td>
                                    <td>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                            {{ $isPositive ? 'bg-success-50 text-success-600' : 'bg-danger-50 text-danger-500' }}">
                                            {{ $typeLabel }}
                                        </span>
                                    </td>
                                    <td class="text-right font-mono text-sm font-medium {{ $isPositive ? 'text-success-500' : 'text-danger-500' }}">
                                        {{ $isPositive ? '+' : '' }}{{ $entry->quantity }}
                                    </td>
                                    <td class="text-right text-content-secondary">{{ $entry->balance_before }}</td>
                                    <td class="text-right font-medium">{{ $entry->balance_after }}</td>
                                    <td class="text-xs text-content-secondary font-mono">{{ $entry->source_document ?? '-' }}</td>
                                    <td class="text-xs text-content-secondary">{{ $entry->reason ?? '-' }}</td>
                                    <td class="text-xs text-content-secondary">{{ $entry->performer?->name ?? 'System' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $ledger->links() }}
                </div>
            @else
                <p class="text-sm text-content-secondary text-center py-4">No ledger entries yet.</p>
            @endif
        </div>

        {{-- Movement Chart Placeholder --}}
        <div class="card p-6">
            <h3 class="text-base font-display font-semibold text-content mb-4">Stock Movement</h3>
            <div class="flex items-center justify-center h-48 rounded-lg border-2 border-dashed border-surface-border">
                <p class="text-sm text-content-muted">Movement chart will be added in a future release.</p>
            </div>
        </div>
    </div>
</x-admin-layout>
