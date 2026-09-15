<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppException;
use App\Models\ExceptionCategory;
use App\Models\User;
use App\Services\ExceptionService;
use Illuminate\Http\Request;

class ExceptionController extends Controller
{
    public function __construct(
        private ExceptionService $exceptionService,
    ) {}

    /**
     * Exception Center dashboard with KPIs, filters, exception list.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = AppException::where('company_id', $companyId)
            ->with(['category', 'assignee', 'latestAssignment']);

        // Severity filter
        if ($severity = $request->get('severity')) {
            $query->where('severity', $severity);
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Category filter
        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Exception type filter
        if ($type = $request->get('exception_type')) {
            $query->where('exception_type', $type);
        }

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        $exceptions = $query->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        // KPIs
        $dashboard = $this->exceptionService->getExceptionDashboard($companyId);

        // Categories for filter
        $categories = ExceptionCategory::active()->orderBy('name')->get();

        return view('admin.exceptions.index', compact('exceptions', 'dashboard', 'categories'));
    }

    /**
     * Exception detail with timeline, comments, assignment.
     */
    public function show(AppException $exception)
    {
        $this->authorizeCompany($exception);

        $exception->load([
            'category',
            'assignee',
            'resolver',
            'assignments.assignedUser',
            'assignments.assigner',
            'comments.user',
        ]);

        $users = User::where('company_id', auth()->user()->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.exceptions.show', compact('exception', 'users'));
    }

    /**
     * Assign exception to user.
     */
    public function assign(Request $request, AppException $exception)
    {
        $this->authorizeCompany($exception);

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        try {
            $this->exceptionService->assignException(
                $exception,
                (int) $request->assigned_to,
                auth()->id(),
            );

            return redirect()->route('admin.exceptions.show', $exception)
                ->with('success', 'Exception assigned successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Resolve exception.
     */
    public function resolve(Request $request, AppException $exception)
    {
        $this->authorizeCompany($exception);

        $request->validate([
            'resolution_notes' => 'required|string|max:2000',
        ]);

        try {
            $this->exceptionService->resolveException(
                $exception,
                $request->resolution_notes,
                auth()->id(),
            );

            return redirect()->route('admin.exceptions.show', $exception)
                ->with('success', 'Exception resolved successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Escalate exception.
     */
    public function escalate(Request $request, AppException $exception)
    {
        $this->authorizeCompany($exception);

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $this->exceptionService->escalateException($exception, $request->reason);

            return redirect()->route('admin.exceptions.show', $exception)
                ->with('success', 'Exception escalated successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Add comment to exception.
     */
    public function addComment(Request $request, AppException $exception)
    {
        $this->authorizeCompany($exception);

        $request->validate([
            'comment'     => 'required|string|max:5000',
            'is_internal' => 'nullable|boolean',
        ]);

        try {
            $this->exceptionService->addComment(
                $exception,
                auth()->id(),
                $request->comment,
                (bool) $request->is_internal,
            );

            return redirect()->route('admin.exceptions.show', $exception)
                ->with('success', 'Comment added.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Trigger auto-detection of exceptions.
     */
    public function autoDetect()
    {
        $companyId = auth()->user()->company_id;

        try {
            $detected = $this->exceptionService->autoDetectExceptions($companyId);
            $count = count($detected);

            if ($count > 0) {
                return redirect()->route('admin.exceptions.index')
                    ->with('success', "{$count} new exception(s) detected and created.");
            }

            return redirect()->route('admin.exceptions.index')
                ->with('success', 'Auto-detection complete. No new exceptions found.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Manage exception categories.
     */
    public function categories()
    {
        $categories = ExceptionCategory::orderBy('name')->get();

        return view('admin.exceptions.categories', compact('categories'));
    }

    /**
     * Store a new exception category.
     */
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:100',
            'code'                  => 'required|string|max:50|unique:exception_categories,code',
            'description'           => 'nullable|string|max:500',
            'default_severity'      => 'required|in:' . implode(',', AppException::SEVERITIES),
            'default_assignee_role' => 'nullable|string|max:50',
            'sla_hours'             => 'nullable|integer|min:1',
        ]);

        ExceptionCategory::create($request->only([
            'name', 'code', 'description', 'default_severity', 'default_assignee_role', 'sla_hours',
        ]));

        return redirect()->route('admin.exceptions.categories')
            ->with('success', 'Category created successfully.');
    }

    private function authorizeCompany(AppException $exception): void
    {
        if ($exception->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }
}
