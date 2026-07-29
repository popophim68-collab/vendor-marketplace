<?php
// Rest routes for vendor registration
add_action('rest_api_init', function() {
    register_rest_route('vmp/v1', '/vendor/register', [
        'methods' => 'POST',
        'callback' => [\VMP\Modules\VendorRegistration\Controllers\RegistrationController::class, 'register'],
        'permission_callback' => function() { return true; },
    ]);

    register_rest_route('vmp/v1', '/vendor/draft', [
        'methods' => 'POST',
        'callback' => [\VMP\Modules\VendorRegistration\Controllers\RegistrationController::class, 'saveDraft'],
        'permission_callback' => function() { return is_user_logged_in(); },
    ]);

    register_rest_route('vmp/v1', '/vendor/draft', [
        'methods' => 'GET',
        'callback' => function() {
            $user_id = get_current_user_id();
            if (!$user_id) return new WP_REST_Response(['error' => 'Unauthorized'], 401);
            $repo = new \VMP\Modules\VendorRegistration\Repositories\WpVendorRequestRepository();
            $existing = $repo->findByUser($user_id);
            if (!$existing) return new WP_REST_Response(['draft' => null]);
            return new WP_REST_Response(['draft' => json_decode($existing->draft_data, true)]);
        },
        'permission_callback' => function() { return is_user_logged_in(); },
    ]);
});
