<x-admin-layout>
    <x-slot name="title">Notification Preferences</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.notifications.index') }}" class="text-content-secondary hover:text-content transition-colors">Notifications</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Preferences</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">Notification Preferences</h1>
    </div>

    <div class="bg-white rounded-xl border border-surface-border-light overflow-hidden">
        <form method="POST" action="{{ route('admin.notifications.update-preferences') }}">
            @csrf
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-surface-border-light">
                    <thead>
                        <tr class="bg-surface-secondary/50">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Notification Type</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">In-App</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Email</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">WhatsApp</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Enabled</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border-light">
                        @foreach ($preferences as $type => $pref)
                            <tr class="hover:bg-surface-secondary/30 transition-colors">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-content">{{ $pref['label'] }}</p>
                                    <p class="text-xs text-content-muted">{{ str_replace('_', ' ', $type) }}</p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="preferences[{{ $type }}][channel_in_app]" value="0">
                                        <input type="checkbox" name="preferences[{{ $type }}][channel_in_app]" value="1"
                                               {{ $pref['channel_in_app'] ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <div class="w-9 h-5 bg-surface-border rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-surface-border after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-500"></div>
                                    </label>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="preferences[{{ $type }}][channel_email]" value="0">
                                        <input type="checkbox" name="preferences[{{ $type }}][channel_email]" value="1"
                                               {{ $pref['channel_email'] ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <div class="w-9 h-5 bg-surface-border rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-surface-border after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-500"></div>
                                    </label>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="preferences[{{ $type }}][channel_whatsapp]" value="0">
                                        <input type="checkbox" name="preferences[{{ $type }}][channel_whatsapp]" value="1"
                                               {{ $pref['channel_whatsapp'] ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <div class="w-9 h-5 bg-surface-border rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-surface-border after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-500"></div>
                                    </label>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="preferences[{{ $type }}][is_enabled]" value="0">
                                        <input type="checkbox" name="preferences[{{ $type }}][is_enabled]" value="1"
                                               {{ $pref['is_enabled'] ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <div class="w-9 h-5 bg-surface-border rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-surface-border after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-500"></div>
                                    </label>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-surface-border-light px-4 py-3 flex justify-end">
                <button type="submit"
                        class="rounded-lg bg-brand-500 px-6 py-2 text-sm font-medium text-white hover:bg-brand-600 transition-colors">
                    Save Preferences
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
