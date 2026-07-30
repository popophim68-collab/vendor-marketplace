<?php
namespace VMP\Modules\VendorRegistration\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use VMP\Modules\VendorRegistration\Repositories\VendorRequestRepository;
use VMP\Modules\VendorRegistration\Services\ActivityLogService;

class AdminRequestsController
{
    private VendorRequestRepository $repo;
    private ActivityLogService $activityService;

    public function __construct()
    {
        $this->repo = new VendorRequestRepository();
        $this->activityService = new ActivityLogService();
    }

    public function listRequests(WP_REST_Request $request): WP_REST_Response
    {
        $page = max(1, (int) $request->get_param('page') ?: 1);
        $perPage = min(50, (int) $request->get_param('per_page') ?: 20);
        $q = $request->get_param('q') ?: '';
        $status = $request->get_param('status') ?: '';

        $offset = ($page - 1) * $perPage;
        $items = $this->repo->search($q, $status, $perPage, $offset);

        // map to simpler structure
        $data = array_map(function($r){
            return [
                'id' => (int)$r->id,
                'vendor_id' => $r->user_id ?? null,
                'vendor_name' => $r->vendor_name ?? '',
                'store_name' => $r->store_name ?? '',
                'status' => $r->status ?? '',
                'submitted_at' => $r->created_at ?? '',
                'last_activity' => $r->updated_at ?? '',
            ];
        }, $items ?: []);

        return new WP_REST_Response(['data' => $data, 'page' => $page, 'per_page' => $perPage], 200);
    }

    public function getRequest(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $r = $this->repo->find($id);
        if (!$r) return new WP_REST_Response(['error' => 'not_found'], 404);

        $detail = [
            'id' => (int)$r->id,
            'vendor_id' => $r->user_id ?? null,
            'vendor_name' => $r->vendor_name ?? '',
            'store_name' => $r->store_name ?? '',
            'status' => $r->status ?? '',
            'submitted_at' => $r->created_at ?? '',
            'last_activity' => $r->updated_at ?? '',
            'payload' => $r,
        ];

        return new WP_REST_Response(['data' => $detail], 200);
    }

    public function healthSummary(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $r = $this->repo->find($id);
        if (!$r) return new WP_REST_Response(['error' => 'not_found'], 404);

        // compute simple health metrics
        $total = 6; $score = 0; $warnings = [];

        // logo/banner
        if (!empty($r->logo)) $score++; else $warnings[] = 'Missing logo';
        if (!empty($r->banner)) $score++; else $warnings[] = 'Missing banner';
        // contact
        if (!empty($r->contact_email) || !empty($r->contact_phone)) $score++; else $warnings[] = 'Missing contact info';
        // policies
        if (!empty($r->terms_accepted) || !empty($r->policies)) $score++; else $warnings[] = 'Missing policies';
        // store fields
        if (!empty($r->store_name) && !empty($r->store_description)) $score++; else $warnings[] = 'Incomplete store details';
        // previous requests
        $prevRequests = $this->repo->countRequestsByVendor($r->user_id ?? 0);
        if ($prevRequests > 0) $score++; // counts as activity

        $percent = (int) (($score / $total) * 100);

        $summary = [
            'percent_complete' => $percent,
            'warnings' => $warnings,
            'previous_requests' => $prevRequests,
            'last_activity' => $r->updated_at ?? '',
        ];

        return new WP_REST_Response(['data' => $summary], 200);
    }

    public function getActivity(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $page = max(1, (int)$request->get_param('page') ?: 1);
        $perPage = min(50, (int)$request->get_param('per_page') ?: 20);

        $offset = ($page - 1) * $perPage;
        $items = $this->activityService->findByRequest($id, $perPage, $offset);

        return new WP_REST_Response(['data' => $items, 'page' => $page, 'per_page' => $perPage], 200);
    }

    public function bulkAction(WP_REST_Request $request): WP_REST_Response
    {
        $body = json_decode($request->get_body() ?: '{}', true);
        $action = $body['action'] ?? '';
        $ids = $body['ids'] ?? [];

        if (!in_array($action, ['activate','reject','request_changes'])) {
            return new WP_REST_Response(['error'=>'invalid_action'], 400);
        }

        $results = [];
        foreach ($ids as $id) {
            try {
                // delegate to AdminReviewService (assumed exists)
                $svc = new \VMP\Modules\VendorRegistration\Services\AdminReviewService();
                if ($action === 'activate') $svc->activate((int)$id, get_current_user_id());
                if ($action === 'reject') $svc->reject((int)$id, get_current_user_id(), 'Bulk reject');
                if ($action === 'request_changes') $svc->requestChanges((int)$id, get_current_user_id(), 'Bulk request');
                $results[$id] = 'ok';
            } catch (\Throwable $e) {
                $results[$id] = 'error';
            }
        }

        return new WP_REST_Response(['data' => $results], 200);
    }
}
