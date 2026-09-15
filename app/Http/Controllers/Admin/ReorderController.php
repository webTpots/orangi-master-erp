<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\ReorderRule;
use App\Models\ReorderSuggestion;
use App\Models\Sku;
use App\Models\Vendor;
use App\Services\ReorderEngine;
use Illuminate\Http\Request;

class ReorderController extends Controller
{
    public function __construct(
        private ReorderEngine $reorderEngine
    ) {}

    public function dashboard()
    {
        $companyId = auth()->user()->company_id;
        $dashboardData = $this->reorderEngine->getReorderDashboard($companyId);

        return view('admin.reorder.dashboard', compact('dashboardData'));
    }

    public function suggestions(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = ReorderSuggestion::forCompany($companyId)
            ->with(['sku.variant.product.design', 'vendor']);

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($vendorId = $request->get('vendor_id')) {
            $query->where('vendor_id', $vendorId);
        }

        $suggestions = $query->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            ->orderBy('days_until_stockout')
            ->paginate(30)
            ->withQueryString();

        $vendors = Vendor::where('company_id', $companyId)->active()->orderBy('name')->get();

        return view('admin.reorder.suggestions', compact('suggestions', 'vendors'));
    }

    public function runCheck()
    {
        $companyId = auth()->user()->company_id;

        try {
            $count = $this->reorderEngine->runReorderCheck($companyId);
            return redirect()->route('admin.reorder.suggestions')
                ->with('success', "{$count} reorder suggestions generated.");
        } catch (\Exception $e) {
            return redirect()->route('admin.reorder.dashboard')
                ->with('error', 'Error running reorder check: ' . $e->getMessage());
        }
    }

    public function convertToPo(ReorderSuggestion $suggestion)
    {
        if ($suggestion->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        try {
            $po = $this->reorderEngine->convertToPurchaseOrder($suggestion);
            return redirect()->route('admin.purchase-orders.show', $po)
                ->with('success', "Purchase order {$po->po_number} created from reorder suggestion.");
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function bulkConvertToPo(Request $request)
    {
        $request->validate([
            'suggestion_ids'   => 'required|array',
            'suggestion_ids.*' => 'exists:reorder_suggestions,id',
        ]);

        try {
            $pos = $this->reorderEngine->bulkConvertToPurchaseOrder($request->suggestion_ids);
            $count = count($pos);
            return redirect()->route('admin.reorder.suggestions')
                ->with('success', "{$count} purchase order(s) created from reorder suggestions.");
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function rules()
    {
        $companyId = auth()->user()->company_id;

        $rules = ReorderRule::forCompany($companyId)
            ->with(['sku', 'design', 'vendor'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $skus = Sku::where('company_id', $companyId)->active()->get();
        $designs = Design::where('company_id', $companyId)->get();
        $vendors = Vendor::where('company_id', $companyId)->active()->orderBy('name')->get();

        return view('admin.reorder.rules', compact('rules', 'skus', 'designs', 'vendors'));
    }

    public function storeRule(Request $request)
    {
        $request->validate([
            'rule_type'              => 'required|in:min_stock,days_of_stock,forecast_based,manual',
            'sku_id'                 => 'nullable|exists:skus,id',
            'design_id'              => 'nullable|exists:designs,id',
            'vendor_id'              => 'nullable|exists:vendors,id',
            'min_stock_threshold'    => 'nullable|integer|min:0',
            'days_of_stock_threshold' => 'nullable|integer|min:1',
            'reorder_quantity'       => 'nullable|integer|min:1',
            'max_order_quantity'     => 'nullable|integer|min:1',
        ]);

        ReorderRule::create([
            'company_id'              => auth()->user()->company_id,
            'rule_type'               => $request->rule_type,
            'sku_id'                  => $request->sku_id,
            'design_id'               => $request->design_id,
            'vendor_id'               => $request->vendor_id,
            'min_stock_threshold'     => $request->min_stock_threshold,
            'days_of_stock_threshold' => $request->days_of_stock_threshold,
            'reorder_quantity'        => $request->reorder_quantity,
            'max_order_quantity'      => $request->max_order_quantity,
            'is_active'               => true,
        ]);

        return redirect()->route('admin.reorder.rules')
            ->with('success', 'Reorder rule created successfully.');
    }

    public function toggleRule(ReorderRule $rule)
    {
        if ($rule->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $rule->update(['is_active' => ! $rule->is_active]);

        $status = $rule->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.reorder.rules')
            ->with('success', "Rule {$status} successfully.");
    }
}
