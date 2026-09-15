<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.flipkart.dashboard') }}" class="text-content-secondary hover:text-content transition-colors">Flipkart</a>
            <svg class="w-3.5 h-3.5 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-content">Import Labels</span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('error'))
                <div class="rounded-lg border border-danger-border bg-danger-50 p-4 text-sm text-danger-500">{{ session('error') }}</div>
            @endif

            <div class="rounded-xl border border-surface-border bg-white p-8 shadow-card">
                <div class="mb-6">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: #2874F0;">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        </div>
                        <h3 class="text-lg font-semibold text-content">Import Flipkart Labels</h3>
                    </div>
                    <p class="mt-1 text-sm text-content-secondary">
                        Upload a Flipkart shipping label PDF. Each page will be parsed to extract order details,
                        customer information, AWB numbers, and invoice data.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.flipkart.process-labels') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <label for="pdf_file" class="block text-sm font-medium text-content mb-1">Label PDF File</label>
                        <div class="mt-1 flex justify-center rounded-lg border-2 border-dashed border-surface-border px-6 py-10 hover:border-blue-400 transition" style="hover:border-color: #2874F0;">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-content-muted" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                </svg>
                                <div class="mt-4 flex text-sm text-content-secondary">
                                    <label for="pdf_file" class="relative cursor-pointer rounded-md font-semibold hover:opacity-80" style="color: #2874F0;">
                                        <span>Choose a file</span>
                                        <input id="pdf_file" name="pdf_file" type="file" accept=".pdf" required class="sr-only">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-content-muted mt-1">PDF up to 20MB</p>
                                <p id="fileName" class="mt-2 text-sm font-medium hidden" style="color: #2874F0;"></p>
                            </div>
                        </div>
                        @error('pdf_file')
                            <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-surface-border pt-6">
                        <a href="{{ route('admin.flipkart.dashboard') }}"
                           class="rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-content-secondary hover:bg-surface-secondary transition">
                            Cancel
                        </a>
                        <button type="submit"
                                class="rounded-lg text-white px-6 py-2 text-sm font-medium hover:opacity-90 transition" style="background-color: #2874F0;">
                            Upload & Import
                        </button>
                    </div>
                </form>
            </div>

            {{-- Help Card --}}
            <div class="rounded-xl border border-surface-border bg-surface-secondary p-6">
                <h4 class="text-sm font-semibold text-content mb-2">Flipkart Label Format</h4>
                <ul class="text-sm text-content-secondary space-y-1">
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-content-muted mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Flipkart shipping labels with invoice (combined PDF from Seller Dashboard)
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-content-muted mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Each page = one order label with AWB, customer details, and tax invoice
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-content-muted mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Flipkart dispatch cutoff: 12:00 PM daily
                    </li>
                </ul>
                <h4 class="text-sm font-semibold text-content mt-4 mb-2">What happens during import</h4>
                <ol class="text-sm text-content-secondary space-y-1 list-decimal list-inside">
                    <li>Each page is parsed to extract Flipkart order ID, AWB, courier, and invoice data</li>
                    <li>Orders and sub-orders are created or matched to existing records</li>
                    <li>Seller SKUs are mapped to internal SKUs via the mapping table</li>
                    <li>Duplicate orders are automatically skipped</li>
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
