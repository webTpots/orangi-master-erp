<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Admin' }} - {{ config('app.name', 'Orangi ERP') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased" x-cloak>
        <div
            class="min-h-screen flex bg-surface-secondary"
            x-data="{
                sidebarOpen: false,
                collapsed: false,
                isMobile: window.innerWidth < 1024,
                init() {
                    window.addEventListener('resize', () => {
                        this.isMobile = window.innerWidth < 1024;
                        if (!this.isMobile) this.sidebarOpen = false;
                    });
                }
            }"
        >
            {{-- Sidebar --}}
            <x-sidebar :collapsed="false" />

            {{-- Main Content --}}
            <div class="flex-1 flex flex-col min-h-screen min-w-0">
                {{-- Top bar --}}
                <header class="bg-white border-b border-surface-border sticky top-0 z-20">
                    <div class="flex items-center justify-between px-6 h-14">
                        <div class="flex items-center gap-4">
                            {{-- Mobile menu button --}}
                            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-content-secondary hover:text-content transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </button>
                            {{-- Collapse button --}}
                            <button @click="collapsed = !collapsed" class="hidden lg:flex items-center justify-center w-7 h-7 rounded-md text-content-secondary hover:text-content hover:bg-surface-secondary transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </button>

                            {{-- Breadcrumb --}}
                            @isset($header)
                                <nav class="flex items-center text-sm">
                                    <span class="text-content-secondary">{{ $header }}</span>
                                </nav>
                            @else
                                @isset($title)
                                    <nav class="flex items-center text-sm">
                                        <span class="font-medium text-content">{{ $title }}</span>
                                    </nav>
                                @endisset
                            @endisset
                        </div>
                        <div class="flex items-center gap-3">
                            {{-- Notification Bell --}}
                            @php
                                $unreadNotificationCount = \App\Models\AppNotification::forUser(auth()->id())->unread()->count();
                            @endphp
                            <a href="{{ route('admin.notifications.index') }}" class="relative flex items-center justify-center w-8 h-8 rounded-lg text-content-secondary hover:text-content hover:bg-surface-secondary transition-colors" title="Notifications">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                @if ($unreadNotificationCount > 0)
                                    <span class="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] rounded-full bg-danger-500 px-1 text-[10px] font-bold text-white">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
                                @endif
                            </a>

                            {{-- User dropdown --}}
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-surface-secondary transition-colors">
                                    <div class="w-7 h-7 rounded-lg bg-brand-500/10 flex items-center justify-center">
                                        <span class="text-brand-600 text-xs font-semibold">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                    </div>
                                    <span class="hidden sm:block text-sm text-content font-medium">{{ auth()->user()->name }}</span>
                                    <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="open" @click.outside="open = false" x-transition
                                     class="absolute right-0 mt-1 w-48 bg-white rounded-xl border border-surface-border shadow-lg py-1 z-50">
                                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-content hover:bg-surface-secondary transition-colors">Profile</a>
                                    <div class="border-t border-surface-border-light my-1"></div>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-danger-500 hover:bg-danger-50 transition-colors">Sign out</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                {{-- Flash messages --}}
                @if(session('success'))
                    <div class="mx-6 mt-4 rounded-lg bg-success-50 border border-success-500/20 px-4 py-3 flex items-center gap-3">
                        <svg class="w-4 h-4 text-success-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <p class="text-sm text-success-600">{{ session('success') }}</p>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mx-6 mt-4 rounded-lg bg-danger-50 border border-danger-500/20 px-4 py-3 flex items-center gap-3">
                        <svg class="w-4 h-4 text-danger-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <p class="text-sm text-danger-600">{{ session('error') }}</p>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mx-6 mt-4 rounded-lg bg-danger-50 border border-danger-500/20 px-4 py-3">
                        <ul class="list-disc list-inside text-sm text-danger-600 space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Page content --}}
                <main class="flex-1 p-6 max-w-[1400px] w-full">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
