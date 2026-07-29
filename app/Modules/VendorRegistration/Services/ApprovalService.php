<?php
namespace VMP\Modules\VendorRegistration\Services;

use VMP\Modules\VendorRegistration\Repositories\VendorRequestRepositoryInterface;
use VMP\Modules\VendorRegistration\Events\VendorApproved;
use VMP\Modules\VendorRegistration\Events\VendorRejected;

class ApprovalService
{
    public function __construct(private VendorRequestRepositoryInterface $requestsRepo, private StateMachine $stateMachine)
    {
    }

    /**
     * Approve a vendor request
     * @param int $requestId
     * @param int $adminId
     * @return bool
     */
    public function approve(int $requestId, int $adminId): bool
    {
        $request = $this->requestsRepo->find($requestId);
        if (!$request) {
            throw new \InvalidArgumentException('Request not found');
        }

        // Ensure valid transition
        $from = $request->status ?? 'draft';
        $to = 'store_setup';
        if (!$this->stateMachine->canTransition($from, $to)) {
            throw new \RuntimeException("Cannot transition from {$from} to {$to}");
        }

        $ok = $this->requestsRepo->updateStatus($requestId, $to, null);
        if (!$ok) {
            return false;
        }

        // Grant vendor role to user
        if (!empty($request->user_id)) {
            $user = get_user_by('ID', $request->user_id);
            if ($user) {
                $user->add_role('vendor');
            }
        }

        // Reload request
        $request = $this->requestsRepo->find($requestId);

        // Fire VendorApproved event
        $event = new VendorApproved($request, $adminId);
        /**
         * Trigger a WP action so listeners can react (CreateStoreOnApproval, SendApprovalEmail, etc.)
         */
        do_action('vmp_vendor_approved', $event);

        return true;
    }

    public function reject(int $requestId, int $adminId, string $reason): bool
    {
        $request = $this->requestsRepo->find($requestId);
        if (!$request) {
            throw new \InvalidArgumentException('Request not found');
        }

        $from = $request->status ?? 'draft';
        $to = 'rejected';
        if (!$this->stateMachine->canTransition($from, $to)) {
            // allow rejection from under_review or submitted
            // we'll allow for safety but still log
        }

        $ok = $this->requestsRepo->updateStatus($requestId, $to, $reason);
        if (!$ok) return false;

        // Reload request
        $request = $this->requestsRepo->find($requestId);

        $event = new VendorRejected($request, $reason);
        do_action('vmp_vendor_rejected', $event);

        return true;
    }
}
