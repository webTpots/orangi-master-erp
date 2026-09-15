<x-admin-layout>
    <x-slot name="title">Scanning</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Scanning</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">Barcode Scanner</h1>
        <a href="{{ route('admin.scan.recent') }}"
           class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Scan History
        </a>
    </div>

    {{-- Scan Input Card --}}
    <div class="rounded-xl border border-surface-border bg-white p-6 shadow-card mb-6">
        <form method="POST" action="{{ route('admin.scan.process') }}" id="scanForm">
            @csrf

            {{-- Scan Type Selector --}}
            <div class="mb-5">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-content-secondary">Scan Action</label>
                <div class="flex flex-wrap gap-2">
                    @php
                        $scanTypes = [
                            'pack'           => ['label' => 'Pack',           'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                            'dispatch'       => ['label' => 'Dispatch',       'icon' => 'M5 13l4 4L19 7'],
                            'receive'        => ['label' => 'Receive',        'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'],
                            'return_receive' => ['label' => 'Return',         'icon' => 'M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z'],
                            'quality_check'  => ['label' => 'QC',             'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                            'shelf_assign'   => ['label' => 'Shelf',          'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'],
                        ];
                    @endphp
                    @foreach ($scanTypes as $typeKey => $typeData)
                        <label class="cursor-pointer">
                            <input type="radio" name="scan_type" value="{{ $typeKey }}" class="peer sr-only" {{ $typeKey === 'dispatch' ? 'checked' : '' }}>
                            <div class="flex items-center gap-2 rounded-lg border border-surface-border px-4 py-2.5 text-sm font-medium text-content-secondary transition
                                        peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $typeData['icon'] }}"/></svg>
                                {{ $typeData['label'] }}
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Barcode Input --}}
            <div class="mb-4">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-content-secondary">Scan Barcode / QR Code</label>
                <input type="text" name="barcode_data" id="barcodeInput"
                       placeholder="Scan or type barcode here..."
                       autocomplete="off" autofocus
                       class="w-full rounded-xl border-surface-border bg-surface text-lg text-content font-mono placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400 py-4 px-5">
            </div>

            {{-- Optional Fields Row --}}
            <div class="flex flex-wrap gap-4 mb-4">
                <div class="w-48">
                    <label class="mb-1 block text-xs font-medium text-content-secondary">Location (optional)</label>
                    <input type="text" name="location" placeholder="Zone / Rack / Bin"
                           class="w-full rounded-lg border-surface-border bg-surface text-sm text-content placeholder:text-content-muted focus:border-brand-400 focus:ring-brand-400">
                </div>
                <div class="w-36">
                    <label class="mb-1 block text-xs font-medium text-content-secondary">Scan Method</label>
                    <select name="scan_method"
                            class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                        <option value="barcode">Barcode</option>
                        <option value="qr">QR Code</option>
                        <option value="manual">Manual</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600 transition">
                Process Scan
            </button>
        </form>
    </div>

    {{-- Last Scan Result --}}
    @if (session('success'))
        <div class="rounded-xl border border-success-500/20 bg-success-50 p-4 mb-6 flex items-center gap-3">
            <svg class="w-5 h-5 text-success-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <p class="text-sm font-medium text-success-600">{{ session('success') }}</p>
        </div>
    @endif
    @if (session('warning'))
        <div class="rounded-xl border border-warning-500/20 bg-warning-50 p-4 mb-6 flex items-center gap-3">
            <svg class="w-5 h-5 text-warning-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <p class="text-sm font-medium text-warning-600">{{ session('warning') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="rounded-xl border border-danger-500/20 bg-danger-50 p-4 mb-6 flex items-center gap-3">
            <svg class="w-5 h-5 text-danger-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <p class="text-sm font-medium text-danger-600">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Recent Scans Table --}}
    <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
        <div class="px-6 py-4 border-b border-surface-border">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-content-secondary">Recent Scans</h3>
        </div>
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
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-border-light">
                @forelse ($recentScans as $scan)
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
                        <td class="px-4 py-3 text-xs text-content-muted">{{ $scan->scanned_at->format('d M, h:i A') }}</td>
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
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-sm text-content-muted">No scans recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const barcodeInput = document.getElementById('barcodeInput');

            // Auto-focus the barcode input
            barcodeInput.focus();

            // Re-focus when clicking anywhere on the page (for barcode scanner usability)
            document.addEventListener('click', function(e) {
                if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'SELECT' && e.target.tagName !== 'BUTTON' && e.target.tagName !== 'A') {
                    barcodeInput.focus();
                }
            });

            // Auto-submit on Enter (barcode scanners typically send Enter after scan)
            barcodeInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && this.value.trim().length > 0) {
                    e.preventDefault();
                    document.getElementById('scanForm').submit();
                }
            });
        });
    </script>
    @endpush
</x-admin-layout>
