<x-admin-layout>
    <x-slot name="title">Opening Stock Import</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.inventory.index') }}" class="text-content-secondary hover:text-content">Inventory</a>
            <svg class="w-4 h-4 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span>Opening Stock Import</span>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        {{-- Instructions --}}
        <div class="card p-6">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-3">Import Instructions</h3>
            <div class="prose prose-sm text-content-secondary">
                <p>Upload a CSV file with the following columns:</p>
                <div class="table-container mt-3">
                    <table>
                        <thead>
                            <tr>
                                <th>Column</th>
                                <th>Description</th>
                                <th>Required</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="font-mono text-xs">sku_code</td>
                                <td class="text-content-secondary">Internal SKU code (must exist in system)</td>
                                <td><span class="badge-success">Yes</span></td>
                            </tr>
                            <tr>
                                <td class="font-mono text-xs">quantity</td>
                                <td class="text-content-secondary">Stock quantity (positive integer)</td>
                                <td><span class="badge-success">Yes</span></td>
                            </tr>
                            <tr>
                                <td class="font-mono text-xs">unit_cost</td>
                                <td class="text-content-secondary">Cost per unit (uses SKU cost price if blank)</td>
                                <td><span class="badge-neutral">Optional</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-content-muted">Example: <code>SKD-MOON-BLACK-M,5,220.00</code></p>
            </div>
        </div>

        {{-- Upload Form --}}
        <form method="POST" action="{{ route('admin.inventory.opening-stock.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="card p-6 space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Upload</h3>

                {{-- Warehouse --}}
                <div>
                    <label for="warehouse_id" class="mb-1 block text-sm font-medium text-content">Warehouse <span class="text-danger-500">*</span></label>
                    <select name="warehouse_id" id="warehouse_id" required
                            class="w-full rounded-lg border-surface-border text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        <option value="">Select Warehouse</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('warehouse_id') == $wh->id)>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                    @error('warehouse_id') <p class="mt-1 text-xs text-danger-500">{{ $message }}</p> @enderror
                </div>

                {{-- CSV File --}}
                <div>
                    <label for="csv_file" class="mb-1 block text-sm font-medium text-content">CSV File <span class="text-danger-500">*</span></label>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" required
                           class="w-full rounded-lg border border-surface-border bg-surface px-3 py-2 text-sm text-content file:mr-4 file:rounded file:border-0 file:bg-brand-50 file:px-3 file:py-1 file:text-xs file:font-medium file:text-brand-600 hover:file:bg-brand-100">
                    @error('csv_file') <p class="mt-1 text-xs text-danger-500">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-content-muted">Max file size: 2 MB. Accepted formats: .csv, .txt</p>
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
                        onclick="return confirm('This will import opening stock. Continue?')">
                    Import Opening Stock
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
