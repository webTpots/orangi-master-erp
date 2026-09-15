<x-admin-layout>
    <x-slot name="title">Reconciliation Rules</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.bank-reconciliation.index') }}" class="text-content-secondary hover:text-content transition-colors">Reconciliation</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Rules</span>
        </div>
    </x-slot>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">Reconciliation Rules</h1>
    </div>

    {{-- Add Rule Form --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-6 mb-6">
        <h2 class="text-sm font-semibold text-content mb-4">Add New Rule</h2>
        <form method="POST" action="{{ route('admin.bank-reconciliation.store-rule') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-content mb-1">Rule Name</label>
                    <input type="text" name="name" id="name" required
                           value="{{ old('name') }}"
                           class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                           placeholder="e.g., Meesho Settlement Matcher">
                    @error('name')
                        <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="match_field" class="block text-sm font-medium text-content mb-1">Match Field</label>
                    <select name="match_field" id="match_field" required
                            class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        @foreach (\App\Models\ReconciliationRule::MATCH_FIELD_LABELS as $val => $label)
                            <option value="{{ $val }}" @selected(old('match_field') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="match_pattern" class="block text-sm font-medium text-content mb-1">Match Pattern (regex or keyword)</label>
                    <input type="text" name="match_pattern" id="match_pattern"
                           value="{{ old('match_pattern') }}"
                           class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                           placeholder="e.g., MEESHO|meesho.*settlement">
                    @error('match_pattern')
                        <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="match_entity_type" class="block text-sm font-medium text-content mb-1">Entity Type</label>
                    <select name="match_entity_type" id="match_entity_type" required
                            class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        <option value="Settlement" @selected(old('match_entity_type') === 'Settlement')>Settlement</option>
                        <option value="Payment" @selected(old('match_entity_type') === 'Payment')>Payment</option>
                        <option value="PurchaseOrder" @selected(old('match_entity_type') === 'PurchaseOrder')>Purchase Order</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="category" class="block text-sm font-medium text-content mb-1">Category</label>
                    <select name="category" id="category" required
                            class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        @foreach (\App\Models\ReconciliationRule::CATEGORY_LABELS as $val => $label)
                            <option value="{{ $val }}" @selected(old('category') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="priority" class="block text-sm font-medium text-content mb-1">Priority</label>
                    <input type="number" name="priority" id="priority" min="0"
                           value="{{ old('priority', 0) }}"
                           class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                </div>

                <div class="flex items-end gap-4 pb-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="rounded border-surface-border text-brand-500 focus:ring-brand-400">
                        <span class="text-sm text-content">Active</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="auto_match" value="1"
                               class="rounded border-surface-border text-brand-500 focus:ring-brand-400">
                        <span class="text-sm text-content">Auto-match</span>
                    </label>
                </div>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-content mb-1">Description (optional)</label>
                <input type="text" name="description" id="description"
                       value="{{ old('description') }}"
                       class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400"
                       placeholder="Describe what this rule matches...">
            </div>

            <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-5 py-2 text-sm font-semibold transition-colors inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Rule
            </button>
        </form>
    </div>

    {{-- Rules Table --}}
    <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
        <table class="min-w-full divide-y divide-surface-border">
            <thead class="bg-surface-secondary">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Priority</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Match Field</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Pattern</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Entity</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Category</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Active</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Auto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-border-light">
                @forelse ($rules as $rule)
                    <tr class="hover:bg-surface-secondary/50 transition">
                        <td class="px-4 py-3 text-center text-sm font-medium text-content">{{ $rule->priority }}</td>
                        <td class="px-4 py-3 text-sm">
                            <p class="font-medium text-content">{{ $rule->name }}</p>
                            @if($rule->description)
                                <p class="text-xs text-content-muted mt-0.5">{{ $rule->description }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-content">{{ $rule->match_field_label }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-xs text-content-secondary">{{ $rule->match_pattern ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-content">{{ $rule->match_entity_type }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-brand-50 text-brand-600">
                                {{ $rule->category_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($rule->is_active)
                                <svg class="inline w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            @else
                                <svg class="inline w-4 h-4 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($rule->auto_match)
                                <svg class="inline w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            @else
                                <svg class="inline w-4 h-4 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-sm text-content-muted">
                            No reconciliation rules defined yet. Add a rule above to automate matching.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
