<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    /**
     * List bank accounts with balance summary.
     */
    public function index()
    {
        $companyId = auth()->user()->company_id;

        $accounts = BankAccount::where('company_id', $companyId)
            ->orderByDesc('is_primary')
            ->orderBy('bank_name')
            ->get();

        $totalBalance = $accounts->where('status', BankAccount::STATUS_ACTIVE)->sum('current_balance');

        return view('admin.bank-accounts.index', compact('accounts', 'totalBalance'));
    }

    /**
     * Show the form to add a bank account.
     */
    public function create()
    {
        return view('admin.bank-accounts.create');
    }

    /**
     * Store a new bank account.
     */
    public function store(Request $request)
    {
        $request->validate([
            'account_name'   => 'required|string|max:255',
            'bank_name'      => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'ifsc_code'      => 'nullable|string|max:20',
            'branch'         => 'nullable|string|max:255',
            'account_type'   => 'required|in:current,savings,overdraft',
            'opening_balance' => 'required|numeric|min:0',
            'is_primary'     => 'boolean',
        ]);

        $companyId = auth()->user()->company_id;

        // Check uniqueness
        $exists = BankAccount::where('company_id', $companyId)
            ->where('account_number', $request->account_number)
            ->exists();

        if ($exists) {
            return redirect()->back()->withInput()->with('error', 'An account with this account number already exists.');
        }

        // If marking as primary, unset other primaries
        if ($request->boolean('is_primary')) {
            BankAccount::where('company_id', $companyId)->update(['is_primary' => false]);
        }

        $account = BankAccount::create([
            'company_id'      => $companyId,
            'account_name'    => $request->account_name,
            'bank_name'       => $request->bank_name,
            'account_number'  => $request->account_number,
            'ifsc_code'       => $request->ifsc_code,
            'branch'          => $request->branch,
            'account_type'    => $request->account_type,
            'opening_balance' => $request->opening_balance,
            'current_balance' => $request->opening_balance,
            'is_primary'      => $request->boolean('is_primary'),
            'status'          => BankAccount::STATUS_ACTIVE,
        ]);

        return redirect()->route('admin.bank-accounts.show', $account)
            ->with('success', 'Bank account added successfully.');
    }

    /**
     * Show account detail with recent transactions.
     */
    public function show(BankAccount $bankAccount)
    {
        $this->authorizeCompany($bankAccount);

        $transactions = $bankAccount->transactions()
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(25);

        $imports = $bankAccount->statementImports()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.bank-accounts.show', compact('bankAccount', 'transactions', 'imports'));
    }

    /**
     * Show the form to edit a bank account.
     */
    public function edit(BankAccount $bankAccount)
    {
        $this->authorizeCompany($bankAccount);

        return view('admin.bank-accounts.edit', compact('bankAccount'));
    }

    /**
     * Update a bank account.
     */
    public function update(Request $request, BankAccount $bankAccount)
    {
        $this->authorizeCompany($bankAccount);

        $request->validate([
            'account_name' => 'required|string|max:255',
            'bank_name'    => 'required|string|max:255',
            'ifsc_code'    => 'nullable|string|max:20',
            'branch'       => 'nullable|string|max:255',
            'account_type' => 'required|in:current,savings,overdraft',
            'is_primary'   => 'boolean',
            'status'       => 'required|in:active,inactive,closed',
        ]);

        $companyId = auth()->user()->company_id;

        if ($request->boolean('is_primary') && ! $bankAccount->is_primary) {
            BankAccount::where('company_id', $companyId)->update(['is_primary' => false]);
        }

        $bankAccount->update([
            'account_name' => $request->account_name,
            'bank_name'    => $request->bank_name,
            'ifsc_code'    => $request->ifsc_code,
            'branch'       => $request->branch,
            'account_type' => $request->account_type,
            'is_primary'   => $request->boolean('is_primary'),
            'status'       => $request->status,
        ]);

        return redirect()->route('admin.bank-accounts.show', $bankAccount)
            ->with('success', 'Bank account updated successfully.');
    }

    // ── Private Helpers ─────────────────────────────────────────

    private function authorizeCompany(BankAccount $bankAccount): void
    {
        if ($bankAccount->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }
}
