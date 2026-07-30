<?php
namespace VMP\Modules\VendorRegistration\Services;

use VMP\Modules\VendorRegistration\Config\Capabilities;

class PermissionService
{
    /**
     * Centralized permission check for vendor reviews.
     */
    public static function canManageVendorRequests(): bool
    {
        // Allow super admins on multisite
        if (function_exists('is_multisite') && is_multisite() && function_exists('is_super_admin') && is_super_admin()) {
            return true;
        }

        if (function_exists('current_user_can') && current_user_can(Capabilities::MANAGE_VENDOR_REQUESTS)) {
            return true;
        }

        // fallback to manage_options for admins if capability wasn't provisioned yet
        if (function_exists('current_user_can') && current_user_can('manage_options')) {
            return true;
        }

        return false;
    }
}
