<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImportController extends Controller
{
    public function __construct(
        protected ProductService $productService,
    ) {}

    /**
     * Show the import upload form.
     */
    public function showImport()
    {
        return view('admin.products.import');
    }

    /**
     * Handle CSV/XLSX upload, parse, and show preview.
     */
    public function processImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:5120',
        ]);

        $file = $request->file('file');
        $path = $file->store('imports', 'local');
        $fullPath = Storage::disk('local')->path($path);

        // Parse the file
        $rows = $this->parseCsvFile($fullPath);

        if (empty($rows)) {
            return back()->with('error', 'No data found in uploaded file.');
        }

        $headers = array_keys($rows[0]);

        // Identify column mappings
        $expectedColumns = [
            'design_code', 'design_name', 'category', 'hsn_code', 'gst_rate',
            'color', 'size', 'selling_price', 'cost_price', 'mrp',
        ];

        // Auto-map headers
        $columnMap = [];
        foreach ($expectedColumns as $expected) {
            foreach ($headers as $header) {
                if (strtolower(str_replace([' ', '_', '-'], '', $header)) === str_replace('_', '', $expected)) {
                    $columnMap[$expected] = $header;
                    break;
                }
            }
        }

        // Store parsed data in session for confirmation step
        session([
            'import_data' => [
                'file_path'  => $path,
                'rows'       => $rows,
                'headers'    => $headers,
                'column_map' => $columnMap,
                'row_count'  => count($rows),
            ],
        ]);

        // Preview
        $preview = array_slice($rows, 0, 20);

        return view('admin.products.import', [
            'step'            => 2,
            'headers'         => $headers,
            'expectedColumns' => $expectedColumns,
            'columnMap'       => $columnMap,
            'preview'         => $preview,
            'totalRows'       => count($rows),
        ]);
    }

    /**
     * Confirm and run the import with mapped columns.
     */
    public function confirmImport(Request $request)
    {
        $importData = session('import_data');
        if (!$importData) {
            return redirect()->route('admin.products.import')->with('error', 'Import session expired. Please upload again.');
        }

        $columnMap = $request->input('column_map', $importData['column_map']);
        $rows      = $importData['rows'];
        $companyId = auth()->user()->company_id;

        // Remap rows with user-selected column mapping
        $mappedRows = [];
        foreach ($rows as $row) {
            $mapped = [];
            foreach ($columnMap as $target => $source) {
                $mapped[$target] = $row[$source] ?? null;
            }
            $mappedRows[] = $mapped;
        }

        // Write a temporary mapped CSV
        $tempPath = Storage::disk('local')->path('imports/mapped_' . uniqid() . '.csv');
        $handle = fopen($tempPath, 'w');
        fputcsv($handle, array_keys($columnMap));
        foreach ($mappedRows as $row) {
            fputcsv($handle, array_values($row));
        }
        fclose($handle);

        $result = $this->productService->importFromCsv($tempPath, $companyId);

        // Clean up
        @unlink($tempPath);
        if (isset($importData['file_path'])) {
            Storage::disk('local')->delete($importData['file_path']);
        }
        session()->forget('import_data');

        return view('admin.products.import', [
            'step'    => 4,
            'result'  => $result,
        ]);
    }

    /**
     * Parse a CSV file into an array of associative arrays.
     */
    protected function parseCsvFile(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return [];
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return [];
        }

        $headers = array_map('trim', $headers);
        $rows = [];

        while (($record = fgetcsv($handle)) !== false) {
            if (count($record) === count($headers)) {
                $rows[] = array_combine($headers, $record);
            }
        }

        fclose($handle);
        return $rows;
    }
}
