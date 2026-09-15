<x-admin-layout>
    <x-slot name="title">Exception Center</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Exception Center</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">Exception Center</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.exceptions.categories') }}"
               class="border border-surface-border text-content hover:bg-surface-secondary rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Categories
            </a>
            <form method="POST" action="{{ route('admin.exceptions.auto-detect') }}" class="inline">
                @csrf
                <button type="submit"
                   class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Auto-Detect
                </button>
            </form>
        </div>
    </div>

    {{-- KPI Row --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-4 h-4 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Open Exceptions</p>
            </div>
            <p class="mt-1 text-2xl font-semibold text-content">{{ $dashboard['open_exceptions'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-4 h-4 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Critical</p>
            </div>
            <p class="mt-1 text-2xl font-semibold text-danger-500">{{ $dashboard['critical_count'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-4 h-4 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">SLA Breaches</p>
            </div>
            <p class="mt-1 text-2xl font-semibold text-warning-500">{{ $dashboard['sla_breaches'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Resolved Today</p>
            </div>
            <p class="mt-1 text-2xl font-semibold text-success-500">{{ $dashboard['resolved_today'] }}</p>
        </div>
    </div>

    {{-- Severity Filter Tabs --}}
    <div class="flex flex-wrap gap-2 mb-4">
        @php
            $severityTabs = [
                '' => 'All',
                'critical' => 'Critical',
                'high' => 'High',
                'medium' => 'Medium',
                'low' => 'Low',
            ];
        @endphp
        @foreach ($severityTabs as $val => $label)
            @php $isActive = ($val === '' && !request('severity')) || request('severity') === $val; @endphp
            <a href="{{ route('admin.exceptions.index', array_merge(request()->except('severity', 'page'), $val ? ['severity' => $val] : [])) }}"
               class="rounded-full px-4 py-1.5 text-sm font-medium transition
                   {{ $isActive ? 'bg-brand-500 text-white shadow-brand' : 'bg-surface-secondary text-content-secondary hover:bg-surface-tertiary' }}">
                {{ $label }}
                @if ($val && isset($dashboard['by_severity'][$val]) && $dashboard['by_severity'][$val] > 0)
                    <span class="ml-1 text-xs opacity-75">({{ $dashboard['by_severity'][$val] }})</span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Search & Filters --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-4 mb-4">
        <form method="GET" action="{{ route('admin.exceptions.index') }}" class="flex flex-wrap items-end gap-4">
            @if (request('severity'))
                <input type="hidden" name="severity" value="{{ request('severity') }}">
            @endif

            <div class="flex-1 min-w-[200px]">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ID, title, description..."
                       class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">
            </div>
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Status</label>
                <select name="status"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    <option value="">All Statuses</option>
                    @foreach (\App\Models\AppException::STATUS_LABELS as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Category</label>
                <select name="category_id"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition-colors">
                    Filter
                </button>
                <a href="{{ route('admin.exceptions.index') }}"
                   class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Exception Table --}}
    <div class="bg-white rounded-xl border border-surface-border-light overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-surface-border-light">
                <thead>
                    <tr class="bg-surface-secondary/50">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Severity</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Assigned To</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Created</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">SLA</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border-light">
                    @forelse ($exceptions as $exception)
                        @php
                            $severityColors = [
                                'critical' => 'bg-danger-50 text-danger-600',
                                'high'     => 'bg-warning-50 text-warning-700',
                                'medium'   => 'bg-yellow-50 text-yellow-700',
                                'low'      => 'bg-blue-50 text-blue-600',
                            ];
                            $statusColors = [
                                'open'      => 'bg-danger-50 text-danger-600',
                                'in_review' => 'bg-warning-50 text-warning-600',
                                'resolved'  => 'bg-success-50 text-success-600',
                                'ignored'   => 'bg-neutral-50 text-neutral-500',
                            ];
                            $slaHours = $exception->slaRemainingHours();
                        @endphp
                        <tr class="hover:bg-surface-secondary/30 transition-colors">
                            <td class="px-4 py-3 text-sm font-mono text-content-secondary">
                                EXC-{{ str_pad($exception->id, 5, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($exception->category)
                                    <span class="inline-flex items-center rounded-md bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-600">
                                        {{ $exception->category->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-content-muted">{{ $exception->exception_type }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.exceptions.show', $exception) }}" class="text-sm font-medium text-content hover:text-brand-500 transition-colors">
                                    {{ Str::limit($exception->title, 50) }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $severityColors[$exception->severity] ?? 'bg-neutral-50 text-neutral-500' }}">
                                    {{ ucfirst($exception->severity) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$exception->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                    {{ \App\Models\AppException::STATUS_LABELS[$exception->status] ?? ucfirst($exception->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-content-secondary">
                                {{ $exception->assignee?->name ?? '---' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-content-secondary">
                                {{ $exception->created_at->format('d M Y') }}
                                <span class="text-xs text-content-muted block">{{ $exception->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($slaHours !== null && in_array($exception->status, ['open', 'in_review']))
                                    @if ($slaHours < 0)
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-danger-500">
                                            <span class="w-2 h-2 rounded-full bg-danger-500 animate-pulse"></span>
                                            Breached
                                        </span>
                                    @elseif ($slaHours <= 4)
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-warning-500">
                                            <span class="w-2 h-2 rounded-full bg-warning-500"></span>
                                            {{ round($slaHours) }}h left
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-success-500">
                                            <span class="w-2 h-2 rounded-full bg-success-500"></span>
                                            {{ round($slaHours) }}h left
                                        </span>
                                    @endif
                                @else
                                    <span class="text-xs text-content-muted">---</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.exceptions.show', $exception) }}"
                                   class="inline-flex items-center gap-1 rounded-lg border border-surface-border px-2.5 py-1 text-xs font-medium text-content-secondary hover:text-content hover:bg-surface-secondary transition-colors">
                                    View
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center">
                                <svg class="mx-auto h-10 w-10 text-content-muted/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="mt-2 text-sm text-content-muted">No exceptions found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if ($exceptions->hasPages())
        <div class="mt-4">
            {{ $exceptions->links() }}
        </div>
    @endif
</x-admin-layout>
