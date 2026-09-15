<x-admin-layout>
    <x-slot name="title">Edit {{ $design->name }}</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Dashboard</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.designs.index') }}" class="text-content-secondary hover:text-content transition-colors">Designs</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.designs.show', $design) }}" class="text-content-secondary hover:text-content transition-colors">{{ $design->name }}</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Edit</span>
        </div>
    </x-slot>

    {{-- Page header --}}
    <div class="mb-6">
        <h1 class="font-display text-xl font-bold text-content">Edit Design</h1>
        <p class="text-xs text-content-secondary mt-0.5">Update {{ $design->name }} ({{ $design->code }})</p>
    </div>

    {{-- Validation errors --}}
    @if($errors->any())
        <div class="mb-6 bg-danger-50 border border-danger-500/20 rounded-xl px-4 py-3">
            <div class="flex items-start gap-3">
                <svg class="w-4 h-4 text-danger-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <ul class="list-disc list-inside text-sm text-danger-500 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.designs.update', $design) }}" enctype="multipart/form-data" class="max-w-4xl space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl border border-surface-border-light shadow-card p-5">
            <h3 class="font-display text-base font-semibold text-content mb-4">Design Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Design Code <span class="text-danger-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $design->code) }}" required class="input">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Name <span class="text-danger-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $design->name) }}" required class="input">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Description</label>
                    <textarea name="description" rows="3" class="input">{{ old('description', $design->description) }}</textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Category</label>
                    <input type="text" name="category" value="{{ old('category', $design->category) }}" class="input">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">HSN Code</label>
                    <input type="text" name="hsn_code" value="{{ old('hsn_code', $design->hsn_code) }}" class="input">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">GST Rate (%)</label>
                    <input type="number" name="gst_rate" value="{{ old('gst_rate', $design->gst_rate) }}" step="0.01" class="input">
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Status</label>
                    <select name="status" class="input">
                        <option value="active" {{ $design->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $design->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-content-secondary uppercase tracking-wide mb-1 block">Primary Image</label>
                    @if($design->primary_image_path)
                        <div class="mb-2">
                            <img src="{{ Storage::url($design->primary_image_path) }}" alt="{{ $design->name }}" class="w-20 h-20 rounded-lg object-cover border border-surface-border-light">
                        </div>
                    @endif
                    <input type="file" name="image" accept="image/*" class="input">
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 text-sm font-semibold transition-colors">Update Design</button>
            <a href="{{ route('admin.designs.show', $design) }}" class="border border-surface-border bg-white hover:bg-surface-secondary text-content rounded-lg px-4 py-2 text-sm font-semibold transition-colors">Cancel</a>
        </div>
    </form>
</x-admin-layout>
