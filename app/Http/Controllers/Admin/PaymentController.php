<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Settlement;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * List payments with filters and KPI cards.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = Payment::where('company_id', $companyId)
            ->with('settlement.marketplaceAccount.marketplace');

        // Filters
        if ($paymentType = $request->get('payment_type')) {
            $query->where('payment_type', $paymentType);
        }

        if ($paymentMethod = $request->get('payment_method')) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($fromDate = $request->get('from_date')) {
            $query->where('paid_at', '>=', $fromDate);
        }

        if ($toDate = $request->get('to_date')) {
            $query->where('paid_at', '<=', $toDate);
        }

        $payments = $query->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        // KPIs
        $kpis = [
            'total_received' => Payment::where('company_id', $companyId)
                ->whereIn('status', ['received', 'confirmed'])
                ->sum('amount'),
            'pending'        => Payment::where('company_id', $companyId)
                ->where('status', 'pending')
                ->sum('amount'),
            'this_month'     => Payment::where('company_id', $companyId)
                ->whereIn('status', ['received', 'confirmed'])
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount'),
        ];

        return view('admin.payments.index', compact('payments', 'kpis'));
    }

    /**
     * Payment detail.
     */
    public function show(Payment $payment)
    {
        $this->authorizeCompany($payment);

        $payment->load('settlement.marketplaceAccount.marketplace');

        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Record new payment form.
     */
    public function create()
    {
        $companyId = auth()->user()->company_id;

        $settlements = Settlement::where('company_id', $companyId)
            ->where('status', '!=', 'closed')
            ->with('marketplaceAccount.marketplace')
            ->orderByDesc('settlement_date')
            ->get();

        return view('admin.payments.create', compact('settlements'));
    }

    /**
     * Save a new payment.
     */
    public function store(Request $request)
    {
        $request->validate([
            'payment_type'   => 'required|string|in:' . implode(',', Payment::PAYMENT_TYPES),
            'payment_method' => 'required|string|in:' . implode(',', Payment::PAYMENT_METHODS),
            'amount'         => 'required|numeric|min:0.01',
            'paid_at'        => 'required|date',
            'settlement_id'  => 'nullable|exists:settlements,id',
            'reference_number' => 'nullable|string|max:255',
            'bank_reference'   => 'nullable|string|max:255',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $companyId = auth()->user()->company_id;

        // Validate settlement belongs to company
        if ($request->settlement_id) {
            $settlement = Settlement::findOrFail($request->settlement_id);
            if ($settlement->company_id !== $companyId) {
                abort(403);
            }
        }

        Payment::create([
            'company_id'       => $companyId,
            'settlement_id'    => $request->settlement_id,
            'payment_type'     => $request->payment_type,
            'payment_method'   => $request->payment_method,
            'amount'           => $request->amount,
            'paid_at'          => $request->paid_at,
            'reference_number' => $request->reference_number,
            'bank_reference'   => $request->bank_reference,
            'status'           => Payment::STATUS_PENDING,
            'notes'            => $request->notes,
        ]);

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment recorded successfully.');
    }

    /**
     * Confirm a payment as received.
     */
    public function confirm(Payment $payment)
    {
        $this->authorizeCompany($payment);

        $payment->update(['status' => Payment::STATUS_CONFIRMED]);

        return redirect()->route('admin.payments.show', $payment)
            ->with('success', 'Payment confirmed.');
    }

    // ── Private Helpers ─────────────────────────────────────────

    private function authorizeCompany(Payment $payment): void
    {
        if ($payment->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }
}
