<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Label;
use App\Models\Manifest;
use App\Models\Marketplace;
use App\Models\Order;
use App\Models\SubOrder;
use App\Services\ManifestImportService;
use Illuminate\Http\Request;

class ManifestController extends Controller
{
    public function __construct(
        private ManifestImportService $manifestImportService,
    ) {}

    /**
     * List manifests.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = Manifest::where('company_id', $companyId)
            ->with(['marketplace']);

        if ($marketplaceId = $request->get('marketplace_id')) {
            $query->where('marketplace_id', $marketplaceId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $manifests = $query->orderByDesc('manifest_date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $marketplaces = Marketplace::active()->orderBy('name')->get();

        return view('admin.manifests.index', compact('manifests', 'marketplaces'));
    }

    /**
     * Upload form.
     */
    public function upload()
    {
        $marketplaces = Marketplace::active()->orderBy('name')->get();

        return view('admin.manifests.upload', compact('marketplaces'));
    }

    /**
     * Process manifest PDF upload.
     */
    public function import(Request $request)
    {
        $request->validate([
            'pdf_file'       => 'required|file|mimes:pdf|max:20480',
            'marketplace_id' => 'required|exists:marketplaces,id',
        ]);

        $companyId = auth()->user()->company_id;
        $marketplaceId = $request->marketplace_id;

        $file = $request->file('pdf_file');
        $storagePath = $file->store("manifests/{$companyId}", 'local');

        try {
            $batch = $this->manifestImportService->importPdf(
                $storagePath,
                $companyId,
                $marketplaceId,
            );

            $message = "Manifest imported: {$batch->successful_records} lines processed";
            if ($batch->failed_records > 0) {
                $message .= ", {$batch->failed_records} errors";
            }
            $message .= '.';

            return redirect()->route('admin.manifests.index')
                ->with('success', $message);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.manifests.upload')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Manifest detail with picklist + shipment lines.
     */
    public function show(Manifest $manifest)
    {
        $this->authorizeCompany($manifest);

        $manifest->load([
            'marketplace',
            'picklistLines',
            'shipmentLines',
        ]);

        return view('admin.manifests.show', compact('manifest'));
    }

    /**
     * Reconciliation view: manifest vs orders vs labels.
     */
    public function reconcile(Manifest $manifest)
    {
        $this->authorizeCompany($manifest);

        $manifest->load(['marketplace', 'picklistLines', 'shipmentLines']);

        $companyId = auth()->user()->company_id;
        $reconciliation = [];

        foreach ($manifest->shipmentLines as $line) {
            $entry = [
                'shipment_line'   => $line,
                'sub_order'       => null,
                'order'           => null,
                'label'           => null,
                'status'          => 'unmatched',
            ];

            if ($line->sub_order_number) {
                $subOrder = SubOrder::where('company_id', $companyId)
                    ->where('sub_order_number', $line->sub_order_number)
                    ->with('order')
                    ->first();

                if ($subOrder) {
                    $entry['sub_order'] = $subOrder;
                    $entry['order'] = $subOrder->order;
                    $entry['status'] = 'matched_order';
                }
            }

            if ($line->awb) {
                $label = Label::where('company_id', $companyId)
                    ->where('awb_number', $line->awb)
                    ->first();

                if ($label) {
                    $entry['label'] = $label;
                    $entry['status'] = $entry['sub_order'] ? 'fully_matched' : 'matched_label';
                }
            }

            $reconciliation[] = $entry;
        }

        $stats = [
            'total'          => count($reconciliation),
            'fully_matched'  => collect($reconciliation)->where('status', 'fully_matched')->count(),
            'matched_order'  => collect($reconciliation)->where('status', 'matched_order')->count(),
            'matched_label'  => collect($reconciliation)->where('status', 'matched_label')->count(),
            'unmatched'      => collect($reconciliation)->where('status', 'unmatched')->count(),
        ];

        return view('admin.manifests.show', compact('manifest', 'reconciliation', 'stats'));
    }

    private function authorizeCompany(Manifest $manifest): void
    {
        if ($manifest->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }
}
