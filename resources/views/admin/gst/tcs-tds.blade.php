<x-admin-layout>
    <x-slot name="title">TCS / TDS Report</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.gst.index') }}" class="text-content-secondary hover:text-content transition-colors">GST</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">TCS / TDS</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="mb-6">
        <h1 class="font-display text-xl font-bold text-content">TCS / TDS Report</h1>
        <p class="text-sm text-content-secondary mt-1">Marketplace-wise TCS and TDS tracking for {{ $financialYear }}</p>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-surface-border-light p-4 mb-6">
        <form method="GET" action="{{ route('admin.gst.tcs-tds') }}" class="flex flex-wrap items-end gap-4">
            <div class="w-36">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Financial Year</label>
                <select name="financial_year"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    @for ($y = now()->year; $y >= now()->year - 3; $y--)
                        @php $fy = $y . '-' . substr($y + 1, 2); @endphp
                        <option value="{{ $fy }}" @selected($financialYear === $fy)>{{ $fy }}</option>
                    @endfor
                </select>
            </div>
            <div class="w-28">
                <label class="mb-1 block text-xs font-medium text-content-secondary">Quarter</label>
                <select name="quarter"
                        class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                    <option value="">All</option>
                    <option value="Q1" @selected($quarter === 'Q1')>Q1 (Apr-Jun)</option>
                    <option value="Q2" @selected($quarter === 'Q2')>Q2 (Jul-Sep)</option>
                    <option value="Q3" @selected($quarter === 'Q3')>Q3 (Oct-Dec)</option>
                    <option value="Q4" @selected($quarter === 'Q4')>Q4 (Jan-Mar)</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">View</button>
                <a href="{{ route('admin.gst.tcs-tds') }}" class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">Clear</a>
            </div>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Total TCS ({{ $financialYear }})</p>
            <p class="mt-1 text-2xl font-semibold text-brand-500">{{ Number::currency($totals['tcs_amount'], 'INR') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-surface-border-light p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-content-secondary">Total TDS ({{ $financialYear }})</p>
            <p class="mt-1 text-2xl font-semibold text-brand-600">{{ Number::currency($totals['tds_amount'], 'INR') }}</p>
        </div>
    </div>

    {{-- TCS Records --}}
    <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card mb-6">
        <div class="px-4 py-3 border-b border-surface-border bg-surface-secondary">
            <h2 class="text-sm font-semibold text-content">TCS Records (Tax Collected at Source)</h2>
        </div>
        <table class="min-w-full divide-y divide-surface-border">
            <thead class="bg-surface-secondary/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Marketplace</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Section</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Quarter</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Gross Amount</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Rate</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">TCS Amount</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Certificate</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-border-light">
                @forelse ($tcsRecords as $record)
                    @php
                        $statusColors = [
                            'computed'             => 'bg-warning-50 text-warning-600',
                            'filed'                => 'bg-brand-50 text-brand-600',
                            'certificate_received' => 'bg-success-50 text-success-600',
                        ];
                    @endphp
                    <tr class="hover:bg-surface-secondary/50 transition">
                        <td class="px-4 py-3 text-sm text-content">{{ $record->marketplaceAccount?->account_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-center text-xs font-mono text-content">{{ $record->section_code }}</td>
                        <td class="px-4 py-3 text-center text-sm text-content-secondary">{{ $record->quarter }}</td>
                        <td class="px-4 py-3 text-right text-sm text-content">{{ Number::currency($record->gross_amount, 'INR') }}</td>
                        <td class="px-4 py-3 text-center text-sm text-content-secondary">{{ $record->rate }}%</td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-content">{{ Number::currency($record->amount, 'INR') }}</td>
                        <td class="px-4 py-3 text-center text-xs text-content-muted">{{ $record->certificate_number ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$record->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                {{ $record->status_label }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-content-muted">No TCS records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- TDS Records --}}
    <div class="overflow-hidden rounded-xl border border-surface-border bg-white shadow-card">
        <div class="px-4 py-3 border-b border-surface-border bg-surface-secondary">
            <h2 class="text-sm font-semibold text-content">TDS Records (Tax Deducted at Source)</h2>
        </div>
        <table class="min-w-full divide-y divide-surface-border">
            <thead class="bg-surface-secondary/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-content-secondary">Marketplace</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Section</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Quarter</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">Gross Amount</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Rate</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-content-secondary">TDS Amount</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Certificate</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-content-secondary">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-border-light">
                @forelse ($tdsRecords as $record)
                    @php
                        $statusColors = [
                            'computed'             => 'bg-warning-50 text-warning-600',
                            'filed'                => 'bg-brand-50 text-brand-600',
                            'certificate_received' => 'bg-success-50 text-success-600',
                        ];
                    @endphp
                    <tr class="hover:bg-surface-secondary/50 transition">
                        <td class="px-4 py-3 text-sm text-content">{{ $record->marketplaceAccount?->account_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-center text-xs font-mono text-content">{{ $record->section_code }}</td>
                        <td class="px-4 py-3 text-center text-sm text-content-secondary">{{ $record->quarter }}</td>
                        <td class="px-4 py-3 text-right text-sm text-content">{{ Number::currency($record->gross_amount, 'INR') }}</td>
                        <td class="px-4 py-3 text-center text-sm text-content-secondary">{{ $record->rate }}%</td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-content">{{ Number::currency($record->amount, 'INR') }}</td>
                        <td class="px-4 py-3 text-center text-xs text-content-muted">{{ $record->certificate_number ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$record->status] ?? 'bg-neutral-50 text-neutral-500' }}">
                                {{ $record->status_label }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-content-muted">No TDS records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
