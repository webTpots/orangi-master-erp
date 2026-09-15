<x-admin-layout>
    <x-slot name="title">Designs</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.designs.index') }}" class="text-content-secondary hover:text-content transition-colors">Products</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Designs</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold text-content">Designs</h1>
        <a href="{{ route('admin.designs.create') }}" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold inline-flex items-center gap-1.5 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Design
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-4 mb-6">
        <form method="GET" action="{{ route('admin.designs.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or code..."
                       class="input">
            </div>
            <div class="w-40">
                <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Category</label>
                <select name="category" class="input">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Status</label>
                <select name="status" class="input">
                    <option value="">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold transition-colors">Filter</button>
                <a href="{{ route('admin.designs.index') }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold transition-colors">Clear</a>
            </div>
        </form>
    </div>

    {{-- Data Table --}}
    <div class="bg-white rounded-xl border border-surface-border-light shadow-card overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-surface-border-light bg-surface-secondary/50">
                    <th class="px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Code</th>
                    <th class="px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Name</th>
                    <th class="px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Category</th>
                    <th class="px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">HSN</th>
                    <th class="px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">GST</th>
                    <th class="px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide text-center">Products</th>
                    <th class="px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide text-center">Variants</th>
                    <th class="px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 text-xs font-semibold text-content-secondary uppercase tracking-wide text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($designs as $design)
                    <tr class="border-b border-surface-border-light hover:bg-surface-secondary/50 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs font-medium text-brand-600">{{ $design->code }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-content">{{ $design->name }}</td>
                        <td class="px-4 py-3 text-sm text-content-secondary">{{ $design->category ?? '-' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-content-secondary">{{ $design->hsn_code ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-content-secondary">{{ $design->gst_rate }}%</td>
                        <td class="px-4 py-3 text-sm text-content-secondary text-center">{{ $design->products_count }}</td>
                        <td class="px-4 py-3 text-sm text-content-secondary text-center">{{ $design->products->sum('variants_count') }}</td>
                        <td class="px-4 py-3">
                            @if($design->status === 'active')
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-success-50 text-success-500">Active</span>
                            @else
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-neutral-50 text-neutral-500">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.designs.show', $design) }}" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-content-muted hover:text-brand-500 hover:bg-brand-50 transition-colors" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="{{ route('admin.designs.edit', $design) }}" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-content-muted hover:text-brand-500 hover:bg-brand-50 transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('admin.designs.destroy', $design) }}" onsubmit="return confirm('Delete this design?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-content-muted hover:text-danger-500 hover:bg-danger-50 transition-colors" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-12 h-12 rounded-full bg-surface-secondary flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <p class="text-sm text-content-secondary">No designs found</p>
                                <a href="{{ route('admin.designs.create') }}" class="text-sm text-brand-500 hover:underline mt-1">Create your first design</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($designs->hasPages())
        <div class="mt-4">
            {{ $designs->links() }}
        </div>
    @endif
</x-admin-layout>
