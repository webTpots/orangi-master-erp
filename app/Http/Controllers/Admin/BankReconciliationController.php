<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankStatementImport;
use App\Models\BankTransaction;
use App\Models\ReconciliationRule;
use App\Services\BankReconciliationService;
use Illuminate\Http\Request;

class BankReconciliationController extends Controller
{
    public function __construct(
        private BankReconciliationService $reconciliationService,
    ) {}

    /**
     * Reconciliation dashboard with summary.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $accounts = BankAccount::where('company_id', $companyId)
            ->active()
            ->orderBy('bank_name')
            ->get();

        $selectedAccountId = $request->get('bank_account_id', $accounts->first()?->id);
        $period = $request->get('period', now()->format('Y-m'));

        $summary = [];
        $selectedAccount = null;
        $unmatchedTransactions = collect();

        if ($selectedAccountId) {
            $selectedAccount = BankAccount::find($selectedAccountId);

            if ($selectedAccount && $selectedAccount->company_id === $companyId) {
                $summary = $this->reconciliationService->getReconciliationSummary($selectedAccount, $period);

                $unmatchedTransactions = BankTransaction::where('bank_account_id', $selectedAccountId)
                    ->where('match_status', BankTransaction::MATCH_UNMATCHED)
                    ->where('transaction_type', 'credit')
                    ->orderByDesc('transaction_date')
                    ->limit(10)
                    ->get();
            }
        }

        return view('admin.bank-reconciliation.index', compact(
            'accounts', 'selectedAccountId', 'period', 'summary',
            'selectedAccount', 'unmatchedTransactions'
        ));
    }

    /**
     * List bank transactions with filters.
     */
    public function transactions(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $accounts = BankAccount::where('company_id', $companyId)
            ->orderBy('bank_name')
            ->get();

        $query = BankTransaction::where('company_id', $companyId)
            ->with('bankAccount');

        if ($accountId = $request->get('bank_account_id')) {
            $query->where('bank_account_id', $accountId);
        }

        if ($matchStatus = $request->get('match_status')) {
            $query->where('match_status', $matchStatus);
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        if ($fromDate = $request->get('from_date')) {
            $query->where('transaction_date', '>=', $fromDate);
        }

        if ($toDate = $request->get('to_date')) {
            $query->where('transaction_date', '<=', $toDate);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.bank-reconciliation.transactions', compact('transactions', 'accounts'));
    }

    /**
     * Show the import bank statement form.
     */
    public function import()
    {
        $companyId = auth()->user()->company_id;

        $accounts = BankAccount::where('company_id', $companyId)
            ->active()
            ->orderBy('bank_name')
            ->get();

        $imports = BankStatementImport::where('company_id', $companyId)
            ->with('bankAccount')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('admin.bank-reconciliation.import', compact('accounts', 'imports'));
    }

    /**
     * Process uploaded bank statement file.
     */
    public function processImport(Request $request)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'file'            => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'format'          => 'required|in:csv,excel,ofx',
        ]);

        $account = BankAccount::findOrFail($request->bank_account_id);

        if ($account->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $filePath = $request->file('file')->store('bank-statements', 'local');
        $fullPath = storage_path('app/' . $filePath);

        try {
            $import = $this->reconciliationService->importBankStatement($account, $fullPath, $request->format);

            return redirect()->route('admin.bank-reconciliation.import')
                ->with('success', "Statement imported: {$import->imported_records} transactions added, {$import->duplicate_records} duplicates skipped.");
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Trigger auto-reconciliation for a bank account.
     */
    public function autoReconcile(Request $request)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
        ]);

        $account = BankAccount::findOrFail($request->bank_account_id);

        if ($account->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $result = $this->reconciliationService->autoReconcile(
            $account,
            $request->get('from_date'),
            $request->get('to_date')
        );

        return redirect()->route('admin.bank-reconciliation.index', ['bank_account_id' => $account->id])
            ->with('success', "Auto-reconciliation complete. {$result['matched']} matched, {$result['unmatched']} unmatched out of {$result['total']} transactions.");
    }

    /**
     * Manual match form / process match.
     */
    public function matchTransaction(Request $request, BankTransaction $transaction)
    {
        if ($transaction->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        if ($request->isMethod('post') && $request->has('entity_type') && $request->has('entity_id')) {
            $request->validate([
                'entity_type' => 'required|string',
                'entity_id'   => 'required|integer',
            ]);

            $this->reconciliationService->manualMatch($transaction, $request->entity_type, $request->entity_id);

            return redirect()->route('admin.bank-reconciliation.transactions')
                ->with('success', 'Transaction matched successfully.');
        }

        // Show match page with suggestions
        $suggestions = $this->reconciliationService->getSuggestedMatches($transaction);

        return view('admin.bank-reconciliation.match', compact('transaction', 'suggestions'));
    }

    /**
     * Remove match from a transaction.
     */
    public function unmatchTransaction(BankTransaction $transaction)
    {
        if ($transaction->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $this->reconciliationService->unmatch($transaction);

        return redirect()->back()->with('success', 'Match removed successfully.');
    }

    /**
     * Mark a transaction as ignored.
     */
    public function ignoreTransaction(Request $request, BankTransaction $transaction)
    {
        if ($transaction->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->reconciliationService->markIgnored($transaction, $request->reason);

        return redirect()->back()->with('success', 'Transaction marked as ignored.');
    }

    /**
     * List reconciliation rules.
     */
    public function rules()
    {
        $companyId = auth()->user()->company_id;

        $rules = ReconciliationRule::where('company_id', $companyId)
            ->orderByDesc('priority')
            ->get();

        return view('admin.bank-reconciliation.rules', compact('rules'));
    }

    /**
     * Store a new reconciliation rule.
     */
    public function storeRule(Request $request)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string|max:500',
            'match_field'       => 'required|in:reference_number,description,amount,combination',
            'match_pattern'     => 'nullable|string|max:255',
            'match_entity_type' => 'required|string|max:255',
            'category'          => 'required|in:marketplace_settlement,vendor_payment,refund,expense,salary,tax,transfer,other',
            'priority'          => 'integer|min:0',
            'is_active'         => 'boolean',
            'auto_match'        => 'boolean',
        ]);

        ReconciliationRule::create([
            'company_id'        => auth()->user()->company_id,
            'name'              => $request->name,
            'description'       => $request->description,
            'match_field'       => $request->match_field,
            'match_pattern'     => $request->match_pattern,
            'match_entity_type' => $request->match_entity_type,
            'category'          => $request->category,
            'priority'          => $request->integer('priority', 0),
            'is_active'         => $request->boolean('is_active', true),
            'auto_match'        => $request->boolean('auto_match', false),
        ]);

        return redirect()->route('admin.bank-reconciliation.rules')
            ->with('success', 'Reconciliation rule created successfully.');
    }
}
