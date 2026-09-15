<x-admin-layout>
    <x-slot name="title">Settlement Detail</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.settlements.index') }}" class="text-content-secondary hover:text-content transition-colors">Settlements</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">{{ $settlement->settlement_reference }}</span>
        </div>
    </x-slot>

    @php
        $statusColors = [
            'imported'              => 'bg-brand-50 text-brand-600',
            'processing'            => 'bg-warning-50 text-warning-600',
            'reconciled'            => 'bg-success-50 text-success-600',
            'partially_reconciled'  => 'bg-warning-50 text-warning-600',
            'disputed'              => 'bg-danger-50 text-danger-600',
            'closed'                => 'bg-neutral-50 text-neutral-500',
        ];
        $matchColors = [
            'matched'   => 'bg-success-50 text-success-600',
            'unmatched' => 'bg-warning-50 text-warning-600',
            'disputed'  => 'bg-danger-50 text-danger-600',
            'ignored'   => 'bg-neutral-50 text-neutral-500',
        ];
    @endphp

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="font-display text-xl font-bold text-content">{{ $settlement->settlement_reference }}</h1>
            <div class="flex items-center gap-3 mt-1">
                <span class="text-sm text-content-secondary">{{ $settlement->marketplaceAccount?->account_name }}</span>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$settlement->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                    {{ $settlement->status_label }}
                </span>
                <span class="text-sm text-content-muted">{{ $settlement->settlement_date->format('d M Y') }}</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if ($settlement->status !== 'closed')
                <form method="POST" action="{{ route('admin.settlements.reconcile', $settlement) }}" class="inline">
                    @csrf
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold transition-colors">
                        Reconcile
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.settlements.close', $settlement) }}" class="inline">
                    @csrf
                    <button type="submit" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold transition-colors"
                            onclick="return confirm('Are you sure you want to close this settlement?')">
                        Close Settlement
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Breakdown Card --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-6 mb-6">
        <h2 class="text-sm font-semibold text-content-secondary uppercase tracking-wider mb-4">Settlement Breakdown</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4">
            <div>
                <p class="text-xs text-content-muted">Order Amount</p>
                <p class="text-lg font-semibold text-content">{{ Number::currency($settlement->total_order_amount, 'INR') }}</p>
            </div>
            <div>
                <p class="text-xs text-content-muted">Commission</p>
                <p class="text-lg font-semibold text-danger-500">-{{ Number::currency($settlement->total_commission, 'INR') }}</p>
            </div>
            <div>
                <p class="text-xs text-content-muted">Shipping</p>
                <p class="text-lg font-semibold text-content-secondary">{{ Number::currency($settlement->total_shipping_fee, 'INR') }}</p>
            </div>
            <div>
                <p class="text-xs text-content-muted">TCS</p>
                <p class="text-lg font-semibold text-content-secondary">-{{ Number::currency($settlement->total_tcs, 'INR') }}</p>
            </div>
            <div>
                <p class="text-xs text-content-muted">TDS</p>
                <p class="text-lg font-semibold text-content-secondary">-{{ Number::currency($settlement->total_tds, 'INR') }}</p>
            </div>
            <div>
                <p class="text-xs text-content-muted">Penalties</p>
                <p class="text-lg font-semibold {{ $settlement->total_penalty > 0 ? 'text-danger-500' : 'text-content-muted' }}">-{{ Number::currency($settlement->total_penalty, 'INR') }}</p>
            </div>
            <div>
                <p class="text-xs text-content-muted">Net Payable</p>
                <p class="text-lg font-bold text-success-500">{{ Number::currency($settlement->net_payable, 'INR') }}</p>
            </div>
        </div>
    </div>

    {{-- Reconciliation Summary --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-6 mb-6">
        <h2 class="text-sm font-semibold text-content-secondary uppercase tracking-wider mb-4">Reconciliation Summary</h2>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            <div>
                <p class="text-xs text-content-muted">Total Lines</p>
                <p class="text-lg font-semibold text-content">{{ $summary['total_lines'] }}</p>
            </div>
            <div>
                <p class="text-xs text-content-muted">Matched</p>
                <p class="text-lg font-semibold text-success-500">{{ $summary['matched_count'] }}</p>
            </div>
            <div>
                <p class="text-xs text-content-muted">Unmatched</p>
                <p class="text-lg font-semibold text-warning-500">{{ $summary['unmatched_count'] }}</p>
            </div>
            <div>
                <p class="text-xs text-content-muted">Disputed</p>
                <p class="text-lg font-semibold text-danger-500">{{ $summary['disputed_count'] }}</p>
            </div>
            <div>
                <p class="text-xs text-content-muted">Match Rate</p>
                <p class="text-lg font-bold {{ $summary['match_percentage'] >= 90 ? 'text-success-500' : ($summary['match_percentage'] >= 50 ? 'text-warning-500' : 'text-danger-500') }}">{{ $summary['match_percentage'] }}%</p>
            </div>
        </div>
    </div>

    {{-- Line Items Table --}}
    <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
        <div class="px-4 py-3 border-b border-surface-border bg-surface-secondary">
            <h2 class="text-sm font-semibold text-content">Line Items ({{ $settlement->lines->count() }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-surface-border">
                <thead class="bg-surface-secondary/50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Order ID</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Product</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">SKU</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Qty</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Selling</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Commission</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Net</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Type</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Match</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border-light">
                    @forelse ($settlement->lines as $line)
                        <tr class="hover:bg-surface-secondary/50 transition">
                            <td class="px-3 py-3 text-xs font-mono text-content">
                                {{ \Illuminate\Support\Str::limit($line->marketplace_order_id, 20) }}
                                @if ($line->order)
                                    <a href="{{ route('admin.orders.show', $line->order) }}" class="block text-brand-500 hover:underline mt-0.5">Internal #{{ $line->order->id }}</a>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-sm text-content max-w-[180px] truncate">{{ $line->product_name ?? '-' }}</td>
                            <td class="px-3 py-3 text-xs font-mono text-content-secondary">{{ $line->sku_code ?? '-' }}</td>
                            <td class="px-3 py-3 text-center text-sm text-content">{{ $line->quantity }}</td>
                            <td class="px-3 py-3 text-right text-sm text-content">{{ Number::currency($line->selling_price, 'INR') }}</td>
                            <td class="px-3 py-3 text-right text-sm text-danger-500">-{{ Number::currency($line->marketplace_commission, 'INR') }}</td>
                            <td class="px-3 py-3 text-right text-sm font-medium text-content">{{ Number::currency($line->net_amount, 'INR') }}</td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-surface-secondary text-content-secondary">
                                    {{ ucfirst($line->settlement_type) }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $matchColors[$line->match_status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                    {{ $line->match_status_label }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right">
                                @if ($line->match_status === 'unmatched' && $settlement->status !== 'closed')
                                    <div class="flex items-center justify-end gap-1" x-data="{ showMatch: false, showDispute: false }">
                                        <button @click="showMatch = !showMatch" class="text-xs text-brand-500 hover:underline">Match</button>
                                        <button @click="showDispute = !showDispute" class="text-xs text-danger-500 hover:underline">Dispute</button>

                                        {{-- Match form (inline) --}}
                                        <div x-show="showMatch" @click.outside="showMatch = false" class="absolute right-4 mt-20 bg-white rounded-lg border border-surface-border shadow-lg p-3 z-10 w-64">
                                            <form method="POST" action="{{ route('admin.settlement-lines.match', $line) }}">
                                                @csrf
                                                <label class="text-xs font-medium text-content-secondary">Order ID</label>
                                                <input type="number" name="order_id" class="w-full mt-1 rounded-lg border-surface-border text-sm" placeholder="Internal order ID" required>
                                                <button type="submit" class="mt-2 w-full bg-brand-500 text-white rounded-lg px-3 py-1.5 text-xs font-medium hover:bg-brand-600 transition">Match</button>
                                            </form>
                                        </div>

                                        {{-- Dispute form (inline) --}}
                                        <div x-show="showDispute" @click.outside="showDispute = false" class="absolute right-4 mt-24 bg-white rounded-lg border border-surface-border shadow-lg p-3 z-10 w-64">
                                            <form method="POST" action="{{ route('admin.settlement-lines.dispute', $line) }}">
                                                @csrf
                                                <label class="text-xs font-medium text-content-secondary">Reason</label>
                                                <textarea name="reason" rows="2" class="w-full mt-1 rounded-lg border-surface-border text-sm" placeholder="Dispute reason..." required></textarea>
                                                <button type="submit" class="mt-2 w-full bg-danger-500 text-white rounded-lg px-3 py-1.5 text-xs font-medium hover:bg-danger-600 transition">Dispute</button>
                                            </form>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-content-muted text-xs">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-sm text-content-muted">No line items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
