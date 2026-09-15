<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReturnResource;
use App\Http\Traits\ApiResponse;
use App\Models\ReturnOrder;
use App\Services\ReturnService;
use Illuminate\Http\Request;

class ReturnApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ReturnService $returnService,
    ) {}

    /**
     * Paginated returns with filters.
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $query = ReturnOrder::forCompany($companyId)
            ->with(['order'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('return_type')) {
            $query->where('return_type', $request->return_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('marketplace_return_id', 'like', "%{$search}%")
                  ->orWhere('tracking_number', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($oq) use ($search) {
                      $oq->where('marketplace_order_id', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = min((int) ($request->per_page ?? 20), 50);
        $paginator = $query->paginate($perPage);

        return $this->paginated(
            $paginator->through(fn ($return) => new ReturnResource($return)),
            'Returns retrieved.'
        );
    }

    /**
     * Return detail.
     */
    public function show(Request $request, ReturnOrder $returnOrder)
    {
        if ($returnOrder->company_id !== $request->user()->company_id) {
            return $this->error('Return not found.', 404);
        }

        $returnOrder->load(['order', 'items', 'inspections']);

        return $this->success(
            new ReturnResource($returnOrder),
            'Return detail retrieved.'
        );
    }

    /**
     * Mark return as received.
     */
    public function receive(Request $request, ReturnOrder $returnOrder)
    {
        if ($returnOrder->company_id !== $request->user()->company_id) {
            return $this->error('Return not found.', 404);
        }

        if (! in_array($returnOrder->status, [ReturnOrder::STATUS_INITIATED, ReturnOrder::STATUS_IN_TRANSIT])) {
            return $this->error(
                "Cannot receive return in status '{$returnOrder->status}'.",
                422
            );
        }

        try {
            $return = $this->returnService->receiveReturn($returnOrder);

            return $this->success(
                new ReturnResource($return->fresh()->load(['order', 'items'])),
                'Return marked as received.'
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
