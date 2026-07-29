<?php
namespace VMP\Modules\VendorRegistration\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use VMP\Modules\VendorRegistration\DTOs\NewVendorDTO;
use VMP\Modules\VendorRegistration\Repositories\WpVendorRequestRepository;
use VMP\Modules\VendorRegistration\Services\RegistrationService;

class RegistrationController {
    private RegistrationService $service;

    public function __construct() {
        $repo = new WpVendorRequestRepository();
        $this->service = new RegistrationService($repo);
    }

    public function register(WP_REST_Request $request): WP_REST_Response {
        $params = $request->get_json_params();
        $dto = new NewVendorDTO($params);
        // Basic server-side validation should run here
        $created = $this->service->register($dto);
        return new WP_REST_Response(['success' => true, 'request' => $created], 201);
    }

    public function saveDraft(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response(['error' => 'Unauthorized'], 401);
        }
        $params = $request->get_json_params();
        $repo = new WpVendorRequestRepository();
        $existing = $repo->findByUser($user_id);
        $data = ['draft_data' => wp_json_encode($params)];
        if ($existing) {
            $repo->update($existing->id, $data);
            return new WP_REST_Response(['success' => true]);
        }
        $data = array_merge($data, ['user_id' => $user_id, 'status' => 'draft']);
        $repo->create($data);
        return new WP_REST_Response(['success' => true], 201);
    }
}
