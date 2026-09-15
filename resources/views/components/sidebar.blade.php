@props(['collapsed' => false])

<aside
    x-show="sidebarOpen || !isMobile"
    x-transition:enter="transition-transform duration-300 ease-out"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition-transform duration-200 ease-in"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
    :class="collapsed ? 'w-[68px]' : 'w-[240px]'"
    class="fixed inset-y-0 left-0 z-40 flex flex-col bg-sidebar-bg transition-all duration-300 ease-in-out lg:relative"
    @click.outside="if(isMobile) sidebarOpen = false"
>
    {{-- Logo --}}
    <div class="flex items-center h-14 px-4 border-b border-white/5 flex-shrink-0">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-8 h-8 rounded-lg bg-brand-500 flex items-center justify-center flex-shrink-0">
                <span class="text-white font-display font-bold text-sm">O</span>
            </div>
            <span x-show="!collapsed" x-transition.opacity.duration.200ms class="text-white font-display font-semibold text-sm tracking-tight truncate">Orangi ERP</span>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto sidebar-scroll py-3 px-2">

        {{-- 1. Dashboard --}}
        <div class="mb-5">
            <a href="{{ route('admin.dashboard') }}"
               class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Dashboard" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-1a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1h-4a1 1 0 01-1-1v-5z"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Dashboard</span>
            </a>
        </div>

        {{-- 2. Analytics --}}
        <div class="mb-5">
            <p x-show="!collapsed" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-sidebar-text/30">Analytics</p>

            <div x-data="{ open: {{ request()->is('admin/analytics*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="sidebar-link w-full justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                        <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Analytics</span>
                    </div>
                    <svg x-show="!collapsed" :class="open ? 'rotate-90' : ''" class="w-3 h-3 text-sidebar-text/30 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div x-show="open && !collapsed" x-collapse class="ml-4 pl-3 border-l border-white/5 space-y-0.5 mt-0.5">
                    <a href="{{ route('admin.analytics.dashboard') }}" class="sidebar-sublink {{ request()->routeIs('admin.analytics.dashboard') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('admin.analytics.profit-loss') }}" class="sidebar-sublink {{ request()->routeIs('admin.analytics.profit-loss') ? 'active' : '' }}">P&L</a>
                    <a href="{{ route('admin.analytics.designs') }}" class="sidebar-sublink {{ request()->routeIs('admin.analytics.designs') ? 'active' : '' }}">Designs</a>
                    <a href="{{ route('admin.analytics.marketplaces') }}" class="sidebar-sublink {{ request()->routeIs('admin.analytics.marketplaces') ? 'active' : '' }}">Marketplaces</a>
                    <a href="{{ route('admin.analytics.vendors') }}" class="sidebar-sublink {{ request()->routeIs('admin.analytics.vendors') ? 'active' : '' }}">Vendors</a>
                    <a href="{{ route('admin.analytics.inventory') }}" class="sidebar-sublink {{ request()->routeIs('admin.analytics.inventory') ? 'active' : '' }}">Inventory</a>
                </div>
            </div>
        </div>

        {{-- 3. Products --}}
        <div class="mb-5">
            <p x-show="!collapsed" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-sidebar-text/30">Products</p>

            <div x-data="{ open: {{ request()->is('admin/products*') || request()->is('admin/designs*') || request()->is('admin/skus*') || request()->is('admin/sku-mappings*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="sidebar-link w-full justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Products</span>
                    </div>
                    <svg x-show="!collapsed" :class="open ? 'rotate-90' : ''" class="w-3 h-3 text-sidebar-text/30 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div x-show="open && !collapsed" x-collapse class="ml-4 pl-3 border-l border-white/5 space-y-0.5 mt-0.5">
                    <a href="{{ route('admin.designs.index') }}" class="sidebar-sublink {{ request()->routeIs('admin.designs.*') ? 'active' : '' }}">Designs</a>
                    <a href="{{ route('admin.skus.index') }}" class="sidebar-sublink {{ request()->routeIs('admin.skus.*') ? 'active' : '' }}">SKUs</a>
                    <a href="{{ route('admin.sku-mappings.index') }}" class="sidebar-sublink {{ request()->routeIs('admin.sku-mappings.*') ? 'active' : '' }}">SKU Mappings</a>
                    <a href="{{ route('admin.products.import') }}" class="sidebar-sublink {{ request()->routeIs('admin.products.import*') ? 'active' : '' }}">Import</a>
                </div>
            </div>
        </div>

        {{-- 4. Procurement --}}
        <div class="mb-5">
            <p x-show="!collapsed" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-sidebar-text/30">Procurement</p>

            {{-- Vendors --}}
            <a href="{{ route('admin.vendors.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.vendors.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Vendors" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Vendors</span>
            </a>

            {{-- Purchase Orders --}}
            <a href="{{ route('admin.purchase-orders.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.purchase-orders.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Purchase Orders" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Purchase Orders</span>
            </a>

            {{-- Reorder Engine --}}
            <div x-data="{ open: {{ request()->is('admin/reorder*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="sidebar-link w-full justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Reorder Engine</span>
                    </div>
                    <svg x-show="!collapsed" :class="open ? 'rotate-90' : ''" class="w-3 h-3 text-sidebar-text/30 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div x-show="open && !collapsed" x-collapse class="ml-4 pl-3 border-l border-white/5 space-y-0.5 mt-0.5">
                    <a href="{{ route('admin.reorder.dashboard') }}" class="sidebar-sublink {{ request()->routeIs('admin.reorder.dashboard') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('admin.reorder.suggestions') }}" class="sidebar-sublink {{ request()->routeIs('admin.reorder.suggestions') ? 'active' : '' }}">Suggestions</a>
                    <a href="{{ route('admin.reorder.rules') }}" class="sidebar-sublink {{ request()->routeIs('admin.reorder.rules') ? 'active' : '' }}">Rules</a>
                </div>
            </div>
        </div>

        {{-- 5. Inventory --}}
        <div class="mb-5">
            <p x-show="!collapsed" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-sidebar-text/30">Inventory</p>

            <a href="{{ route('admin.inventory.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Inventory" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Inventory</span>
            </a>
        </div>

        {{-- 6. Orders & Labels --}}
        <div class="mb-5">
            <p x-show="!collapsed" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-sidebar-text/30">Orders & Labels</p>

            {{-- Orders --}}
            <a href="{{ route('admin.orders.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Orders" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Orders</span>
            </a>

            {{-- Labels --}}
            <a href="{{ route('admin.labels.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.labels.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Labels" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Labels</span>
            </a>

            {{-- Manifests --}}
            <a href="{{ route('admin.manifests.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.manifests.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Manifests" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Manifests</span>
            </a>
        </div>

        {{-- 7. Logistics --}}
        <div class="mb-5">
            <p x-show="!collapsed" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-sidebar-text/30">Logistics</p>

            {{-- Shipments --}}
            <a href="{{ route('admin.shipments.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.shipments.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Shipments" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Shipments</span>
            </a>

            {{-- Scanning --}}
            <a href="{{ route('admin.scan.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.scan.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Scanning" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Scanning</span>
            </a>
        </div>

        {{-- 8. After-Sales --}}
        <div class="mb-5">
            <p x-show="!collapsed" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-sidebar-text/30">After-Sales</p>

            {{-- Returns & RTO --}}
            <a href="{{ route('admin.returns.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.returns.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Returns" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Returns & RTO</span>
            </a>

            {{-- Claims --}}
            <a href="{{ route('admin.claims.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.claims.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Claims" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Claims</span>
            </a>
        </div>

        {{-- 9. Finance --}}
        <div class="mb-5">
            <p x-show="!collapsed" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-sidebar-text/30">Finance</p>

            {{-- Settlements --}}
            <a href="{{ route('admin.settlements.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.settlements.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Settlements" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Settlements</span>
            </a>

            {{-- Payments --}}
            <a href="{{ route('admin.payments.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Payments" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Payments</span>
            </a>

            {{-- GST --}}
            <div x-data="{ open: {{ request()->is('admin/gst*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="sidebar-link w-full justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                        <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">GST</span>
                    </div>
                    <svg x-show="!collapsed" :class="open ? 'rotate-90' : ''" class="w-3 h-3 text-sidebar-text/30 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div x-show="open && !collapsed" x-collapse class="ml-4 pl-3 border-l border-white/5 space-y-0.5 mt-0.5">
                    <a href="{{ route('admin.gst.index') }}" class="sidebar-sublink {{ request()->routeIs('admin.gst.index') ? 'active' : '' }}">Entries</a>
                    <a href="{{ route('admin.gst.summary') }}" class="sidebar-sublink {{ request()->routeIs('admin.gst.summary') ? 'active' : '' }}">Summary</a>
                    <a href="{{ route('admin.gst.tcs-tds') }}" class="sidebar-sublink {{ request()->routeIs('admin.gst.tcs-tds') ? 'active' : '' }}">TCS / TDS</a>
                </div>
            </div>

            {{-- Bank Accounts --}}
            <a href="{{ route('admin.bank-accounts.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.bank-accounts.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Bank Accounts" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Bank Accounts</span>
            </a>

            {{-- Reconciliation --}}
            <div x-data="{ open: {{ request()->is('admin/bank-reconciliation*') || request()->is('admin/bank-transactions*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="sidebar-link w-full justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Reconciliation</span>
                    </div>
                    <svg x-show="!collapsed" :class="open ? 'rotate-90' : ''" class="w-3 h-3 text-sidebar-text/30 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div x-show="open && !collapsed" x-collapse class="ml-4 pl-3 border-l border-white/5 space-y-0.5 mt-0.5">
                    <a href="{{ route('admin.bank-reconciliation.index') }}" class="sidebar-sublink {{ request()->routeIs('admin.bank-reconciliation.index') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('admin.bank-reconciliation.transactions') }}" class="sidebar-sublink {{ request()->routeIs('admin.bank-reconciliation.transactions') ? 'active' : '' }}">Transactions</a>
                    <a href="{{ route('admin.bank-reconciliation.import') }}" class="sidebar-sublink {{ request()->routeIs('admin.bank-reconciliation.import') || request()->routeIs('admin.bank-reconciliation.process-import') ? 'active' : '' }}">Import</a>
                    <a href="{{ route('admin.bank-reconciliation.rules') }}" class="sidebar-sublink {{ request()->routeIs('admin.bank-reconciliation.rules') ? 'active' : '' }}">Rules</a>
                </div>
            </div>
        </div>

        {{-- 10. Marketplaces --}}
        <div class="mb-5">
            <p x-show="!collapsed" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-sidebar-text/30">Marketplaces</p>

            {{-- Flipkart --}}
            <div x-data="{ open: {{ request()->is('admin/flipkart*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="sidebar-link w-full justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72l1.189-1.19A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72M6.75 18h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .414.336.75.75.75z"/></svg>
                        <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Flipkart</span>
                    </div>
                    <svg x-show="!collapsed" :class="open ? 'rotate-90' : ''" class="w-3 h-3 text-sidebar-text/30 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div x-show="open && !collapsed" x-collapse class="ml-4 pl-3 border-l border-white/5 space-y-0.5 mt-0.5">
                    <a href="{{ route('admin.flipkart.dashboard') }}" class="sidebar-sublink {{ request()->routeIs('admin.flipkart.dashboard') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('admin.flipkart.sku-mapping') }}" class="sidebar-sublink {{ request()->routeIs('admin.flipkart.sku-mapping') ? 'active' : '' }}">SKU Mapping</a>
                    <a href="{{ route('admin.flipkart.import-labels') }}" class="sidebar-sublink {{ request()->routeIs('admin.flipkart.import-labels') ? 'active' : '' }}">Import Labels</a>
                    <a href="{{ route('admin.flipkart.import-settlement') }}" class="sidebar-sublink {{ request()->routeIs('admin.flipkart.import-settlement') ? 'active' : '' }}">Import Settlement</a>
                </div>
            </div>

            {{-- Marketplace Settings --}}
            <a href="{{ route('admin.marketplace-config.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.marketplace-config.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Marketplace Settings" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Settings</span>
            </a>
        </div>

        {{-- 11. AI Center --}}
        <div class="mb-5">
            <p x-show="!collapsed" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-sidebar-text/30">AI Center</p>

            <div x-data="{ open: {{ request()->is('admin/ai*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="sidebar-link w-full justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/></svg>
                        <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">AI Center</span>
                    </div>
                    <svg x-show="!collapsed" :class="open ? 'rotate-90' : ''" class="w-3 h-3 text-sidebar-text/30 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div x-show="open && !collapsed" x-collapse class="ml-4 pl-3 border-l border-white/5 space-y-0.5 mt-0.5">
                    <a href="{{ route('admin.ai.dashboard') }}" class="sidebar-sublink {{ request()->routeIs('admin.ai.dashboard') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('admin.ai.suggestions') }}" class="sidebar-sublink {{ request()->routeIs('admin.ai.suggestions') ? 'active' : '' }}">Suggestions</a>
                    <a href="{{ route('admin.ai.anomalies') }}" class="sidebar-sublink {{ request()->routeIs('admin.ai.anomalies') ? 'active' : '' }}">Anomalies</a>
                    <a href="{{ route('admin.ai.forecast') }}" class="sidebar-sublink {{ request()->routeIs('admin.ai.forecast') ? 'active' : '' }}">Forecast</a>
                    <a href="{{ route('admin.ai.document-analyzer') }}" class="sidebar-sublink {{ request()->routeIs('admin.ai.document-analyzer') ? 'active' : '' }}">Document Analyzer</a>
                    <a href="{{ route('admin.ai.sku-matcher') }}" class="sidebar-sublink {{ request()->routeIs('admin.ai.sku-matcher') ? 'active' : '' }}">SKU Matcher</a>
                </div>
            </div>
        </div>

        {{-- 12. System --}}
        <div class="mb-4">
            <p x-show="!collapsed" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-sidebar-text/30">System</p>

            {{-- Exceptions --}}
            <a href="{{ route('admin.exceptions.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.exceptions.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Exceptions" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Exceptions</span>
            </a>

            {{-- Notifications --}}
            <a href="{{ route('admin.notifications.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Notifications" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Notifications</span>
            </a>

            {{-- Vendor Portal --}}
            <a href="{{ route('admin.vendor-portal.tokens') }}"
               class="sidebar-link {{ request()->routeIs('admin.vendor-portal.*') ? 'active' : '' }}"
               @if($collapsed ?? false) title="Vendor Portal" @endif>
                <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="sidebar-label">Vendor Portal</span>
            </a>
        </div>
    </nav>

    {{-- User Info --}}
    <div class="border-t border-white/5 p-3 flex-shrink-0">
        <div class="flex items-center gap-3" :class="collapsed ? 'justify-center' : ''">
            <div class="w-8 h-8 rounded-lg bg-brand-500/20 flex items-center justify-center flex-shrink-0">
                <span class="text-brand-400 text-xs font-semibold">{{ substr(Auth::user()->name ?? 'U', 0, 1) }}</span>
            </div>
            <div x-show="!collapsed" x-transition.opacity.duration.200ms class="flex-1 min-w-0">
                <p class="text-white text-xs font-medium truncate">{{ Auth::user()->name ?? 'User' }}</p>
                <p class="text-sidebar-text/50 text-[10px] truncate">{{ Auth::user()->email ?? '' }}</p>
            </div>
            <form x-show="!collapsed" method="POST" action="{{ route('logout') }}" class="flex-shrink-0">
                @csrf
                <button type="submit" class="text-sidebar-text/30 hover:text-white transition-colors" title="Logout">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- Mobile Overlay --}}
<div
    x-show="sidebarOpen && isMobile"
    x-transition.opacity
    @click="sidebarOpen = false"
    class="fixed inset-0 bg-black/50 z-30 lg:hidden"
></div>

<style>
    .sidebar-link {
        @apply flex items-center gap-3 px-3 py-2 rounded-lg text-sidebar-text/60 hover:text-white hover:bg-white/5 transition-all duration-150 text-sm;
    }
    .sidebar-link.active {
        @apply bg-brand-500/10 text-brand-400 border-l-2 border-brand-400;
    }
    .sidebar-icon {
        @apply w-[18px] h-[18px] flex-shrink-0;
    }
    .sidebar-label {
        @apply truncate text-[13px];
    }
    .sidebar-sublink {
        @apply block py-1.5 px-3 text-xs text-sidebar-text/45 hover:text-white rounded-md hover:bg-white/5 transition-all duration-150;
    }
    .sidebar-sublink.active {
        @apply text-white bg-white/5;
    }
</style>
