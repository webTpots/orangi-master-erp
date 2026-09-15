<x-admin-layout>
    <x-slot name="title">Exception Categories</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.exceptions.index') }}" class="text-content-secondary hover:text-content transition-colors">Exception Center</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Categories</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">Exception Categories</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Category Table --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-surface-border-light overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-surface-border-light">
                        <thead>
                            <tr class="bg-surface-secondary/50">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Code</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Default Severity</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">SLA Hours</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Assignee Role</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border-light">
                            @forelse ($categories as $category)
                                @php
                                    $severityColors = [
                                        'critical' => 'bg-danger-50 text-danger-600',
                                        'high'     => 'bg-warning-50 text-warning-700',
                                        'medium'   => 'bg-yellow-50 text-yellow-700',
                                        'low'      => 'bg-blue-50 text-blue-600',
                                    ];
                                @endphp
                                <tr class="hover:bg-surface-secondary/30 transition-colors">
                                    <td class="px-4 py-3 text-sm font-medium text-content">{{ $category->name }}</td>
                                    <td class="px-4 py-3 text-sm font-mono text-content-secondary">{{ $category->code }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $severityColors[$category->default_severity] ?? 'bg-neutral-50 text-neutral-500' }}">
                                            {{ ucfirst($category->default_severity) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-content-secondary">
                                        {{ $category->sla_hours ? $category->sla_hours . 'h' : '---' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-content-secondary">
                                        {{ $category->default_assignee_role ?? '---' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($category->is_active)
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-success-50 text-success-600">Active</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-neutral-50 text-neutral-500">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center">
                                        <svg class="mx-auto h-10 w-10 text-content-muted/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        <p class="mt-2 text-sm text-content-muted">No categories yet. Create one to get started.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Add Category Form --}}
        <div>
            <div class="bg-white rounded-xl border border-surface-border-light p-5">
                <h3 class="text-sm font-semibold text-content mb-4">Add Category</h3>
                <form method="POST" action="{{ route('admin.exceptions.store-category') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs font-medium text-content-secondary">Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g., SKU Mismatch"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-content-secondary">Code</label>
                        <input type="text" name="code" value="{{ old('code') }}" required placeholder="e.g., sku_mismatch"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-content-secondary">Description</label>
                        <textarea name="description" rows="2" placeholder="Optional description..."
                                  class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-content-secondary">Default Severity</label>
                        <select name="default_severity" required
                                class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            <option value="low" {{ old('default_severity') === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('default_severity', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('default_severity') === 'high' ? 'selected' : '' }}>High</option>
                            <option value="critical" {{ old('default_severity') === 'critical' ? 'selected' : '' }}>Critical</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-content-secondary">Default Assignee Role</label>
                        <select name="default_assignee_role"
                                class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            <option value="">None</option>
                            <option value="admin" {{ old('default_assignee_role') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="operations" {{ old('default_assignee_role') === 'operations' ? 'selected' : '' }}>Operations</option>
                            <option value="warehouse" {{ old('default_assignee_role') === 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-content-secondary">SLA Hours</label>
                        <input type="number" name="sla_hours" value="{{ old('sla_hours') }}" min="1" placeholder="e.g., 24"
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">
                    </div>
                    <button type="submit"
                            class="w-full rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition-colors">
                        Create Category
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
