<x-admin-layout>
    <x-slot name="title">GST Summary</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.gst.index') }}" class="text-content-secondary hover:text-content transition-colors">GST</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">HSN Summary</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">HSN-wise GST Summary</h1>
        <div class="flex items-center gap-2">
            <button type="button" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors" disabled title="Export coming soon">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export
            </button>
        </div>
    </div>

    {{-- Period Selector --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-4 mb-6">
        <form method="GET" action="{{ route('admin.gst.summary') }}" class="flex items-end gap-4">
            <div class="w-48">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Period</label>
                <input type="month" name="period" value="{{ $period }}"
                       class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
            </div>
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">View</button>
        </form>
    </div>

    {{-- Summary Table --}}
    <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
        <div class="px-4 py-3 border-b border-surface-border bg-surface-secondary">
            <h2 class="text-sm font-semibold text-content">
                GST Summary for {{ \Carbon\Carbon::parse($period . '-01')->format('F Y') }}
                <span class="text-content-muted font-normal">(for GSTR-1 filing)</span>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-surface-border">
                <thead class="bg-surface-secondary/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">HSN Code</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Entries</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Taxable Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">CGST</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">SGST</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">IGST</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Total GST</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Total Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border-light">
                    @forelse ($summary as $row)
                        <tr class="hover:bg-surface-secondary/50 transition">
                            <td class="px-4 py-3 text-sm font-mono font-medium text-content">{{ $row['hsn_code'] }}</td>
                            <td class="px-4 py-3 text-center text-sm text-content">{{ $row['total_entries'] }}</td>
                            <td class="px-4 py-3 text-right text-sm text-content">{{ Number::currency($row['total_taxable'], 'INR') }}</td>
                            <td class="px-4 py-3 text-right text-sm text-content-secondary">{{ Number::currency($row['total_cgst'], 'INR') }}</td>
                            <td class="px-4 py-3 text-right text-sm text-content-secondary">{{ Number::currency($row['total_sgst'], 'INR') }}</td>
                            <td class="px-4 py-3 text-right text-sm text-content-secondary">{{ Number::currency($row['total_igst'], 'INR') }}</td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-content">{{ Number::currency($row['total_gst'], 'INR') }}</td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-content">{{ Number::currency($row['total_amount'], 'INR') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-content-muted">
                                No GST entries found for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if (!empty($summary))
                    <tfoot class="bg-surface-secondary border-t-2 border-surface-border">
                        <tr>
                            <td class="px-4 py-3 text-sm font-bold text-content">Grand Total</td>
                            <td class="px-4 py-3 text-center text-sm font-semibold text-content">{{ array_sum(array_column($summary, 'total_entries')) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-content">{{ Number::currency($grandTotals['taxable'], 'INR') }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-content">{{ Number::currency($grandTotals['cgst'], 'INR') }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-content">{{ Number::currency($grandTotals['sgst'], 'INR') }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-content">{{ Number::currency($grandTotals['igst'], 'INR') }}</td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-content">{{ Number::currency($grandTotals['gst'], 'INR') }}</td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-content">{{ Number::currency($grandTotals['amount'], 'INR') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-admin-layout>
