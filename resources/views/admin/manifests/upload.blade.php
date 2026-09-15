<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.manifests.index') }}" class="text-content-muted hover:text-content transition">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            </a>
            <h2 class="text-xl font-display font-semibold text-content">Upload Manifest PDF</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('error'))
                <div class="rounded-lg border border-danger-border bg-danger-50 p-4 text-sm text-danger-500">{{ session('error') }}</div>
            @endif

            <div class="rounded-xl border border-surface-border bg-white p-8 shadow-card">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-content">Import Supplier Manifest</h3>
                    <p class="mt-1 text-sm text-content-secondary">
                        Upload a Meesho Supplier Manifest PDF. The system will parse the picklist section
                        (consolidated SKU quantities) and the courier-wise manifest section (individual shipment lines with AWB numbers).
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.manifests.import') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <label for="marketplace_id" class="block text-sm font-medium text-content mb-1">Marketplace</label>
                        <select name="marketplace_id" id="marketplace_id" required
                                class="w-full rounded-lg border-surface-border bg-surface text-sm text-content focus:border-brand-400 focus:ring-brand-400">
                            <option value="">Select marketplace...</option>
                            @foreach ($marketplaces as $mp)
                                <option value="{{ $mp->id }}" @selected(old('marketplace_id') == $mp->id)>{{ $mp->name }}</option>
                            @endforeach
                        </select>
                        @error('marketplace_id')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="pdf_file" class="block text-sm font-medium text-content mb-1">Manifest PDF File</label>
                        <div class="mt-1 flex justify-center rounded-lg border-2 border-dashed border-surface-border px-6 py-10 hover:border-brand-400 transition">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-content-muted" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h14.25c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z"/>
                                </svg>
                                <div class="mt-4 flex text-sm text-content-secondary">
                                    <label for="pdf_file" class="relative cursor-pointer rounded-md font-semibold text-brand-500 hover:text-brand-600">
                                        <span>Choose a file</span>
                                        <input id="pdf_file" name="pdf_file" type="file" accept=".pdf" required class="sr-only">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-content-muted mt-1">PDF up to 20MB</p>
                                <p id="fileName" class="mt-2 text-sm font-medium text-brand-500 hidden"></p>
                            </div>
                        </div>
                        @error('pdf_file')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-surface-border pt-6">
                        <a href="{{ route('admin.manifests.index') }}"
                           class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">
                            Cancel
                        </a>
                        <button type="submit"
                                class="rounded-lg bg-brand-500 px-6 py-2 text-sm font-medium text-white shadow-brand hover:bg-brand-600 transition">
                            Upload & Import
                        </button>
                    </div>
                </form>
            </div>

            {{-- Help --}}
            <div class="rounded-xl border border-surface-border bg-surface-secondary p-6">
                <h4 class="text-sm font-semibold text-content mb-2">Manifest Sections Parsed</h4>
                <ul class="text-sm text-content-secondary space-y-1 list-disc list-inside">
                    <li><strong>Picklist:</strong> Consolidated SKU + Size + Quantity for picking</li>
                    <li><strong>Courier-wise Manifest:</strong> Individual shipment lines with Sub Order #, AWB, SKU, Size per courier partner</li>
                </ul>
                <h4 class="text-sm font-semibold text-content mt-4 mb-2">Reconciliation</h4>
                <p class="text-sm text-content-secondary">
                    After import, use the Reconcile view to compare manifest entries against existing orders and labels.
                    Unmatched entries will be highlighted for review.
                </p>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('pdf_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            const display = document.getElementById('fileName');
            if (fileName) {
                display.textContent = fileName;
                display.classList.remove('hidden');
            }
        });
    </script>
    @endpush
</x-admin-layout>
