<?php
// Admin requests REST routes
add_action('rest_api_init', function() {
    $ns = 'vmp/v1';

    register_rest_route($ns, '/admin/requests', [
        'methods' => 'GET',
        'callback' => function(\WP_REST_Request $request) {
            $controller = new \VMP\Modules\VendorRegistration\Controllers\AdminRequestsController();
            return $controller->listRequests($request);
        },
        'permission_callback' => function() { return current_user_can('manage_vmp_requests'); },
    ]);

    register_rest_route($ns, '/admin/requests/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => function(\WP_REST_Request $request) {
            $controller = new \VMP\Modules\VendorRegistration\Controllers\AdminRequestsController();
            return $controller->getRequest($request);
        },
        'permission_callback' => function() { return current_user_can('manage_vmp_requests'); },
    ]);
});
