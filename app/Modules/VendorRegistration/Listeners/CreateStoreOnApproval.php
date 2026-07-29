<?php
namespace VMP\Modules\VendorRegistration\Listeners;

use VMP\Modules\VendorRegistration\Events\VendorApproved;
use VMP\Modules\VendorRegistration\Services\StateMachine;

class CreateStoreOnApproval {
    public function handle(VendorApproved $event): void {
        // Minimal skeleton: create store record in wp_vmp_vendor_stores
        global $wpdb;
        $table = $wpdb->prefix . 'vmp_vendor_stores';
        $vendorId = $event->request->user_id ?? 0;
        $slug = sanitize_title($event->request->username ?? 'vendor-' . $vendorId);
        $wpdb->insert($table, [
            'vendor_id' => $vendorId,
            'store_name' => $event->request->username ?? 'Store',
            'store_slug' => $slug,
            'store_url' => home_url('/vendor-store/' . $slug),
            'is_active' => 0,
        ]);
    }
}
