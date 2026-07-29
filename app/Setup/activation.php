<?php
/** Activation hook: run migrations and add vendor role */
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
});
