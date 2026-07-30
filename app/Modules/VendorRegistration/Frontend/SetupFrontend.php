<?php
namespace VMP\Modules\VendorRegistration\Frontend;

class SetupFrontend
{
    public static function init(): void
    {
        add_action('init', [self::class, 'addRewriteRules']);
        add_filter('query_vars', [self::class, 'addQueryVars']);
        add_filter('template_include', [self::class, 'loadSetupTemplate']);
    }

    public static function addRewriteRules(): void
    {
        add_rewrite_rule('^vendor/store/setup/?$', 'index.php?vmp_store_setup=1', 'top');
    }

    public static function addQueryVars(array $vars): array
    {
        $vars[] = 'vmp_store_setup';
        return $vars;
    }

    public static function loadSetupTemplate(string $template): string
    {
        $flag = get_query_var('vmp_store_setup');
        if (empty($flag)) return $template;

        $tpl = defined('VMP_PLUGIN_DIR') ? trailingslashit(VMP_PLUGIN_DIR) . 'public/templates/vendor-registration/store-setup-wizard.php' : __DIR__ . '/../../../public/templates/vendor-registration/store-setup-wizard.php';
        if (file_exists($tpl)) return $tpl;

        return $template;
    }
}

add_action('plugins_loaded', function() {
    SetupFrontend::init();
});
