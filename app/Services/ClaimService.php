<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\ClaimCommunication;
use Illuminate\Support\Facades\DB;

class ClaimService
{
    /**
     * Create a new claim.
     */
    public function createClaim(array $data): Claim
    {
        return Claim::create([
            'company_id'       => $data['company_id'],
            'return_id'        => $data['return_id'] ?? null,
            'order_id'         => $data['order_id'] ?? null,
            'claim_type'       => $data['claim_type'],
            'claim_against'    => $data['claim_against'],
            'status'           => Claim::STATUS_DRAFT,
            'reference_number' => $data['reference_number'] ?? null,
            'claimed_amount'   => $data['claimed_amount'],
            'currency'         => $data['currency'] ?? 'INR',
            'evidence_json'    => $data['evidence_json'] ?? null,
            'notes'            => $data['notes'] ?? null,
        ]);
    }

    /**
     * File a claim (mark as filed).
     */
    public function fileClaim(Claim $claim): Claim
    {
        return DB::transaction(function () use ($claim) {
            $claim->changeStatus(Claim::STATUS_FILED);
            $claim->filed_at = now();
            $claim->save();

            return $claim;
        });
    }

    /**
     * Update claim status with optional data.
     */
    public function updateClaimStatus(Claim $claim, string $status, array $data = []): Claim
    {
        return DB::transaction(function () use ($claim, $status, $data) {
            $claim->changeStatus($status);

            if (! empty($data['approved_amount'])) {
                $claim->approved_amount = $data['approved_amount'];
            }

            if (! empty($data['resolution_notes'])) {
                $claim->resolution_notes = $data['resolution_notes'];
            }

            if (in_array($status, [Claim::STATUS_APPROVED, Claim::STATUS_PARTIALLY_APPROVED, Claim::STATUS_REJECTED, Claim::STATUS_SETTLED, Claim::STATUS_CLOSED])) {
                $claim->resolved_at = now();
            }

            $claim->save();

            return $claim;
        });
    }

    /**
     * Add a communication record to a claim.
     */
    public function addCommunication(Claim $claim, array $data): ClaimCommunication
    {
        return ClaimCommunication::create([
            'claim_id'         => $claim->id,
            'direction'        => $data['direction'],
            'channel'          => $data['channel'],
            'subject'          => $data['subject'] ?? null,
            'message'          => $data['message'],
            'attachments_json' => $data['attachments_json'] ?? null,
            'communicated_at'  => $data['communicated_at'] ?? now(),
            'communicated_by'  => auth()->id(),
        ]);
    }

    /**
     * Settle a claim.
     */
    public function settleClaim(Claim $claim, float $settledAmount, string $notes): Claim
    {
        return DB::transaction(function () use ($claim, $settledAmount, $notes) {
            $claim->changeStatus(Claim::STATUS_SETTLED);
            $claim->settled_amount = $settledAmount;
            $claim->resolution_notes = $notes;
            $claim->resolved_at = now();
            $claim->save();

            // If linked to a return, update return status
            if ($claim->return_id) {
                $return = $claim->returnOrder;
                if ($return && $return->canTransitionTo('claim_settled')) {
                    $return->changeStatus('claim_settled');
                }
            }

            return $claim;
        });
    }

    /**
     * Get aggregate claim statistics for a company.
     */
    public function getClaimSummary(int $companyId): array
    {
        $claims = Claim::where('company_id', $companyId);

        $totalClaimed = (clone $claims)->sum('claimed_amount');
        $totalApproved = (clone $claims)->whereNotNull('approved_amount')->sum('approved_amount');
        $totalSettled = (clone $claims)->whereNotNull('settled_amount')->sum('settled_amount');

        return [
            'total'          => (clone $claims)->count(),
            'pending'        => (clone $claims)->whereIn('status', ['draft', 'filed', 'under_review'])->count(),
            'approved'       => (clone $claims)->whereIn('status', ['approved', 'partially_approved'])->count(),
            'rejected'       => (clone $claims)->where('status', 'rejected')->count(),
            'settled'        => (clone $claims)->where('status', 'settled')->count(),
            'total_claimed'  => $totalClaimed,
            'total_approved' => $totalApproved,
            'total_settled'  => $totalSettled,
            'recovery_rate'  => $totalClaimed > 0 ? round(($totalSettled / $totalClaimed) * 100, 1) : 0,
        ];
    }
}
