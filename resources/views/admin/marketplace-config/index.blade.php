<x-admin-layout>
    <x-slot:title>Marketplace Settings</x-slot:title>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-display font-semibold text-content">Marketplace Settings</h1>
            <p class="text-sm text-content-secondary mt-0.5">Configure marketplace-specific settings</p>
        </div>
        <a href="{{ route('admin.marketplace-config.comparison') }}" class="bg-white border border-surface-border text-content rounded-lg px-4 py-2 text-sm font-medium hover:bg-surface-secondary transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/></svg>
            Compare
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($marketplaces as $marketplace)
            <div class="bg-white rounded-xl border border-surface-border p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-brand-500/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72l1.189-1.19A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72M6.75 18h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .414.336.75.75.75z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-content">{{ $marketplace->name }}</h3>
                        <p class="text-xs text-content-muted">{{ strtoupper($marketplace->code) }}</p>
                    </div>
                </div>

                <dl class="space-y-2 text-sm mb-4">
                    <div class="flex justify-between">
                        <dt class="text-content-secondary">Commission</dt>
                        <dd class="font-medium text-content">{{ $marketplace->configs->get('commission_rate', '-') }}{{ $marketplace->configs->has('commission_rate') ? '%' : '' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-content-secondary">Return Window</dt>
                        <dd class="font-medium text-content">{{ $marketplace->configs->get('return_window_days', '-') }}{{ $marketplace->configs->has('return_window_days') ? ' days' : '' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-content-secondary">Cutoff Time</dt>
                        <dd class="font-medium text-content">{{ $marketplace->configs->get('cutoff_time', '-') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-content-secondary">SLA Days</dt>
                        <dd class="font-medium text-content">{{ $marketplace->configs->get('sla_days', '-') }}</dd>
                    </div>
                </dl>

                <a href="{{ route('admin.marketplace-config.edit', $marketplace) }}" class="block text-center bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-medium hover:bg-surface-border/50 transition-colors">
                    Edit Settings
                </a>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-content-muted">
                <svg class="w-12 h-12 mx-auto mb-3 text-content-muted/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72l1.189-1.19A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72M6.75 18h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .414.336.75.75.75z"/></svg>
                <p class="text-sm">No marketplaces configured yet.</p>
            </div>
        @endforelse
    </div>
</x-admin-layout>
