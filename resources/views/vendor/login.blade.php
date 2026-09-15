<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Vendor Portal Login - {{ config('app.name', 'Orangi ERP') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-surface-secondary min-h-screen flex items-center justify-center">
        <div class="w-full max-w-md mx-auto px-6">
            <div class="text-center mb-8">
                <div class="w-14 h-14 rounded-2xl bg-brand-500 flex items-center justify-center mx-auto mb-4">
                    <span class="text-white font-display font-bold text-2xl">O</span>
                </div>
                <h1 class="text-xl font-display font-semibold text-content">Vendor Portal</h1>
                <p class="text-sm text-content-secondary mt-1">Enter your access token to continue</p>
            </div>

            @if(session('error'))
                <div class="mb-4 rounded-lg bg-danger-50 border border-danger-500/20 px-4 py-3 flex items-center gap-3">
                    <svg class="w-4 h-4 text-danger-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <p class="text-sm text-danger-600">{{ session('error') }}</p>
                </div>
            @endif

            @if(session('success'))
                <div class="mb-4 rounded-lg bg-success-50 border border-success-500/20 px-4 py-3 flex items-center gap-3">
                    <svg class="w-4 h-4 text-success-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <p class="text-sm text-success-600">{{ session('success') }}</p>
                </div>
            @endif

            <div class="bg-white rounded-xl border border-surface-border p-6">
                <form method="POST" action="{{ route('vendor.authenticate') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="token" class="block text-sm font-medium text-content mb-1.5">Access Token</label>
                        <input
                            type="text"
                            name="token"
                            id="token"
                            placeholder="Paste your access token here"
                            class="w-full rounded-lg border border-surface-border px-3 py-2.5 text-sm text-content placeholder-content-muted focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                            required
                            autofocus
                        >
                        @error('token')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full bg-brand-500 text-white rounded-lg px-4 py-2.5 text-sm font-medium hover:bg-brand-600 transition-colors">
                        Access Portal
                    </button>
                </form>
            </div>

            <p class="text-center text-xs text-content-muted mt-6">
                Don't have a token? Contact your buyer to get access.
            </p>
        </div>
    </body>
</html>
