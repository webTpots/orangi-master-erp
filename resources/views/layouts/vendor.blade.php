<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Vendor Portal' }} - {{ config('app.name', 'Orangi ERP') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-surface-secondary min-h-screen">
        {{-- Header --}}
        <header class="bg-white border-b border-surface-border sticky top-0 z-20">
            <div class="max-w-6xl mx-auto flex items-center justify-between px-6 h-14">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-brand-500 flex items-center justify-center flex-shrink-0">
                        <span class="text-white font-display font-bold text-sm">O</span>
                    </div>
                    <span class="font-display font-semibold text-sm tracking-tight text-content">Vendor Portal</span>
                </div>

                @if(session('vendor_portal_token'))
                    <nav class="flex items-center gap-4">
                        <a href="{{ route('vendor.dashboard') }}" class="text-sm {{ request()->routeIs('vendor.dashboard') ? 'text-brand-600 font-medium' : 'text-content-secondary hover:text-content' }} transition-colors">Dashboard</a>
                        <a href="{{ route('vendor.purchase-orders') }}" class="text-sm {{ request()->routeIs('vendor.purchase-orders*') ? 'text-brand-600 font-medium' : 'text-content-secondary hover:text-content' }} transition-colors">Purchase Orders</a>
                        <form method="POST" action="{{ route('vendor.logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-content-secondary hover:text-danger-500 transition-colors">Logout</button>
                        </form>
                    </nav>
                @endif
            </div>
        </header>

        {{-- Flash messages --}}
        <div class="max-w-6xl mx-auto px-6">
            @if(session('success'))
                <div class="mt-4 rounded-lg bg-success-50 border border-success-500/20 px-4 py-3 flex items-center gap-3">
                    <svg class="w-4 h-4 text-success-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <p class="text-sm text-success-600">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="mt-4 rounded-lg bg-danger-50 border border-danger-500/20 px-4 py-3 flex items-center gap-3">
                    <svg class="w-4 h-4 text-danger-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <p class="text-sm text-danger-600">{{ session('error') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="mt-4 rounded-lg bg-danger-50 border border-danger-500/20 px-4 py-3">
                    <ul class="list-disc list-inside text-sm text-danger-600 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Page content --}}
        <main class="max-w-6xl mx-auto p-6">
            {{ $slot }}
        </main>
    </body>
</html>
