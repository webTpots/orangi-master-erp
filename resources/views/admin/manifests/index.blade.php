<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-display font-semibold text-content">Manifests</h2>
            <a href="{{ route('admin.manifests.upload') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-brand hover:bg-brand-600 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                Upload Manifest
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

            {{-- Filters --}}
            <div class="rounded-xl border border-surface-border bg-white p-4 shadow-card">
                <form method="GET" action="{{ route('admin.manifests.index') }}" class="flex flex-wrap items-end gap-4">
                    <div class="w-44">
                        <label class="mb-1 block text-xs font-medium text-content-secondary">Marketplace</label>
                        <select name="marketplace_id" class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            <option value="">All</option>
                            @foreach ($marketplaces as $mp)
                                <option value="{{ $mp->id }}" @selected(request('marketplace_id') == $mp->id)>{{ $mp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-36">
                        <label class="mb-1 block text-xs font-medium text-content-secondary">Status</label>
                        <select name="status" class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            <option value="">All</option>
                            <option value="uploaded" @selected(request('status') === 'uploaded')>Uploaded</option>
                            <option value="processing" @selected(request('status') === 'processing')>Processing</option>
                            <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                            <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">Filter</button>
                        <a href="{{ route('admin.manifests.index') }}" class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">Clear</a>
                    </div>
                </form>
            </div>

            {{-- Manifests Table --}}
            <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
                <table class="min-w-full divide-y divide-surface-border">
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">File Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Marketplace</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Manifest Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Supplier</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Imported</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border-light">
                        @forelse ($manifests as $manifest)
                            <tr class="hover:bg-surface-secondary/50 transition">
                                <td class="px-4 py-3 text-sm text-content-muted">{{ $manifest->id }}</td>
                                <td class="px-4 py-3 text-sm text-content font-medium">{{ \Illuminate\Support\Str::limit($manifest->file_name, 40) }}</td>
                                <td class="px-4 py-3 text-sm text-content">{{ $manifest->marketplace->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-content">{{ $manifest->manifest_date?->format('d M Y') ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-content-secondary">{{ $manifest->supplier_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $statusColor = match($manifest->status) {
                                            'completed' => 'bg-success-50 text-success-500',
                                            'processing' => 'bg-warning-50 text-warning-500',
                                            'failed' => 'bg-danger-50 text-danger-500',
                                            default => 'bg-neutral-50 text-neutral-500',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                                        {{ ucfirst($manifest->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-content-secondary">{{ $manifest->created_at->diffForHumans() }}</td>
                                <td class="px-4 py-3 text-right space-x-2">
                                    <a href="{{ route('admin.manifests.show', $manifest) }}" class="text-content-muted hover:text-brand-500 transition" title="View">
                                        <svg class="inline h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                    </a>
                                    <a href="{{ route('admin.manifests.reconcile', $manifest) }}" class="text-content-muted hover:text-brand-500 transition" title="Reconcile">
                                        <svg class="inline h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-sm text-content-muted">
                                    No manifests found. <a href="{{ route('admin.manifests.upload') }}" class="text-brand-500 hover:underline">Upload a manifest PDF.</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $manifests->links() }}</div>

        </div>
    </div>
</x-admin-layout>
