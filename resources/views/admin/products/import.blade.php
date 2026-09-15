<x-admin-layout>
    <x-slot name="title">Import Products</x-slot>
    <x-slot name="header">Import Products</x-slot>

    @php $step = $step ?? 1; @endphp

    {{-- Step Indicator --}}
    <div class="flex items-center gap-2 mb-8">
        @foreach([1 => 'Upload', 2 => 'Map Columns', 3 => 'Preview', 4 => 'Complete'] as $num => $label)
            <div class="flex items-center gap-2">
                <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium
                    {{ $step >= $num ? 'bg-brand-500 text-white' : 'bg-surface-secondary text-content-muted border border-surface-border' }}">
                    @if($step > $num)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    @else
                        {{ $num }}
                    @endif
                </div>
                <span class="text-sm {{ $step >= $num ? 'text-content font-medium' : 'text-content-muted' }}">{{ $label }}</span>
            </div>
            @if($num < 4)
                <div class="flex-1 h-px {{ $step > $num ? 'bg-brand-500' : 'bg-surface-border' }} mx-2"></div>
            @endif
        @endforeach
    </div>

    {{-- Step 1: Upload --}}
    @if($step === 1)
        <div class="card p-8 max-w-2xl">
            <h3 class="text-base font-display font-semibold text-content mb-2">Upload Product File</h3>
            <p class="text-sm text-content-secondary mb-6">Upload a CSV or XLSX file with your product data. The file should include columns for design code, name, color, size, and pricing.</p>

            <form method="POST" action="{{ route('admin.products.import.process') }}" enctype="multipart/form-data">
                @csrf
                <div class="border-2 border-dashed border-surface-border rounded-lg p-8 text-center hover:border-brand-500 transition-colors">
                    <svg class="mx-auto h-12 w-12 text-content-muted mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <input type="file" name="file" accept=".csv,.txt,.xlsx" required class="input max-w-xs mx-auto">
                    <p class="mt-2 text-xs text-content-muted">CSV or XLSX, max 5MB</p>
                </div>

                <div class="mt-6">
                    <button type="submit" class="btn-primary">Upload & Parse</button>
                </div>
            </form>

            <div class="mt-8 border-t border-surface-border pt-6">
                <h4 class="text-sm font-medium text-content mb-2">Expected Columns</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach(['design_code', 'design_name', 'category', 'hsn_code', 'gst_rate', 'color', 'size', 'selling_price', 'cost_price', 'mrp'] as $col)
                        <span class="badge-neutral font-mono">{{ $col }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Step 2: Column Mapping --}}
    @if($step === 2)
        <form method="POST" action="{{ route('admin.products.import.confirm') }}">
            @csrf
            <div class="card p-6 mb-6">
                <h3 class="text-base font-display font-semibold text-content mb-4">Column Mapping</h3>
                <p class="text-sm text-content-secondary mb-4">Map your file columns to the expected fields. We auto-detected the mapping below -- adjust if needed.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($expectedColumns as $expected)
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium text-content w-32">{{ str_replace('_', ' ', ucfirst($expected)) }}</label>
                            <select name="column_map[{{ $expected }}]" class="input flex-1">
                                <option value="">-- Skip --</option>
                                @foreach($headers as $header)
                                    <option value="{{ $header }}" {{ ($columnMap[$expected] ?? '') === $header ? 'selected' : '' }}>{{ $header }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Preview --}}
            <div class="card p-6 mb-6">
                <h3 class="text-base font-display font-semibold text-content mb-4">Data Preview ({{ $totalRows }} rows total, showing first {{ count($preview) }})</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-surface-border">
                                @foreach($headers as $header)
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-content-secondary uppercase whitespace-nowrap">{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preview as $row)
                                <tr class="border-b border-surface-border-light">
                                    @foreach($headers as $header)
                                        <td class="px-3 py-2 whitespace-nowrap">{{ $row[$header] ?? '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Confirm & Import</button>
                <a href="{{ route('admin.products.import') }}" class="btn-ghost">Start Over</a>
            </div>
        </form>
    @endif

    {{-- Step 4: Complete --}}
    @if($step === 4)
        <div class="card p-8 max-w-2xl">
            @if(($result['created'] ?? 0) > 0)
                <div class="text-center mb-6">
                    <div class="w-16 h-16 rounded-full bg-success-50 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h3 class="text-lg font-display font-semibold text-content">Import Complete</h3>
                    <p class="text-sm text-content-secondary mt-1">{{ $result['created'] }} variants/SKUs created successfully.</p>
                </div>
            @else
                <div class="text-center mb-6">
                    <h3 class="text-lg font-display font-semibold text-content">Import Finished</h3>
                    <p class="text-sm text-content-secondary mt-1">No new records were created.</p>
                </div>
            @endif

            @if(!empty($result['errors']))
                <div class="bg-danger-50 border border-danger-border rounded-lg p-4 mb-6">
                    <h4 class="text-sm font-medium text-danger-500 mb-2">Errors ({{ count($result['errors']) }})</h4>
                    <ul class="list-disc list-inside text-xs text-danger-500 space-y-1 max-h-40 overflow-y-auto">
                        @foreach($result['errors'] as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex gap-3 justify-center">
                <a href="{{ route('admin.designs.index') }}" class="btn-primary">View Designs</a>
                <a href="{{ route('admin.products.import') }}" class="btn-secondary">Import More</a>
            </div>
        </div>
    @endif
</x-admin-layout>
