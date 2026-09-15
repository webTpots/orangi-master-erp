<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Label;
use App\Models\LabelFile;
use App\Models\Marketplace;
use App\Services\LabelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LabelController extends Controller
{
    public function __construct(
        private LabelImportService $labelImportService,
    ) {}

    /**
     * List all labels with filters.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = Label::where('company_id', $companyId)
            ->with(['marketplace', 'order', 'subOrder', 'labelFile']);

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Marketplace filter
        if ($marketplaceId = $request->get('marketplace_id')) {
            $query->where('marketplace_id', $marketplaceId);
        }

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('awb_number', 'like', "%{$search}%")
                  ->orWhere('sub_order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%");
            });
        }

        $labels = $query->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        // Stats
        $stats = [
            'total'  => Label::where('company_id', $companyId)->count(),
            'parsed' => Label::where('company_id', $companyId)->where('status', 'parsed')->count(),
            'linked' => Label::where('company_id', $companyId)->where('status', 'linked')->count(),
            'errors' => Label::where('company_id', $companyId)->where('status', 'error')->count(),
        ];

        $marketplaces = Marketplace::active()->orderBy('name')->get();

        // Recent label files
        $recentFiles = LabelFile::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.labels.index', compact('labels', 'stats', 'marketplaces', 'recentFiles'));
    }

    /**
     * Upload form.
     */
    public function upload()
    {
        $marketplaces = Marketplace::active()->orderBy('name')->get();

        return view('admin.labels.upload', compact('marketplaces'));
    }

    /**
     * Process PDF upload and import labels.
     */
    public function import(Request $request)
    {
        $request->validate([
            'pdf_file'       => 'required|file|mimes:pdf|max:20480', // 20 MB max
            'marketplace_id' => 'required|exists:marketplaces,id',
        ]);

        $companyId = auth()->user()->company_id;
        $marketplaceId = $request->marketplace_id;

        // Store uploaded file
        $file = $request->file('pdf_file');
        $storagePath = $file->store("labels/{$companyId}", 'local');

        try {
            $batch = $this->labelImportService->importPdf(
                $storagePath,
                $companyId,
                $marketplaceId,
            );

            $message = "Import complete: {$batch->successful_records} labels linked";
            if ($batch->failed_records > 0) {
                $message .= ", {$batch->failed_records} errors";
            }
            $message .= " out of {$batch->total_records} total pages.";

            return redirect()->route('admin.labels.index')
                ->with('success', $message);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.labels.upload')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * View single label.
     */
    public function show(Label $label)
    {
        if ($label->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $label->load(['marketplace', 'order.subOrders', 'subOrder', 'labelFile']);

        return view('admin.labels.show', compact('label'));
    }
}
