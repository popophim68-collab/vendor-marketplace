<?php
/**
 * Activation hook: run migrations and add vendor role & capabilities
 */
register_activation_hook(__FILE__, function() {
    // Run migration files in Database/Migrations
    foreach (glob(__DIR__ . '/Database/Migrations/*.php') as $file) {
        include_once $file;
    }

    // Add vendor role if not exists
    if (!get_role('vendor')) {
        add_role('vendor', 'Vendor', [
            'read' => true,
        ]);
    }

    // Add custom capability for managing vendor requests
    $cap = 'manage_vmp_requests';
    $admin = get_role('administrator');
    if ($admin && !$admin->has_cap($cap)) {
        $admin->add_cap($cap);
    }

    // Optionally grant to shop manager (WooCommerce) if present
    $shop_manager = get_role('shop_manager');
    if ($shop_manager && !$shop_manager->has_cap($cap)) {
        $shop_manager->add_cap($cap);
    }

    // Flush rewrite rules
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function() {
    // Optionally remove capability on deactivation
    $cap = 'manage_vmp_requests';
    $admin = get_role('administrator');
    if ($admin && $admin->has_cap($cap)) {
        $admin->remove_cap($cap);
    }
    $shop_manager = get_role('shop_manager');
    if ($shop_manager && $shop_manager->has_cap($cap)) {
        $shop_manager->remove_cap($cap);
    }

    flush_rewrite_rules();
});
