<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * قالب الخطوة 1: البيانات الأساسية (معلومات الحساب)
 *
 * @package VMP\Templates
 * @var string $step_key      مفتاح الخطوة الحالي
 * @var array  $form_data     بيانات النموذج المحفوظة (للاستئناف)
 * @var array  $errors        أخطاء التحقق
 * @var WP_User|false $current_user المستخدم الحالي إن وجد
 * @var array  $settings      إعدادات الإضافة
 */

// قيم افتراضية للمتغيرات إن لم تكن معرفة
$step_key    = $step_key ?? 'step1';
$form_data   = $form_data ?? [];
$errors      = $errors ?? [];
$current_user = $current_user ?? wp_get_current_user();
$settings    = $settings ?? get_option('vmp_settings', []);

// تحديد ما إذا كان المستخدم مسجلاً بالفعل
$is_logged_in = is_user_logged_in();
$user_email   = $is_logged_in ? $current_user->user_email : ($form_data['user_email'] ?? '');
$user_first   = $is_logged_in ? $current_user->first_name : ($form_data['first_name'] ?? '');
$user_last    = $is_logged_in ? $current_user->last_name : ($form_data['last_name'] ?? '');
$user_pass    = $form_data['user_pass'] ?? '';
$full_name    = $form_data['full_name'] ?? ($is_logged_in ? $current_user->display_name : '');

// دالة مساعدة لجلب خطأ حقل معين
$get_error = function ($field) use ($errors) {
    return isset($errors[$field]) ? '<span class="vmp-field-error">' . esc_html($errors[$field]) . '</span>' : '';
};

// دالة مساعدة للتحقق من وجود خطأ
$has_error = function ($field) use ($errors) {
    return isset($errors[$field]) ? ' vmp-has-error' : '';
};

// Nonce للخطوة
wp_nonce_field('vmp_vendor_register_step1', 'vmp_step1_nonce');
?>

<div class="vmp-step-content active" data-step="1">
    <h2 class="vmp-step-title"><?php esc_html_e('معلومات الحساب الأساسية', 'vmp'); ?></h2>
    <p class="vmp-step-desc"><?php esc_html_e('أدخل بيانات حسابك الأساسية. إذا كان لديك حساب بالفعل، سجل الدخول أولاً.', 'vmp'); ?></p>

    <div class="vmp-form-grid">
        <div class="vmp-form-group<?php echo $has_error('first_name'); ?>">
            <label for="vmp_first_name"><?php esc_html_e('الاسم الأول', 'vmp'); ?> <span class="required">*</span></label>
            <input type="text" id="vmp_first_name" name="first_name" class="vmp-input" value="<?php echo esc_attr($user_first); ?>" required autocomplete="given-name" >
            <?php echo $get_error('first_name'); ?>
            <?php if ($is_logged_in) : ?>
                <span class="vmp-input-hint"><?php esc_html_e('يمكنك تعديل الاسم، سيتم تحديثه في حسابك.', 'vmp'); ?></span>
            <?php endif; ?>
        </div>

        <div class="vmp-form-group<?php echo $has_error('last_name'); ?>">
            <label for="vmp_last_name"><?php esc_html_e('الاسم الأخير', 'vmp'); ?> <span class="required">*</span></label>
            <input type="text" id="vmp_last_name" name="last_name" class="vmp-input" value="<?php echo esc_attr($user_last); ?>" required autocomplete="family-name" >
            <?php echo $get_error('last_name'); ?>
            <?php if ($is_logged_in) : ?>
                <span class="vmp-input-hint"><?php esc_html_e('يمكنك تعديل الاسم، سيتم تحديثه في حسابك.', 'vmp'); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$is_logged_in) : ?>
        <div class="vmp-form-group<?php echo $has_error('user_email'); ?>">
            <label for="vmp_user_email"><?php esc_html_e('البريد الإلكتروني', 'vmp'); ?> <span class="required">*</span></label>
            <input type="email" id="vmp_user_email" name="user_email" class="vmp-input" value="<?php echo esc_attr($user_email); ?>" required autocomplete="email">
            <?php echo $get_error('user_email'); ?>
        </div>

        <div class="vmp-form-group<?php echo $has_error('user_pass'); ?>">
            <label for="vmp_user_pass"><?php esc_html_e('كلمة المرور', 'vmp'); ?> <span class="required">*</span></label>
            <div class="vmp-password-wrapper">
                <input type="password" id="vmp_user_pass" name="user_pass" class="vmp-input" value="<?php echo esc_attr($user_pass); ?>" required autocomplete="new-password" minlength="8">
                <button type="button" class="vmp-toggle-password" aria-label="<?php esc_attr_e('إظهار/إخفاء كلمة المرور', 'vmp'); ?>">
                    <span class="dashicons dashicons-visibility"></span>
                </button>
            </div>
            <?php echo $get_error('user_pass'); ?>
            <div class="vmp-password-strength" aria-live="polite"></div>
            <span class="vmp-input-hint"><?php esc_html_e('8 أحرف على الأقل، يفضل احتواؤها على حروف وأرقام ورموز.', 'vmp'); ?></span>
        </div>

        <div class="vmp-form-group<?php echo $has_error('user_pass_confirm'); ?>">
            <label for="vmp_user_pass_confirm"><?php esc_html_e('تأكيد كلمة المرور', 'vmp'); ?> <span class="required">*</span></label>
            <input type="password" id="vmp_user_pass_confirm" name="user_pass_confirm" class="vmp-input" required autocomplete="new-password">
            <?php echo $get_error('user_pass_confirm'); ?>
        </div>
    <?php else : ?>
        <div class="vmp-notice vmp-notice-info">
            <span class="dashicons dashicons-info"></span>
            <p><?php printf(esc_html__('أنت مسجل الدخول بالفعل كـ %s. سيتم ربط متجر البائع بهذا الحساب.', 'vmp'), '<strong>' . esc_html($current_user->display_name) . '</strong>'); ?></p>
        </div>
        <input type="hidden" name="user_id" value="<?php echo esc_attr($current_user->ID); ?>">
    <?php endif; ?>

    <!-- حقل الاسم الكامل (مخفي للمستخدمين غير المسجلين، يستخدم للتحديث عند المستخدمين المسجلين) -->
    <input type="hidden" name="full_name" value="<?php echo esc_attr($full_name); ?>">

    <div class="vmp-form-actions">
        <button type="button" class="vmp-btn vmp-btn-primary vmp-btn-next" data-next="2">
            <span><?php esc_html_e('التالي', 'vmp'); ?></span>
            <span class="dashicons dashicons-arrow-left-alt2" style="margin-right: 6px;"></span>
        </button>
    </div>
</div>
