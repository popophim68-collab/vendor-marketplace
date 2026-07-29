<?php
namespace VMP\Database\Migrations;

defined('ABSPATH') || exit;

class CreateVendorTables
{
    public static function up(): void
    {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = [];
        $prefix = $wpdb->prefix;

        $sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}vmp_vendor_requests (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            store_name VARCHAR(191) NOT NULL,
            store_slug VARCHAR(191) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY store_slug (store_slug)
        ) $charset;";

        $sql[] = "CREATE TABLE IF NOT EXISTS {$prefix}vmp_vendors (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            store_name VARCHAR(191) NOT NULL,
            store_slug VARCHAR(191) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY store_slug (store_slug)
        ) $charset;";

        foreach ($sql as $query) {
            dbDelta($query);
        }
    }
}
