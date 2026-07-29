<?php
/**
 * Plugin Name: Vendor Marketplace
 * Description: Multi-vendor marketplace plugin with registration, onboarding, and vendor management.
 * Version: 1.0.0
 * Author: Your Team
 */

defined('ABSPATH') || exit;

// Autoload
require_once __DIR__ . '/vendor/autoload.php';

use VMP\Core\Application;

// Const
if (!defined('VMP_PLUGIN_URL')) {
    define('VMP_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('VMP_PLUGIN_PATH')) {
    define('VMP_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('VMP_PLUGIN_DIR')) {
    define('VMP_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('VMP_PLUGIN_FILE')) {
    define('VMP_PLUGIN_FILE', __FILE__);
}
if (!defined('VMP_PLUGIN_BASENAME')) {
    define('VMP_PLUGIN_BASENAME', plugin_basename(__FILE__));
}
if (!defined('VMP_VERSION')) {
    define('VMP_VERSION', '1.0.0');
}

// Boot
add_action('plugins_loaded', function () {
    // Load textdomain
    load_plugin_textdomain('vmp', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Boot plugin via Application (new architecture)
    $plugin = new Application(__FILE__);
    $plugin->boot();
});

// Activation hook
register_activation_hook(__FILE__, function () {
    $migration = __DIR__ . '/app/Database/Migrations/CreateVendorTables.php';
    if (file_exists($migration)) {
        require_once $migration;
        \VMP\Database\Migrations\CreateVendorTables::up();
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
