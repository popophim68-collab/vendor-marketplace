<?php
namespace VMP\Modules\VendorRegistration\Admin;

// Registers admin menu and page for Vendor Requests review
add_action('admin_menu', function() {
    add_menu_page(
        __('Vendor Marketplace', 'vmp'),
        __('Vendor Marketplace', 'vmp'),
        'manage_vmp_requests',
        'vmp_dashboard',
        '\VMP\Modules\VendorRegistration\Admin\render_requests_page',
        'dashicons-store',
        56
    );

    add_submenu_page('vmp_dashboard', __('Vendor Requests', 'vmp'), __('Vendor Requests', 'vmp'), 'manage_vmp_requests', 'vmp_requests', '\VMP\Modules\VendorRegistration\Admin\render_requests_page');
});

add_action('admin_enqueue_scripts', function($hook) {
    // only enqueue on our pages
    if (strpos($hook, 'vmp') === false) return;

    $base = plugin_dir_url(__FILE__) . '/../../../assets/admin/';
    wp_enqueue_style('vmp-admin-review', $base . 'css/review.css', [], '1.0');
    wp_enqueue_script('vmp-admin-review', $base . 'js/review.js', ['wp-api-fetch', 'jquery'], '1.0', true);

    // pass REST root and nonce
    wp_localize_script('vmp-admin-review', 'VMP_Admin_Settings', [
        'restRoot' => esc_url_raw(rest_url('vmp/v1')),
        'nonce' => wp_create_nonce('wp_rest'),
    ]);
});

function render_requests_page()
{
    ?>
    <div class="wrap vmp-admin-wrap">
      <h1><?php esc_html_e('Vendor Requests', 'vmp'); ?></h1>

      <div id="vmp-request-list" class="vmp-grid">
        <div class="vmp-col vmp-col--full">
          <div class="vmp-card">
            <div id="vmp-requests-table">Loading requests...</div>
          </div>
        </div>
      </div>

      <div id="vmp-request-detail-modal" class="vmp-modal" aria-hidden="true"></div>
    </div>
    <?php
}
