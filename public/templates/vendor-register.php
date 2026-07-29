<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * القالب الرئيسي لتسجيل البائعين متعدد الخطوات
 * متوافق مع VendorRegistrationController
 *
 * @package VMP\Templates
 * @since 2.0.0
 */

// ── استخدام الحاوية للحصول على المستودعات ──
$container = \VMP\Core\Container::getInstance();
$plan_repo = $container->make(\VMP\Repositories\SubscriptionPlanRepository::class);
$vendor_repo = $container->make(\VMP\Contracts\VendorRepositoryInterface::class);

// ── التحقق مما إذا كان المستخدم مسجلاً بالفعل ──
$current_user_id = is_user_logged_in() ? get_current_user_id() : 0;
$vendor = null;
$redirect_url = '';

// إذا كان المستخدم مسجلاً بالفعل
if ($current_user_id) {
    $vendor = $vendor_repo->findByUserId($current_user_id);

    // إذا كان المستخدم لديه حساب بائع بالفعل
    if ($vendor) {
        $settings = get_option('vmp_settings', []);
        $dashboard_page_id = !empty($settings['display']['dashboard_page']) ? (int) $settings['display']['dashboard_page'] : 0;
        $redirect_url = $dashboard_page_id && get_post($dashboard_page_id) ? get_permalink($dashboard_page_id) : home_url('/vendor-dashboard/');
        ?>
        <script>
            window.location.href = '<?php echo esc_js($redirect_url); ?>';
        </script>
        <div class="vmp-notice vmp-notice-info">
            <?php _e('جاري تحويلك إلى لوحة البائع...', 'vmp'); ?>
        </div>
        <?php
        return;
    }
}

// ── جلب خطط الاشتراك النشطة ──
$plans = $plan_repo->getAll(true);

// ── التحقق من وجود خطة مجانية كافتراضية ──
$default_plan_id = 0;
foreach ($plans as $plan) {
    if ((float) $plan->price == 0) {
        $default_plan_id = (int) $plan->id;
        break;
    }
}
if ($default_plan_id === 0 && !empty($plans)) {
    $default_plan_id = (int) $plans[0]->id;
}

// الحصول على رابط صفحة الأحكام من الإعدادات (fallback لصفحة /terms/)
$settings = get_option('vmp_settings', []);
$terms_page_id = !empty($settings['registration']['terms_page_url']) ? (int) $settings['registration']['terms_page_url'] : 
                 (!empty($settings['display']['terms_page']) ? (int) $settings['display']['terms_page'] : 0);
$terms_url = $terms_page_id && get_post($terms_page_id) ? get_permalink($terms_page_id) : home_url('/terms/');

// ── المتغيرات المشتركة للقوالب ──
$template_vars = compact(
    'form_data', 'errors', 'current_user', 'settings', 
    'plans', 'default_plan_id', 'terms_url', 'current_step'
);

$step1_vars = array_merge($template_vars, [
    'step_key' => 'step1',
    'current_user' => wp_get_current_user(),
    'is_logged_in' => is_user_logged_in(),
]);
$step2_vars = array_merge($template_vars, [
    'step_key' => 'step2',
]);
$step3_vars = array_merge($template_vars, [
    'step_key' => 'step3',
]);

?>

<div class="vmp-wrap vmp-register-wrap">
    <div class="vmp-container" style="max-width: 800px;">
        
        <header class="vmp-header-bar" style="text-align: center; margin-bottom: 32px;">
            <h1><?php _e('انضم إلينا كبائع', 'vmp'); ?></h1>
            <p><?php _e('قم بإنشاء متجرك الخاص وابدأ البيع في دقائق.', 'vmp'); ?></p>
        </header>

        <div class="vmp-card">
            <!-- مسار التقدم (Progress Steps) -->
            <nav class="vmp-progress-steps" aria-label="<?php esc_attr_e('خطوات التسجيل', 'vmp'); ?>">
                <div class="vmp-progress-step<?php echo ($current_step ?? 1) >= 1 ? ' active' : ''; ?><?php echo ($current_step ?? 1) > 1 ? ' completed' : ''; ?>" data-step="1">
                    <span class="vmp-step-circle" aria-hidden="true">1</span>
                    <span class="vmp-step-label"><?php esc_html_e('الحساب', 'vmp'); ?></span>
                    <span class="vmp-step-desc"><?php esc_html_e('البيانات الأساسية', 'vmp'); ?></span>
                </div>
                <div class="vmp-progress-line" aria-hidden="true"></div>
                <div class="vmp-progress-step<?php echo ($current_step ?? 1) >= 2 ? ' active' : ''; ?><?php echo ($current_step ?? 1) > 2 ? ' completed' : ''; ?>" data-step="2">
                    <span class="vmp-step-circle" aria-hidden="true">2</span>
                    <span class="vmp-step-label"><?php esc_html_e('المتجر', 'vmp'); ?></span>
                    <span class="vmp-step-desc"><?php esc_html_e('بيانات المتجر', 'vmp'); ?></span>
                </div>
                <div class="vmp-progress-line" aria-hidden="true"></div>
                <div class="vmp-progress-step<?php echo ($current_step ?? 1) >= 3 ? ' active' : ''; ?>" data-step="3">
                    <span class="vmp-step-circle" aria-hidden="true">3</span>
                    <span class="vmp-step-label"><?php esc_html_e('الخطة', 'vmp'); ?></span>
                    <span class="vmp-step-desc"><?php esc_html_e('خطة الاشتراك', 'vmp'); ?></span>
                </div>
            </nav>

            <form id="vmp-register-form" class="vmp-ajax-form vmp-multi-step-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="POST" novalidate>
                <?php 
                // Nonce رئيسي للنموذج
                wp_nonce_field('vmp_vendor_register', 'vmp_register_nonce'); 
                ?>
                <input type="hidden" name="action" value="vmp_vendor_register">
                <input type="hidden" name="vmp_current_step" id="vmp_current_step" value="<?php echo esc_attr($current_step ?? 1); ?>">

                <!-- الخطوة 1: البيانات الأساسية -->
                <?php 
                extract($step1_vars); 
                include VMP_PLUGIN_DIR . 'public/templates/vendor-register-step1.php';
                ?>

                <!-- الخطوة 2: بيانات المتجر -->
                <?php 
                extract($step2_vars); 
                include VMP_PLUGIN_DIR . 'public/templates/vendor-register-step2.php';
                ?>

                <!-- الخطوة 3: خطة الاشتراك -->
                <?php 
                extract($step3_vars); 
                include VMP_PLUGIN_DIR . 'public/templates/vendor-register-step3.php';
                ?>
            </form>
        </div>

        <!-- رسالة نجاح (تظهر بعد الإرسال الناجح) -->
        <div id="vmp-success-message" class="vmp-card vmp-success-message" style="display: none; text-align: center; padding: 48px 24px;">
            <div class="vmp-success-icon" aria-hidden="true">
                <span class="dashicons dashicons-yes-alt" style="font-size: 64px; width: 64px; height: 64px; color: var(--vmp-success);"></span>
            </div>
            <h2><?php esc_html_e('تم إرسال طلبك بنجاح!', 'vmp'); ?></h2>
            <p id="vmp_success_text"><?php esc_html_e('سيتم مراجعة طلبك والتواصل معك خلال 24-48 ساعة.', 'vmp'); ?></p>
            <div id="vmp_success_actions" style="margin-top: 24px;"></div>
        </div>

        <!-- رسالة خطأ (تظهر عند فشل الإرسال) -->
        <div id="vmp-error-message" class="vmp-card vmp-error-message" style="display: none; text-align: center; padding: 32px 24px; border-color: var(--vmp-error);">
            <div class="vmp-error-icon" aria-hidden="true">
                <span class="dashicons dashicons-dismiss" style="font-size: 48px; width: 48px; height: 48px; color: var(--vmp-error);"></span>
            </div>
            <h2><?php esc_html_e('حدث خطأ', 'vmp'); ?></h2>
            <p id="vmp_error_text"></p>
            <button type="button" class="vmp-btn vmp-btn-primary" id="vmp_retry_btn" style="margin-top: 16px;"><?php esc_html_e('إعادة المحاولة', 'vmp'); ?></button>
        </div>
    </div>
</div>

<!-- JavaScript مضمّن للتفاعل الفوري (سيتم استبداله بالملف المنفصل) -->
<script>
(function() {
    'use strict';
    
    // بيانات التهيئة للملف JS الرئيسي
    window.vmpRegisterData = {
        ajaxUrl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
        nonce: '<?php echo esc_js(wp_create_nonce('vmp_vendor_registration_nonce')); ?>',
        currentStep: <?php echo (int) ($current_step ?? 1); ?>,
        totalSteps: 3,
        isLoggedIn: <?php echo is_user_logged_in() ? 'true' : 'false'; ?>,
        strings: {
            next: '<?php echo esc_js(__('التالي', 'vmp')); ?>',
            prev: '<?php echo esc_js(__('السابق', 'vmp')); ?>',
            submit: '<?php echo esc_js(__('إرسال طلب التسجيل', 'vmp')); ?>',
            submitting: '<?php echo esc_js(__('جاري الإرسال...', 'vmp')); ?>',
            slugTaken: '<?php echo esc_js(__('رابط المتجر مستخدم بالفعل', 'vmp')); ?>',
            slugAvailable: '<?php echo esc_js(__('رابط المتجر متاح', 'vmp')); ?>',
            checking: '<?php echo esc_js(__('جاري التحقق...', 'vmp')); ?>',
            passwordMismatch: '<?php echo esc_js(__('كلمتا المرور غير متطابقتين', 'vmp')); ?>',
            passwordWeak: '<?php echo esc_js(__('كلمة المرور ضعيفة جداً', 'vmp')); ?>',
            passwordMedium: '<?php echo esc_js(__('كلمة المرور متوسطة', 'vmp')); ?>',
            passwordStrong: '<?php echo esc_js(__('كلمة المرور قوية', 'vmp')); ?>',
            error: '<?php echo esc_js(__('حدث خطأ', 'vmp')); ?>',
        }
    };
})();
</script>
