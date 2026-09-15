<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name', 'Orangi ERP') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface">
    <div class="min-h-screen flex" x-data="{ showPassword: false }">

        {{-- Left Brand Panel --}}
        <div class="hidden lg:flex lg:w-[480px] xl:w-[520px] flex-col justify-between p-10 relative overflow-hidden"
             style="background: linear-gradient(135deg, #C1502E 0%, #9C3F24 40%, #7A2D18 70%, #3D1309 100%);">

            {{-- Decorative elements --}}
            <div class="absolute top-0 right-0 w-64 h-64 rounded-full opacity-10"
                 style="background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%); transform: translate(30%, -30%);"></div>
            <div class="absolute bottom-0 left-0 w-80 h-80 rounded-full opacity-5"
                 style="background: radial-gradient(circle, rgba(255,255,255,0.4) 0%, transparent 70%); transform: translate(-30%, 30%);"></div>

            {{-- Logo --}}
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <span class="text-white font-display font-bold text-xl">O</span>
                    </div>
                    <span class="text-white font-display font-semibold text-xl tracking-tight">Orangi ERP</span>
                </div>
                <p class="text-white/60 text-sm ml-[52px]">Enterprise Resource Planning</p>
            </div>

            {{-- Features --}}
            <div class="space-y-6 relative z-10">
                <h2 class="text-white font-display text-2xl font-semibold leading-tight">
                    Complete Control Over<br>Your Operations
                </h2>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div>
                            <p class="text-white text-sm font-medium">Multi-Channel Order Management</p>
                            <p class="text-white/50 text-xs mt-0.5">Meesho, Flipkart, and more in one place</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <div>
                            <p class="text-white text-sm font-medium">Real-Time Inventory Tracking</p>
                            <p class="text-white/50 text-xs mt-0.5">SKU-level stock with automated alerts</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-white text-sm font-medium">Financial Reconciliation</p>
                            <p class="text-white/50 text-xs mt-0.5">Payments, GST, claims automated</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div>
                            <p class="text-white text-sm font-medium">Analytics & Reports</p>
                            <p class="text-white/50 text-xs mt-0.5">Business intelligence at your fingertips</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="relative z-10">
                <div class="h-px bg-white/10 mb-4"></div>
                <p class="text-white/40 text-xs">&copy; {{ date('Y') }} Orangi ERP. Built by TPOTS.</p>
            </div>
        </div>

        {{-- Right Login Form --}}
        <div class="flex-1 flex items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-[400px]">

                {{-- Mobile Logo --}}
                <div class="lg:hidden flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-brand-500 flex items-center justify-center">
                        <span class="text-white font-display font-bold text-xl">O</span>
                    </div>
                    <span class="text-content font-display font-semibold text-xl tracking-tight">Orangi ERP</span>
                </div>

                <div class="mb-8">
                    <h1 class="text-2xl font-display font-semibold text-content">Welcome back</h1>
                    <p class="text-content-secondary text-sm mt-1">Sign in to your account to continue</p>
                </div>

                {{-- Error Display --}}
                @if ($errors->any())
                    <div class="mb-5 p-3 rounded-lg bg-danger-50 border border-danger-border">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-danger-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-danger-500 text-sm">{{ $errors->first() }}</p>
                        </div>
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-5 p-3 rounded-lg bg-success-50 border border-success-500/20">
                        <p class="text-success-500 text-sm">{{ session('status') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    {{-- Email or Phone --}}
                    <div>
                        <label for="login" class="block text-sm font-medium text-content mb-1.5">Email or Phone</label>
                        <input
                            id="login"
                            name="login"
                            type="text"
                            value="{{ old('login') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="you@company.com or 9876543210"
                            class="input"
                        >
                    </div>

                    {{-- Password --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-sm font-medium text-content">Password</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs text-brand-500 hover:text-brand-600 font-medium">Forgot password?</a>
                            @endif
                        </div>
                        <div class="relative">
                            <input
                                id="password"
                                name="password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                class="input pr-10"
                            >
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-content-muted hover:text-content-secondary transition-colors"
                            >
                                <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Remember Me --}}
                    <div class="flex items-center gap-2">
                        <input
                            id="remember"
                            name="remember"
                            type="checkbox"
                            class="w-4 h-4 rounded border-surface-border text-brand-500 focus:ring-brand-500"
                        >
                        <label for="remember" class="text-sm text-content-secondary select-none">Remember me</label>
                    </div>

                    {{-- Submit --}}
                    <button type="submit" class="btn-primary w-full py-2.5">
                        Sign In
                    </button>
                </form>

                <p class="mt-8 text-center text-xs text-content-muted">
                    Orangi ERP v1.0 &middot; Powered by TPOTS
                </p>
            </div>
        </div>
    </div>
</body>
</html>
