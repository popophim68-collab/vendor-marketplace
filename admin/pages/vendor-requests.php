<?php
/** Admin page: Vendor Requests (skeleton) */
add_action('admin_menu', function() {
    add_menu_page('Vendor Requests', 'Vendor Requests', 'manage_options', 'vmp-vendor-requests', function() {
        echo '<div class="wrap"><h1>Vendor Requests</h1>';
        echo '<div id="vmp-requests-root"></div>';
        echo '</div>';
    }, 'dashicons-store');
});
