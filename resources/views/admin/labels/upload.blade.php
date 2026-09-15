<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.labels.index') }}" class="text-content-muted hover:text-content transition">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            </a>
            <h2 class="text-xl font-display font-semibold text-content">Upload Label PDF</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('error'))
                <div class="rounded-lg border border-danger-border bg-danger-50 p-4 text-sm text-danger-500">{{ session('error') }}</div>
            @endif

            <div class="rounded-xl border border-surface-border bg-white p-8 shadow-card">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-content">Import Meesho / Flipkart Labels</h3>
                    <p class="mt-1 text-sm text-content-secondary">
                        Upload a PDF containing shipping labels with tax invoices. Each page will be parsed as one sub-order.
                        The system will automatically create orders, link labels, and attempt SKU mapping.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.labels.import') }}" enctype="multipart/form-data" class="space-y-6">
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
                        <label for="pdf_file" class="block text-sm font-medium text-content mb-1">Label PDF File</label>
                        <div class="mt-1 flex justify-center rounded-lg border-2 border-dashed border-surface-border px-6 py-10 hover:border-brand-400 transition">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-content-muted" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
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
                        <a href="{{ route('admin.labels.index') }}"
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

            {{-- Help Card --}}
            <div class="rounded-xl border border-surface-border bg-surface-secondary p-6">
                <h4 class="text-sm font-semibold text-content mb-2">Supported Formats</h4>
                <ul class="text-sm text-content-secondary space-y-1">
                    <li>Meesho Sub Order Labels (combined shipping label + tax invoice per page)</li>
                    <li>Flipkart Invoice Labels</li>
                </ul>
                <h4 class="text-sm font-semibold text-content mt-4 mb-2">What happens during import</h4>
                <ol class="text-sm text-content-secondary space-y-1 list-decimal list-inside">
                    <li>Each page is parsed to extract order details, customer info, courier, AWB, and invoice data</li>
                    <li>Orders and sub-orders are created or matched to existing records</li>
                    <li>SKU mapping is attempted using the intelligence engine</li>
                    <li>Duplicate sub-orders are automatically skipped</li>
                </ol>
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
