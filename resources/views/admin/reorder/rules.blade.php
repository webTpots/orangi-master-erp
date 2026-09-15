<x-admin-layout>
    <x-slot:title>Reorder Rules</x-slot:title>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-display font-semibold text-content">Reorder Rules</h1>
            <p class="text-sm text-content-secondary mt-0.5">Configure automatic reorder triggers</p>
        </div>
        <a href="{{ route('admin.reorder.dashboard') }}" class="text-sm text-content-secondary hover:text-content transition-colors flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Dashboard
        </a>
    </div>

    {{-- Add Rule Form --}}
    <div class="bg-white rounded-xl border border-surface-border p-5 mb-6">
        <h2 class="text-sm font-semibold text-content mb-4">Add Reorder Rule</h2>
        <form method="POST" action="{{ route('admin.reorder.store-rule') }}">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-content-secondary mb-1">Rule Type</label>
                    <select name="rule_type" required class="w-full rounded-lg border border-surface-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="min_stock">Min Stock Level</option>
                        <option value="days_of_stock">Days of Stock</option>
                        <option value="forecast_based">Forecast Based</option>
                        <option value="manual">Manual</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-content-secondary mb-1">SKU (optional)</label>
                    <select name="sku_id" class="w-full rounded-lg border border-surface-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">All SKUs</option>
                        @foreach($skus as $sku)
                            <option value="{{ $sku->id }}">{{ $sku->sku_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-content-secondary mb-1">Design (optional)</label>
                    <select name="design_id" class="w-full rounded-lg border border-surface-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">All Designs</option>
                        @foreach($designs as $design)
                            <option value="{{ $design->id }}">{{ $design->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-content-secondary mb-1">Preferred Vendor</label>
                    <select name="vendor_id" class="w-full rounded-lg border border-surface-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">Auto-detect</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-content-secondary mb-1">Min Stock Threshold</label>
                    <input type="number" name="min_stock_threshold" min="0" placeholder="e.g. 50" class="w-full rounded-lg border border-surface-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-content-secondary mb-1">Days of Stock Threshold</label>
                    <input type="number" name="days_of_stock_threshold" min="1" placeholder="e.g. 7" class="w-full rounded-lg border border-surface-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-content-secondary mb-1">Reorder Quantity</label>
                    <input type="number" name="reorder_quantity" min="1" placeholder="Auto-calculate" class="w-full rounded-lg border border-surface-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-content-secondary mb-1">Max Order Quantity</label>
                    <input type="number" name="max_order_quantity" min="1" placeholder="No limit" class="w-full rounded-lg border border-surface-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                </div>
            </div>
            <button type="submit" class="bg-brand-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-brand-600 transition-colors">
                Add Rule
            </button>
        </form>
    </div>

    {{-- Rules List --}}
    <div class="bg-white rounded-xl border border-surface-border">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-surface-border-light">
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Type</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Target</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Threshold</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Vendor</th>
                        <th class="text-right px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Reorder Qty</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Last Triggered</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Status</th>
                        <th class="text-right px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border-light">
                    @forelse($rules as $rule)
                        <tr class="hover:bg-surface-secondary/50 transition-colors {{ !$rule->is_active ? 'opacity-50' : '' }}">
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-brand-50 text-brand-600">
                                    {{ $rule->rule_type_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-content-secondary">{{ $rule->target_label }}</td>
                            <td class="px-5 py-3 text-content-secondary">
                                @if($rule->rule_type === 'min_stock')
                                    {{ $rule->min_stock_threshold ?? '-' }} units
                                @elseif($rule->rule_type === 'days_of_stock')
                                    {{ $rule->days_of_stock_threshold ?? '-' }} days
                                @else
                                    Auto
                                @endif
                            </td>
                            <td class="px-5 py-3 text-content-secondary">{{ $rule->vendor?->name ?? 'Auto' }}</td>
                            <td class="px-5 py-3 text-right">{{ $rule->reorder_quantity ?? 'Auto' }}</td>
                            <td class="px-5 py-3 text-content-secondary text-xs">{{ $rule->last_triggered_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-5 py-3">
                                @if($rule->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-success-50 text-success-600">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-neutral-100 text-content-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('admin.reorder.toggle-rule', $rule) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium {{ $rule->is_active ? 'text-danger-500 hover:text-danger-600' : 'text-success-500 hover:text-success-600' }}">
                                        {{ $rule->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-content-muted text-sm">No reorder rules configured yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rules->hasPages())
            <div class="px-5 py-3 border-t border-surface-border-light">
                {{ $rules->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
