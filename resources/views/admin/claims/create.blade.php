<x-admin-layout>
    <x-slot name="title">New Claim</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.claims.index') }}" class="text-content-secondary hover:text-content transition-colors">Claims</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">New Claim</span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <h1 class="font-display text-xl font-bold text-content mb-6">Create New Claim</h1>

            <form method="POST" action="{{ route('admin.claims.store') }}" class="space-y-6">
                @csrf

                {{-- Claim Type & Against --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Claim Information</h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-content">Claim Type</label>
                            <select name="claim_type" required
                                    class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                <option value="">-- Select Type --</option>
                                @foreach (\App\Models\Claim::TYPE_LABELS as $val => $label)
                                    <option value="{{ $val }}" @selected(old('claim_type') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('claim_type') <p class="text-xs text-danger-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-content">Claim Against</label>
                            <select name="claim_against" required
                                    class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                <option value="">-- Select --</option>
                                @foreach (\App\Models\Claim::AGAINST_LABELS as $val => $label)
                                    <option value="{{ $val }}" @selected(old('claim_against') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('claim_against') <p class="text-xs text-danger-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Link to Return / Order --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Link to Return / Order</h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-content">Link to Return</label>
                            <select name="return_id"
                                    class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                <option value="">-- None --</option>
                                @foreach ($returns as $r)
                                    <option value="{{ $r->id }}" @selected(old('return_id', $returnOrder?->id) == $r->id)>
                                        RTN-{{ str_pad($r->id, 5, '0', STR_PAD_LEFT) }} ({{ $r->type_label }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-content">Link to Order</label>
                            <select name="order_id"
                                    class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                                <option value="">-- None --</option>
                                @foreach ($orders as $o)
                                    <option value="{{ $o->id }}" @selected(old('order_id', $returnOrder?->order_id) == $o->id)>
                                        {{ $o->marketplace_order_id }} ({{ $o->order_date->format('d M') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Financial --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Financial Details</h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-content">Claimed Amount (Rs.)</label>
                            <input type="number" name="claimed_amount" step="0.01" min="0.01" value="{{ old('claimed_amount') }}" required
                                   placeholder="0.00"
                                   class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">
                            @error('claimed_amount') <p class="text-xs text-danger-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-content">Reference Number</label>
                            <input type="text" name="reference_number" value="{{ old('reference_number') }}"
                                   placeholder="External claim reference..."
                                   class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-content">Notes</label>
                            <textarea name="notes" rows="3"
                                      class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400"
                                      placeholder="Additional details about this claim...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Evidence Upload (UI only) --}}
                <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary mb-4">Evidence</h3>
                    <div class="border-2 border-dashed border-surface-border rounded-lg p-8 text-center">
                        <svg class="mx-auto h-10 w-10 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        <p class="mt-2 text-sm text-content-muted">Drag and drop evidence files here, or click to browse</p>
                        <p class="text-xs text-content-muted mt-1">PDF, PNG, JPG up to 10MB each</p>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 transition">
                        Create Claim
                    </button>
                    <a href="{{ route('admin.claims.index') }}" class="rounded-lg border border-surface-border px-6 py-2.5 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
