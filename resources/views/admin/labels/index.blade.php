<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-display font-semibold text-content">Labels</h2>
            <a href="{{ route('admin.labels.upload') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-brand hover:bg-brand-600 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                Upload PDF
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-lg border border-success-500/20 bg-success-50 p-4 text-sm text-success-600">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-lg border border-danger-border bg-danger-50 p-4 text-sm text-danger-500">{{ session('error') }}</div>
            @endif

            {{-- Stats --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-surface-border bg-white p-4 shadow-card">
                    <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Total Labels</p>
                    <p class="mt-1 text-2xl font-semibold text-content">{{ $stats['total'] }}</p>
                </div>
                <div class="rounded-xl border border-surface-border bg-white p-4 shadow-card">
                    <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Parsed</p>
                    <p class="mt-1 text-2xl font-semibold text-warning-500">{{ $stats['parsed'] }}</p>
                </div>
                <div class="rounded-xl border border-surface-border bg-white p-4 shadow-card">
                    <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Linked</p>
                    <p class="mt-1 text-2xl font-semibold text-success-500">{{ $stats['linked'] }}</p>
                </div>
                <div class="rounded-xl border border-surface-border bg-white p-4 shadow-card">
                    <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Errors</p>
                    <p class="mt-1 text-2xl font-semibold text-danger-500">{{ $stats['errors'] }}</p>
                </div>
            </div>

            {{-- Filters --}}
            <div class="rounded-xl border border-surface-border bg-white p-4 shadow-card">
                <form method="GET" action="{{ route('admin.labels.index') }}" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[180px]">
                        <label class="mb-1 block text-xs font-medium text-content-secondary">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="AWB, sub-order, customer, SKU..."
                               class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">
                    </div>
                    <div class="w-40">
                        <label class="mb-1 block text-xs font-medium text-content-secondary">Status</label>
                        <select name="status" class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            <option value="">All</option>
                            <option value="parsed" @selected(request('status') === 'parsed')>Parsed</option>
                            <option value="linked" @selected(request('status') === 'linked')>Linked</option>
                            <option value="error" @selected(request('status') === 'error')>Error</option>
                        </select>
                    </div>
                    <div class="w-40">
                        <label class="mb-1 block text-xs font-medium text-content-secondary">Marketplace</label>
                        <select name="marketplace_id" class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            <option value="">All</option>
                            @foreach ($marketplaces as $mp)
                                <option value="{{ $mp->id }}" @selected(request('marketplace_id') == $mp->id)>{{ $mp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">Filter</button>
                        <a href="{{ route('admin.labels.index') }}" class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">Clear</a>
                    </div>
                </form>
            </div>

            {{-- Labels Table --}}
            <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
                <table class="min-w-full divide-y divide-surface-border">
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">AWB</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Sub Order #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">SKU</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Customer</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Courier</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Payment</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Amount</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border-light">
                        @forelse ($labels as $label)
                            <tr class="hover:bg-surface-secondary/50 transition">
                                <td class="px-4 py-3 text-xs font-mono text-content">{{ $label->awb_number ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs font-mono text-content">{{ $label->sub_order_number ? \Illuminate\Support\Str::limit($label->sub_order_number, 18) : '-' }}</td>
                                <td class="px-4 py-3 text-sm text-content">{{ $label->sku ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-content">{{ $label->customer_name ? \Illuminate\Support\Str::limit($label->customer_name, 20) : '-' }}</td>
                                <td class="px-4 py-3 text-sm text-content-secondary">{{ $label->courier_partner ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if ($label->payment_type)
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $label->payment_type === 'cod' ? 'bg-warning-50 text-warning-600' : 'bg-success-50 text-success-500' }}">
                                            {{ strtoupper($label->payment_type) }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-medium text-content">{{ $label->invoice_amount ? number_format($label->invoice_amount, 2) : '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $label->status === 'linked' ? 'bg-success-50 text-success-500' : ($label->status === 'error' ? 'bg-danger-50 text-danger-500' : 'bg-warning-50 text-warning-500') }}">
                                        {{ ucfirst($label->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.labels.show', $label) }}" class="text-content-muted hover:text-brand-500 transition" title="View">
                                        <svg class="inline h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center text-sm text-content-muted">
                                    No labels found. <a href="{{ route('admin.labels.upload') }}" class="text-brand-500 hover:underline">Upload a label PDF.</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $labels->links() }}</div>

            {{-- Recent Files --}}
            @if ($recentFiles->isNotEmpty())
                <div class="rounded-xl border border-surface-border bg-white shadow-card">
                    <div class="px-6 py-4 border-b border-surface-border">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Recent Imports</h3>
                    </div>
                    <table class="min-w-full divide-y divide-surface-border">
                        <thead class="bg-surface-secondary">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">File</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Pages</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Parsed</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Linked</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Errors</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Imported</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border-light">
                            @foreach ($recentFiles as $file)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-content">{{ \Illuminate\Support\Str::limit($file->file_name, 40) }}</td>
                                    <td class="px-4 py-3 text-center text-sm text-content">{{ $file->total_pages }}</td>
                                    <td class="px-4 py-3 text-center text-sm text-content">{{ $file->parsed_labels }}</td>
                                    <td class="px-4 py-3 text-center text-sm text-success-500 font-medium">{{ $file->linked_labels }}</td>
                                    <td class="px-4 py-3 text-center text-sm {{ $file->error_labels > 0 ? 'text-danger-500' : 'text-content-muted' }}">{{ $file->error_labels }}</td>
                                    <td class="px-4 py-3 text-sm text-content-secondary">{{ $file->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>
</x-admin-layout>
