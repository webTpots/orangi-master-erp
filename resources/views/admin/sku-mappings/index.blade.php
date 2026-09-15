<x-admin-layout>
    <x-slot name="title">SKU Mappings</x-slot>
    <x-slot name="header">SKU Mappings</x-slot>

    {{-- Tabs --}}
    <div class="flex items-center gap-1 mb-6 border-b border-surface-border">
        @foreach(['all' => 'All', 'mapped' => 'Mapped', 'suggested' => 'Suggested', 'rejected' => 'Rejected'] as $key => $label)
            <a href="{{ route('admin.sku-mappings.index', array_merge(request()->except('tab', 'page'), ['tab' => $key])) }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors
                      {{ $tab === $key ? 'border-brand-500 text-brand-600' : 'border-transparent text-content-secondary hover:text-content hover:border-surface-border' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <form method="GET" action="{{ route('admin.sku-mappings.index') }}" class="flex flex-wrap gap-3 flex-1">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search external SKU or internal SKU..." class="input flex-1 max-w-sm">

            <select name="marketplace_account_id" class="input w-auto">
                <option value="">All Marketplaces</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" {{ request('marketplace_account_id') == $account->id ? 'selected' : '' }}>
                        {{ $account->marketplace->name ?? $account->account_name }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn-secondary btn-sm">Filter</button>
        </form>

        {{-- Bulk Approve (for suggested tab) --}}
        @if($tab === 'suggested')
            <form method="POST" action="{{ route('admin.sku-mappings.index') }}" id="bulk-approve-form">
                @csrf
                <button type="button" onclick="bulkApprove()" class="btn-primary btn-sm">Bulk Approve Selected</button>
            </form>
        @endif
    </div>

    {{-- Data Table --}}
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    @if($tab === 'suggested')
                        <th class="w-8"><input type="checkbox" id="select-all" class="rounded border-surface-border text-brand-500"></th>
                    @endif
                    <th>External SKU</th>
                    <th>Marketplace</th>
                    <th>Internal SKU</th>
                    <th>Design</th>
                    <th class="text-center">Confidence</th>
                    <th>Mapped By</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mappings as $mapping)
                    <tr>
                        @if($tab === 'suggested')
                            <td><input type="checkbox" name="mapping_ids[]" value="{{ $mapping->id }}" class="mapping-checkbox rounded border-surface-border text-brand-500"></td>
                        @endif
                        <td class="font-mono text-xs font-medium">{{ $mapping->marketplace_sku }}</td>
                        <td class="text-content-secondary">{{ $mapping->marketplaceAccount?->marketplace?->name ?? '-' }}</td>
                        <td>
                            <a href="{{ route('admin.skus.show', $mapping->sku) }}" class="font-mono text-xs text-brand-600 hover:underline">
                                {{ $mapping->sku->sku_code }}
                            </a>
                        </td>
                        <td class="text-content-secondary">{{ $mapping->sku->variant?->product?->design?->name ?? '-' }}</td>
                        <td class="text-center">
                            @php $score = $mapping->confidence_score; @endphp
                            <span class="badge-{{ $score >= 0.9 ? 'success' : ($score >= 0.7 ? 'warning' : 'danger') }}">
                                {{ number_format($score * 100) }}%
                            </span>
                        </td>
                        <td class="text-content-secondary text-sm">{{ $mapping->mapper?->name ?? 'System' }}</td>
                        <td>
                            @if($mapping->status === 'active')
                                <span class="badge-success">Active</span>
                            @elseif($mapping->status === 'suggested')
                                <span class="badge-warning">Suggested</span>
                            @else
                                <span class="badge-neutral">{{ ucfirst($mapping->status) }}</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($mapping->status === 'suggested')
                                    <form method="POST" action="{{ route('admin.sku-mappings.approve', $mapping) }}">
                                        @csrf
                                        <button type="submit" class="btn-sm text-xs text-success-500 hover:bg-success-50 px-2 py-1 rounded">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.sku-mappings.reject', $mapping) }}">
                                        @csrf
                                        <button type="submit" class="btn-sm text-xs text-danger-500 hover:bg-danger-50 px-2 py-1 rounded">Reject</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.sku-mappings.destroy', $mapping) }}" onsubmit="return confirm('Delete this mapping?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-sm text-xs text-content-muted hover:text-danger-500 px-2 py-1 rounded">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $tab === 'suggested' ? 10 : 9 }}" class="text-center py-8 text-content-secondary">
                            No mappings found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $mappings->links() }}
    </div>

    {{-- Create Mapping Form --}}
    <div class="mt-8 card p-6" x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center gap-2 text-sm font-medium text-content hover:text-brand-500">
            <svg class="w-4 h-4" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            Add Manual Mapping
        </button>
        <form method="POST" action="{{ route('admin.sku-mappings.store') }}" x-show="open" x-collapse class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-content-secondary mb-1">External SKU *</label>
                <input type="text" name="marketplace_sku" required class="input" placeholder="e.g. SKD-2pocket-Black">
            </div>
            <div>
                <label class="block text-xs font-medium text-content-secondary mb-1">Internal SKU ID *</label>
                <input type="number" name="sku_id" required class="input" placeholder="Internal SKU ID">
            </div>
            <div>
                <label class="block text-xs font-medium text-content-secondary mb-1">Marketplace Account</label>
                <select name="marketplace_account_id" class="input">
                    <option value="">-- Select --</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->marketplace->name ?? $account->account_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3">
                <button type="submit" class="btn-primary btn-sm">Create Mapping</button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('select-all')?.addEventListener('change', function() {
            document.querySelectorAll('.mapping-checkbox').forEach(cb => cb.checked = this.checked);
        });

        function bulkApprove() {
            const checked = document.querySelectorAll('.mapping-checkbox:checked');
            if (checked.length === 0) {
                alert('Select at least one mapping to approve.');
                return;
            }
            if (!confirm(`Approve ${checked.length} mapping(s)?`)) return;
            // Submit each approval via forms
            checked.forEach(cb => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/sku-mappings/${cb.value}/approve`;
                form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">`;
                document.body.appendChild(form);
                form.submit();
            });
        }
    </script>
</x-admin-layout>
