<x-admin-layout>
    <x-slot:title>Reorder Engine</x-slot:title>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-display font-semibold text-content">Reorder Engine</h1>
            <p class="text-sm text-content-secondary mt-0.5">Automated inventory reorder management</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.reorder.rules') }}" class="bg-white border border-surface-border text-content rounded-lg px-4 py-2 text-sm font-medium hover:bg-surface-secondary transition-colors">
                Manage Rules
            </a>
            <form method="POST" action="{{ route('admin.reorder.run-check') }}" class="inline">
                @csrf
                <button type="submit" class="bg-brand-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-brand-600 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Run Reorder Check
                </button>
            </form>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- Urgent Items --}}
        <div class="bg-white rounded-xl border border-surface-border p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-danger-500/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4.5 h-4.5 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-content-secondary">Urgent Items</p>
                    <p class="text-lg font-semibold text-danger-600">{{ $dashboardData['urgent_items'] }}</p>
                </div>
            </div>
        </div>

        {{-- Active Suggestions --}}
        <div class="bg-white rounded-xl border border-surface-border p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-warning-500/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4.5 h-4.5 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-content-secondary">Active Suggestions</p>
                    <p class="text-lg font-semibold text-content">{{ $dashboardData['active_suggestions'] }}</p>
                </div>
            </div>
        </div>

        {{-- POs Generated Today --}}
        <div class="bg-white rounded-xl border border-surface-border p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-success-500/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4.5 h-4.5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-content-secondary">POs Today</p>
                    <p class="text-lg font-semibold text-content">{{ $dashboardData['pos_generated_today'] }}</p>
                </div>
            </div>
        </div>

        {{-- Avg Coverage Days --}}
        <div class="bg-white rounded-xl border border-surface-border p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-info-500/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4.5 h-4.5 text-info-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-content-secondary">Avg Coverage</p>
                    <p class="text-lg font-semibold text-content">{{ $dashboardData['avg_coverage_days'] }} days</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Urgent Reorder Items --}}
        <div class="bg-white rounded-xl border border-surface-border">
            <div class="px-5 py-4 border-b border-surface-border flex items-center justify-between">
                <h2 class="text-sm font-semibold text-content flex items-center gap-2">
                    <svg class="w-4 h-4 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    Urgent Items
                </h2>
                <a href="{{ route('admin.reorder.suggestions', ['priority' => 'urgent']) }}" class="text-xs text-brand-600 hover:text-brand-700 font-medium">View All</a>
            </div>
            <div class="divide-y divide-surface-border-light">
                @forelse($dashboardData['urgent_suggestions'] as $suggestion)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-content">{{ $suggestion->sku->sku_code }}</p>
                            <p class="text-xs text-content-secondary">Stock: {{ $suggestion->current_stock }} | {{ $suggestion->days_until_stockout }}d left</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-medium text-danger-600 bg-danger-50 px-2 py-0.5 rounded">Urgent</span>
                            <form method="POST" action="{{ route('admin.reorder.convert-to-po', $suggestion) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-brand-600 hover:text-brand-700 font-medium">Create PO</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-6 text-center text-sm text-content-muted">No urgent items at this time.</div>
                @endforelse
            </div>
        </div>

        {{-- Suggestions by Vendor --}}
        <div class="bg-white rounded-xl border border-surface-border">
            <div class="px-5 py-4 border-b border-surface-border">
                <h2 class="text-sm font-semibold text-content">Suggestions by Vendor</h2>
            </div>
            <div class="divide-y divide-surface-border-light">
                @forelse($dashboardData['suggestions_by_vendor'] as $group)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-content">{{ $group['vendor']?->name ?? 'No Vendor' }}</p>
                            <p class="text-xs text-content-secondary">{{ $group['count'] }} suggestions</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-content">{{ number_format($group['total_cost'], 2) }}</p>
                            <p class="text-xs text-content-secondary">Est. Cost</p>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-6 text-center text-sm text-content-muted">No pending suggestions.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent Auto-Generated POs --}}
    @if($dashboardData['recent_pos']->isNotEmpty())
        <div class="bg-white rounded-xl border border-surface-border mt-6">
            <div class="px-5 py-4 border-b border-surface-border">
                <h2 class="text-sm font-semibold text-content">Recent Auto-Generated POs</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-surface-border-light">
                            <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">PO Number</th>
                            <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Vendor</th>
                            <th class="text-right px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Amount</th>
                            <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Status</th>
                            <th class="text-left px-5 py-3 text-xs font-medium text-content-secondary uppercase tracking-wider">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border-light">
                        @foreach($dashboardData['recent_pos'] as $po)
                            <tr class="hover:bg-surface-secondary/50 transition-colors">
                                <td class="px-5 py-3"><a href="{{ route('admin.purchase-orders.show', $po) }}" class="text-brand-600 hover:text-brand-700 font-medium">{{ $po->po_number }}</a></td>
                                <td class="px-5 py-3 text-content-secondary">{{ $po->vendor->name }}</td>
                                <td class="px-5 py-3 text-right font-medium">{{ number_format($po->total_amount, 2) }}</td>
                                <td class="px-5 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-{{ $po->status_color }}-50 text-{{ $po->status_color }}-600">{{ $po->status_label }}</span></td>
                                <td class="px-5 py-3 text-content-secondary text-xs">{{ $po->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-admin-layout>
