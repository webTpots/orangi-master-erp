<x-admin-layout>
    <x-slot name="title">GST Entries</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">GST Entries</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">GST Entries</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.gst.summary') }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                HSN Summary
            </a>
            <a href="{{ route('admin.gst.tcs-tds') }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
                TCS / TDS
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-4 mb-6">
        <form method="GET" action="{{ route('admin.gst.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="w-36">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Type</label>
                <select name="gst_type"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    <option value="">All</option>
                    @foreach (\App\Models\GstEntry::GST_TYPE_LABELS as $val => $label)
                        <option value="{{ $val }}" @selected(request('gst_type') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Period</label>
                <input type="month" name="period" value="{{ request('period') }}"
                       class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
            </div>
            <div class="w-32">
                <label class="mb-1 block text-xs font-medium text-content-secondary">HSN Code</label>
                <select name="hsn_code"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    <option value="">All</option>
                    @foreach ($hsnCodes as $hsn)
                        <option value="{{ $hsn }}" @selected(request('hsn_code') === $hsn)>{{ $hsn }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-32">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Status</label>
                <select name="status"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    <option value="">All</option>
                    @foreach (\App\Models\GstEntry::STATUS_LABELS as $val => $label)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">Filter</button>
                <a href="{{ route('admin.gst.index') }}" class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">Clear</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-surface-border">
                <thead class="bg-surface-secondary">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Invoice #</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Date</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Type</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">HSN</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Taxable</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">CGST</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">SGST</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">IGST</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Total</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border-light">
                    @forelse ($entries as $entry)
                        @php
                            $statusColors = [
                                'draft'     => 'bg-warning-50 text-warning-600',
                                'filed'     => 'bg-success-50 text-success-600',
                                'amended'   => 'bg-brand-50 text-brand-600',
                                'cancelled' => 'bg-danger-50 text-danger-500',
                            ];
                            $typeColors = [
                                'sale'        => 'bg-success-50 text-success-600',
                                'purchase'    => 'bg-brand-50 text-brand-600',
                                'credit_note' => 'bg-danger-50 text-danger-600',
                                'debit_note'  => 'bg-warning-50 text-warning-600',
                            ];
                        @endphp
                        <tr class="hover:bg-surface-secondary/50 transition">
                            <td class="px-3 py-3 text-sm font-mono text-content">{{ $entry->invoice_number ?? '-' }}</td>
                            <td class="px-3 py-3 text-sm text-content-secondary">{{ $entry->invoice_date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $typeColors[$entry->gst_type] ?? 'bg-neutral-50 text-neutral-500' }}">
                                    {{ $entry->gst_type_label }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center text-xs font-mono text-content">{{ $entry->hsn_code }}</td>
                            <td class="px-3 py-3 text-right text-sm text-content">{{ Number::currency($entry->taxable_amount, 'INR') }}</td>
                            <td class="px-3 py-3 text-right text-sm text-content-secondary">{{ $entry->cgst_amount > 0 ? Number::currency($entry->cgst_amount, 'INR') : '-' }}</td>
                            <td class="px-3 py-3 text-right text-sm text-content-secondary">{{ $entry->sgst_amount > 0 ? Number::currency($entry->sgst_amount, 'INR') : '-' }}</td>
                            <td class="px-3 py-3 text-right text-sm text-content-secondary">{{ $entry->igst_amount > 0 ? Number::currency($entry->igst_amount, 'INR') : '-' }}</td>
                            <td class="px-3 py-3 text-right text-sm font-medium text-content">{{ Number::currency($entry->total_amount, 'INR') }}</td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$entry->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                    {{ $entry->status_label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-sm text-content-muted">
                                No GST entries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($entries->isNotEmpty())
                    <tfoot class="bg-surface-secondary border-t-2 border-surface-border">
                        <tr>
                            <td colspan="4" class="px-3 py-3 text-sm font-semibold text-content">Totals</td>
                            <td class="px-3 py-3 text-right text-sm font-semibold text-content">{{ Number::currency($totals['taxable'], 'INR') }}</td>
                            <td class="px-3 py-3 text-right text-sm font-semibold text-content">{{ Number::currency($totals['cgst'], 'INR') }}</td>
                            <td class="px-3 py-3 text-right text-sm font-semibold text-content">{{ Number::currency($totals['sgst'], 'INR') }}</td>
                            <td class="px-3 py-3 text-right text-sm font-semibold text-content">{{ Number::currency($totals['igst'], 'INR') }}</td>
                            <td class="px-3 py-3 text-right text-sm font-bold text-content">{{ Number::currency($totals['total'], 'INR') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $entries->links() }}
    </div>
</x-admin-layout>
