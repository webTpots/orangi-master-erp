<x-admin-layout>
    <x-slot name="title">Notifications</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Notifications</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <h1 class="font-display text-xl font-bold text-content">Notifications</h1>
            @if ($unreadCount > 0)
                <span class="inline-flex items-center rounded-full bg-danger-500 px-2.5 py-0.5 text-xs font-semibold text-white">
                    {{ $unreadCount }} unread
                </span>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.notifications.preferences') }}"
               class="border border-surface-border text-content hover:bg-surface-secondary rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Preferences
            </a>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}" class="inline">
                    @csrf
                    <button type="submit"
                       class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Mark All Read
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Notifications List --}}
    <div class="space-y-3">
        @forelse ($notifications as $notification)
            @php
                $typeIcons = [
                    'order_exception'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>',
                    'settlement_imported' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
                    'inventory_alert'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>',
                    'return_received'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/>',
                    'claim_update'        => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
                    'exception_assigned'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
                    'exception_escalated' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>',
                    'sla_breach'          => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    'system'              => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                ];
                $icon = $typeIcons[$notification->type] ?? $typeIcons['system'];
            @endphp

            <div class="bg-white rounded-xl border border-surface-border-light p-4 {{ !$notification->is_read ? 'border-l-4 border-l-brand-500' : '' }} hover:bg-surface-secondary/30 transition-colors">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-9 h-9 rounded-lg {{ !$notification->is_read ? 'bg-brand-50' : 'bg-surface-secondary' }} flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 {{ !$notification->is_read ? 'text-brand-500' : 'text-content-muted' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $icon !!}</svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm {{ !$notification->is_read ? 'font-semibold text-content' : 'font-medium text-content-secondary' }}">
                                    {{ $notification->title }}
                                </p>
                                <p class="text-sm text-content-secondary mt-0.5">{{ $notification->message }}</p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-xs text-content-muted whitespace-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                                @if (!$notification->is_read)
                                    <form method="POST" action="{{ route('admin.notifications.mark-read', $notification) }}" class="inline">
                                        @csrf
                                        <button type="submit" title="Mark as read"
                                                class="text-content-muted hover:text-brand-500 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        @if ($notification->action_url)
                            <a href="{{ $notification->action_url }}" class="inline-flex items-center gap-1 text-xs font-medium text-brand-500 hover:text-brand-600 mt-1.5 transition-colors">
                                View Details
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-surface-border-light p-12 text-center">
                <svg class="mx-auto h-10 w-10 text-content-muted/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <p class="mt-2 text-sm text-content-muted">No notifications yet.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($notifications->hasPages())
        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    @endif
</x-admin-layout>
