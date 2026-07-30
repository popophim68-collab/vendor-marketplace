<?php
namespace VMP\Modules\VendorRegistration\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use VMP\Modules\VendorRegistration\Repositories\VendorRequestRepository;

class AdminRequestsController
{
    private VendorRequestRepository $repo;

    public function __construct()
    {
        $this->repo = new VendorRequestRepository();
    }

    public function listRequests(WP_REST_Request $request): WP_REST_Response
    {
        $page = max(1, (int) $request->get_param('page') ?: 1);
        $perPage = min(50, (int) $request->get_param('per_page') ?: 20);

        $offset = ($page - 1) * $perPage;
        $items = $this->repo->all($perPage, $offset);

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

        // activity log might be available via ActivityLogService; for now include minimal
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
}
