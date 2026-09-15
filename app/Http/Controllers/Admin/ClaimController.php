<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Order;
use App\Models\ReturnOrder;
use App\Services\ClaimService;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function __construct(
        private ClaimService $claimService,
    ) {}

    /**
     * List claims with filters and KPIs.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = Claim::where('company_id', $companyId)
            ->with(['returnOrder', 'order']);

        // Claim type filter
        if ($type = $request->get('claim_type')) {
            $query->where('claim_type', $type);
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Claim against filter
        if ($against = $request->get('claim_against')) {
            $query->where('claim_against', $against);
        }

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($oq) use ($search) {
                      $oq->where('marketplace_order_id', 'like', "%{$search}%");
                  });
            });
        }

        $claims = $query->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        // KPIs
        $summary = $this->claimService->getClaimSummary($companyId);

        return view('admin.claims.index', compact('claims', 'summary'));
    }

    /**
     * Claim detail.
     */
    public function show(Claim $claim)
    {
        $this->authorizeCompany($claim);

        $claim->load([
            'returnOrder.order',
            'order',
            'communications.communicator',
        ]);

        $availableTransitions = Claim::TRANSITIONS[$claim->status] ?? [];

        return view('admin.claims.show', compact('claim', 'availableTransitions'));
    }

    /**
     * Show create claim form.
     */
    public function create(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $returnOrder = null;
        if ($returnId = $request->get('return_id')) {
            $returnOrder = ReturnOrder::where('id', $returnId)
                ->where('company_id', $companyId)
                ->with('order')
                ->first();
        }

        $orders = Order::where('company_id', $companyId)
            ->orderByDesc('order_date')
            ->limit(50)
            ->get();

        $returns = ReturnOrder::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('admin.claims.create', compact('returnOrder', 'orders', 'returns'));
    }

    /**
     * Store a new claim.
     */
    public function store(Request $request)
    {
        $request->validate([
            'claim_type'       => 'required|in:' . implode(',', Claim::TYPES),
            'claim_against'    => 'required|in:' . implode(',', Claim::AGAINST_OPTIONS),
            'return_id'        => 'nullable|exists:returns,id',
            'order_id'         => 'nullable|exists:orders,id',
            'claimed_amount'   => 'required|numeric|min:0.01',
            'reference_number' => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:2000',
        ]);

        $data = $request->all();
        $data['company_id'] = auth()->user()->company_id;

        try {
            $claim = $this->claimService->createClaim($data);

            return redirect()->route('admin.claims.show', $claim)
                ->with('success', 'Claim created successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * File a claim.
     */
    public function file(Claim $claim)
    {
        $this->authorizeCompany($claim);

        try {
            $this->claimService->fileClaim($claim);

            return redirect()->route('admin.claims.show', $claim)
                ->with('success', 'Claim filed successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update claim status.
     */
    public function updateStatus(Request $request, Claim $claim)
    {
        $this->authorizeCompany($claim);

        $request->validate([
            'status'           => 'required|in:' . implode(',', Claim::STATUSES),
            'approved_amount'  => 'nullable|numeric|min:0',
            'resolution_notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->claimService->updateClaimStatus($claim, $request->status, $request->all());

            return redirect()->route('admin.claims.show', $claim)
                ->with('success', 'Claim status updated to ' . Claim::STATUS_LABELS[$request->status] . '.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Add communication to a claim.
     */
    public function addCommunication(Request $request, Claim $claim)
    {
        $this->authorizeCompany($claim);

        $request->validate([
            'direction' => 'required|in:outgoing,incoming',
            'channel'   => 'required|in:email,phone,portal,chat',
            'subject'   => 'nullable|string|max:255',
            'message'   => 'required|string|max:5000',
        ]);

        try {
            $this->claimService->addCommunication($claim, $request->all());

            return redirect()->route('admin.claims.show', $claim)
                ->with('success', 'Communication added.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Settle a claim.
     */
    public function settle(Request $request, Claim $claim)
    {
        $this->authorizeCompany($claim);

        $request->validate([
            'settled_amount'   => 'required|numeric|min:0',
            'resolution_notes' => 'required|string|max:2000',
        ]);

        try {
            $this->claimService->settleClaim($claim, (float) $request->settled_amount, $request->resolution_notes);

            return redirect()->route('admin.claims.show', $claim)
                ->with('success', 'Claim settled successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function authorizeCompany(Claim $claim): void
    {
        if ($claim->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }
}
