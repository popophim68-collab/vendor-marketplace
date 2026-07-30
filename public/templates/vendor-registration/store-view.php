<?php
/**
 * Store view template
 * Expects query var vmp_store_slug to be present
 */
use VMP\Modules\VendorRegistration\Repositories\WpVendorStoreRepository;

$slug = get_query_var('vmp_store_slug');
if (empty($slug)) {
    status_header(404);
    echo '<h1>Store not found</h1>';
    return;
}

$repo = new WpVendorStoreRepository();
$store = $repo->findBySlug($slug);
if (!$store) {
    status_header(404);
    echo '<h1>Store not found</h1>';
    return;
}

// render basic store page
?><div class="vmp-store-page">
  <h1 class="vmp-store-title"><?php echo esc_html($store->store_name ?? $store->store_slug); ?></h1>
  <?php if (!empty($store->logo)): ?>
    <div class="vmp-store-logo"><img src="<?php echo esc_url($store->logo); ?>" alt="<?php echo esc_attr($store->store_name ?? ''); ?>" /></div>
  <?php endif; ?>
  <?php if (!empty($store->description)): ?>
    <div class="vmp-store-description"><?php echo wp_kses_post(wpautop($store->description)); ?></div>
  <?php endif; ?>

  <div class="vmp-store-meta">
    <strong><?php _e('البائع', 'vmp'); ?>:</strong> <?php echo esc_html($store->vendor_id); ?><br />
    <strong><?php _e('الحالة', 'vmp'); ?>:</strong> <?php echo esc_html($store->is_active ? __('Active','vmp') : __('Inactive','vmp')); ?>
  </div>

  <div class="vmp-store-actions">
    <?php if (is_user_logged_in() && current_user_can('manage_vmp_requests')): ?>
      <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=vmp-vendor-requests')); ?>"><?php _e('Manage stores', 'vmp'); ?></a>
    <?php endif; ?>
  </div>

  <div class="vmp-store-products">
    <!-- Placeholder for products listing. Integrate with WooCommerce or product repository later. -->
    <p><?php _e('قسم المنتجات سيُعرض هنا لاحقًا.', 'vmp'); ?></p>
  </div>
</div>
