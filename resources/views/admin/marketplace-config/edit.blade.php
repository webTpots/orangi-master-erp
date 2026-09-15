<x-admin-layout>
    <x-slot:title>Edit {{ $marketplace->name }} Settings</x-slot:title>

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.marketplace-config.index') }}" class="text-content-secondary hover:text-content transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-lg font-display font-semibold text-content">{{ $marketplace->name }} Settings</h1>
                <p class="text-sm text-content-secondary mt-0.5">Configure settings for {{ $marketplace->name }}</p>
            </div>
        </div>
    </div>

    <div class="max-w-2xl">
        <div class="bg-white rounded-xl border border-surface-border p-6">
            {{-- Marketplace Header --}}
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-surface-border-light">
                <div class="w-12 h-12 rounded-lg bg-brand-500/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72l1.189-1.19A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72M6.75 18h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .414.336.75.75.75z"/></svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-content">{{ $marketplace->name }}</h2>
                    <p class="text-xs text-content-muted">Code: {{ strtoupper($marketplace->code) }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.marketplace-config.update', $marketplace) }}">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label for="cutoff_time" class="block text-sm font-medium text-content mb-1">Cutoff Time</label>
                        <input type="time" name="cutoff_time" id="cutoff_time" value="{{ $configs->get('cutoff_time', '') }}" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <p class="mt-1 text-xs text-content-muted">Order processing cutoff time for same-day dispatch</p>
                    </div>

                    <div>
                        <label for="commission_rate" class="block text-sm font-medium text-content mb-1">Commission Rate (%)</label>
                        <input type="number" name="commission_rate" id="commission_rate" step="0.01" min="0" max="100" value="{{ $configs->get('commission_rate', '') }}" placeholder="e.g. 15.00" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <p class="mt-1 text-xs text-content-muted">Marketplace commission percentage</p>
                    </div>

                    <div>
                        <label for="return_window_days" class="block text-sm font-medium text-content mb-1">Return Window (Days)</label>
                        <input type="number" name="return_window_days" id="return_window_days" min="0" value="{{ $configs->get('return_window_days', '') }}" placeholder="e.g. 7" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <p class="mt-1 text-xs text-content-muted">Number of days customers can return products</p>
                    </div>

                    <div>
                        <label for="sla_days" class="block text-sm font-medium text-content mb-1">SLA Days</label>
                        <input type="number" name="sla_days" id="sla_days" min="0" value="{{ $configs->get('sla_days', '') }}" placeholder="e.g. 3" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <p class="mt-1 text-xs text-content-muted">Dispatch SLA in days</p>
                    </div>

                    <div>
                        <label for="avg_delivery_days" class="block text-sm font-medium text-content mb-1">Average Delivery Days</label>
                        <input type="number" name="avg_delivery_days" id="avg_delivery_days" min="0" value="{{ $configs->get('avg_delivery_days', '') }}" placeholder="e.g. 5" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <p class="mt-1 text-xs text-content-muted">Average days to deliver orders</p>
                    </div>

                    <div>
                        <label for="payment_cycle_days" class="block text-sm font-medium text-content mb-1">Payment Cycle (Days)</label>
                        <input type="number" name="payment_cycle_days" id="payment_cycle_days" min="0" value="{{ $configs->get('payment_cycle_days', '') }}" placeholder="e.g. 15" class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <p class="mt-1 text-xs text-content-muted">Settlement payment cycle in days</p>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-surface-border-light flex items-center gap-3">
                    <button type="submit" class="bg-brand-500 text-white rounded-lg px-6 py-2.5 text-sm font-medium hover:bg-brand-600 transition-colors">
                        Save Settings
                    </button>
                    <a href="{{ route('admin.marketplace-config.index') }}" class="text-sm text-content-secondary hover:text-content transition-colors">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
