<x-admin-layout>
    <x-slot name="title">Scan History</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.scan.index') }}" class="text-content-secondary hover:text-content transition-colors">Scanning</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">History</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">Scan History</h1>
        <a href="{{ route('admin.scan.index') }}"
           class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Scan
        </a>
    </div>

    {{-- Scans Table --}}
    <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
        <table class="min-w-full divide-y divide-surface-border">
            <thead class="bg-surface-secondary">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Time</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Barcode</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Action</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Method</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Identified As</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">User</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Location</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-border-light">
                @forelse ($scans as $scan)
                    @php
                        $actionColors = [
                            'pack'           => 'bg-brand-50 text-brand-600',
                            'dispatch'       => 'bg-success-50 text-success-600',
                            'receive'        => 'bg-brand-50 text-brand-500',
                            'return_receive' => 'bg-danger-50 text-danger-500',
                            'quality_check'  => 'bg-warning-50 text-warning-600',
                            'shelf_assign'   => 'bg-neutral-50 text-neutral-600',
                        ];
                    @endphp
                    <tr class="hover:bg-surface-secondary/50 transition">
                        <td class="px-4 py-3 text-xs text-content-muted">{{ $scan->scanned_at->format('d M Y, h:i A') }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-content">{{ \Illuminate\Support\Str::limit($scan->barcode_data, 25) }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $actionColors[$scan->scan_type] ?? 'bg-neutral-50 text-neutral-500' }}">
                                {{ $scan->scan_type_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-content-secondary capitalize">{{ $scan->scan_method }}</td>
                        <td class="px-4 py-3 text-sm text-content-secondary">
                            @if ($scan->scannable)
                                {{ class_basename($scan->scannable_type) }} #{{ $scan->scannable_id }}
                            @else
                                <span class="text-content-muted">Unknown</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-content-secondary">{{ $scan->user?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-content-muted">{{ $scan->location ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-content-muted">{{ \Illuminate\Support\Str::limit($scan->notes, 30) ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-sm text-content-muted">No scan history found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $scans->links() }}
    </div>
</x-admin-layout>
