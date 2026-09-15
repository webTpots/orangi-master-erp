<x-admin-layout>
    <x-slot:title>Reorder Suggestions</x-slot:title>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-display font-semibold text-content">Reorder Suggestions</h1>
            <p class="text-sm text-content-secondary mt-0.5">Review and convert reorder suggestions to purchase orders</p>
        </div>
        <a href="{{ route('admin.reorder.dashboard') }}" class="text-sm text-content-secondary hover:text-content transition-colors flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Dashboard
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-surface-border p-4 mb-6">
        <form method="GET" action="{{ route('admin.reorder.suggestions') }}" class="flex items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-content-secondary mb-1">Priority</label>
                <select name="priority" class="rounded-lg border border-surface-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                    <option value="">All</option>
                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                    <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-content-secondary mb-1">Status</label>
                <select name="status" class="rounded-lg border border-surface-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                    <option value="">All</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="converted_to_po" {{ request('status') === 'converted_to_po' ? 'selected' : '' }}>Converted</option>
                    <option value="dismissed" {{ request('status') === 'dismissed' ? 'selected' : '' }}>Dismissed</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-content-secondary mb-1">Vendor</label>
                <select name="vendor_id" class="rounded-lg border border-surface-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                    <option value="">All Vendors</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-brand-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-brand-600 transition-colors">Filter</button>
            <a href="{{ route('admin.reorder.suggestions') }}" class="text-sm text-content-secondary hover:text-content transition-colors">Clear</a>
        </form>
    </div>

    {{-- Bulk Actions --}}
    <form method="POST" action="{{ route('admin.reorder.bulk-convert') }}" id="bulkForm">
        @csrf
        <div class="bg-white rounded-xl border border-surface-border">
            <div class="px-5 py-3 border-b border-surface-border flex items-center justify-between">
                <div class="flex items-center gap-3" x-data="{ checked: false }">
                    <input type="checkbox" @change="document.querySelectorAll('.suggestion-check').forEach(c => c.checked = $event.target.checked)" class="rounded border-surface-border text-brand-500 focus:ring-brand-500/20">
                    <span class="text-xs text-content-secondary">Select All</span>
                </div>
                <button type="submit" onclick="return confirm('Convert selected suggestions to purchase orders?')" class="bg-brand-500 text-white rounded-lg px-3 py-1.5 text-xs font-medium hover:bg-brand-600 transition-colors">
                    Convert Selected to PO
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-surface-border-light">
                            <th class="w-10 px-5 py-3"></th>
                            <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">SKU</th>
                            <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Design</th>
                            <th class="text-right px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Stock</th>
                            <th class="text-right px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Run Rate</th>
                            <th class="text-right px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Days Left</th>
                            <th class="text-right px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Qty</th>
                            <th class="text-right px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Est. Cost</th>
                            <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Vendor</th>
                            <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Priority</th>
                            <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Status</th>
                            <th class="text-right px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border-light">
                        @forelse($suggestions as $suggestion)
                            @php
                                $priorityColors = ['urgent' => 'danger', 'high' => 'warning', 'medium' => 'brand', 'low' => 'info'];
                                $pColor = $priorityColors[$suggestion->priority] ?? 'neutral';
                            @endphp
                            <tr class="hover:bg-surface-secondary/50 transition-colors {{ $suggestion->priority === 'urgent' ? 'bg-danger-50/30' : '' }}">
                                <td class="px-5 py-3">
                                    @if($suggestion->status === 'pending')
                                        <input type="checkbox" name="suggestion_ids[]" value="{{ $suggestion->id }}" class="suggestion-check rounded border-surface-border text-brand-500 focus:ring-brand-500/20">
                                    @endif
                                </td>
                                <td class="px-5 py-3 font-mono text-xs font-medium">{{ $suggestion->sku->sku_code }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $suggestion->sku->variant?->product?->design?->name ?? '-' }}</td>
                                <td class="px-5 py-3 text-right">{{ $suggestion->current_stock }}</td>
                                <td class="px-5 py-3 text-right">{{ $suggestion->daily_run_rate }}/day</td>
                                <td class="px-5 py-3 text-right font-medium {{ $suggestion->days_until_stockout <= 3 ? 'text-danger-600' : '' }}">{{ $suggestion->days_until_stockout }}d</td>
                                <td class="px-5 py-3 text-right font-medium">{{ $suggestion->suggested_quantity }}</td>
                                <td class="px-5 py-3 text-right">{{ $suggestion->estimated_cost ? number_format($suggestion->estimated_cost, 2) : '-' }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $suggestion->vendor?->name ?? '-' }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-{{ $pColor }}-50 text-{{ $pColor }}-600">
                                        {{ ucfirst($suggestion->priority) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-neutral-100 text-content-secondary">
                                        {{ $suggestion->status_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @if($suggestion->status === 'pending' && $suggestion->vendor_id)
                                        <form method="POST" action="{{ route('admin.reorder.convert-to-po', $suggestion) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-xs text-brand-600 hover:text-brand-700 font-medium">Create PO</button>
                                        </form>
                                    @elseif($suggestion->purchase_order_id)
                                        <a href="{{ route('admin.purchase-orders.show', $suggestion->purchase_order_id) }}" class="text-xs text-brand-600 hover:text-brand-700 font-medium">View PO</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-5 py-8 text-center text-content-muted text-sm">No reorder suggestions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($suggestions->hasPages())
                <div class="px-5 py-3 border-t border-surface-border-light">
                    {{ $suggestions->links() }}
                </div>
            @endif
        </div>
    </form>
</x-admin-layout>
