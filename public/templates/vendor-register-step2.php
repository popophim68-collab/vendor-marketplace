<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * قالب الخطوة 2: بيانات المتجر
 *
 * @package VMP\Templates
 * @var string $step_key      مفتاح الخطوة الحالي
 * @var array  $form_data     بيانات النموذج المحفوظة
 * @var array  $errors        أخطاء التحقق
 * @var array  $settings      إعدادات الإضافة
 * @var int    $default_plan_id خطة الاشتراك الافتراضية
 */

$step_key   = $step_key ?? 'step2';
$form_data  = $form_data ?? [];
$errors     = $errors ?? [];
$settings   = $settings ?? get_option('vmp_settings', []);
$default_plan_id = $default_plan_id ?? 0;

// قيم النموذج
$store_name        = $form_data['store_name'] ?? '';
$store_slug        = $form_data['store_slug'] ?? '';
$store_description = $form_data['store_description'] ?? '';
$store_address     = $form_data['store_address'] ?? '';
$store_phone       = $form_data['store_phone'] ?? '';
$store_email       = $form_data['store_email'] ?? '';
$whatsapp_number   = $form_data['whatsapp_number'] ?? '';
$store_logo        = $form_data['store_logo'] ?? 0;
$store_banner      = $form_data['store_banner'] ?? 0;
$license_file      = $form_data['license_file'] ?? 0;
$plan_id           = isset($form_data['plan_id']) ? (int) $form_data['plan_id'] : $default_plan_id;

// دالة مساعدة لجلب خطأ حقل معين
$get_error = function ($field) use ($errors) {
    return isset($errors[$field]) ? '<span class="vmp-field-error">' . esc_html($errors[$field]) . '</span>' : '';
};

$has_error = function ($field) use ($errors) {
    return isset($errors[$field]) ? ' vmp-has-error' : '';
};

// صفحة الأحكام والشروط - استخدام الإعدادات الصحيحة
$terms_page_id = $settings['registration']['terms_page_url'] ?? $settings['terms_page_url'] ?? 0;
$terms_url = $terms_page_id ? get_permalink($terms_page_id) : home_url('/terms/');

// Nonce للخطوة
wp_nonce_field('vmp_vendor_register_step2', 'vmp_step2_nonce');
?>

<div class="vmp-step-content" data-step="2" style="display: none;">
    <h2 class="vmp-step-title"><?php esc_html_e('بيانات المتجر', 'vmp'); ?></h2>
    <p class="vmp-step-desc"><?php esc_html_e('أدخل معلومات متجرك التي ستظهر للعملاء.', 'vmp'); ?></p>

    <div class="vmp-form-group<?php echo $has_error('store_name'); ?>">
        <label for="vmp_store_name"><?php esc_html_e('اسم المتجر', 'vmp'); ?> <span class="required">*</span></label>
        <input type="text" id="vmp_store_name" name="store_name" class="vmp-input" value="<?php echo esc_attr($store_name); ?>" required maxlength="100" autocomplete="organization">
        <?php echo $get_error('store_name'); ?>
        <span class="vmp-input-hint"><?php esc_html_e('سيظهر هذا الاسم للعملاء في متجرك.', 'vmp'); ?></span>
    </div>

    <div class="vmp-form-group<?php echo $has_error('store_slug'); ?>">
        <label for="vmp_store_slug"><?php esc_html_e('رابط المتجر (Slug)', 'vmp'); ?> <span class="required">*</span></label>
        <div class="vmp-slug-wrapper" style="display:flex; align-items:center; direction:ltr;">
            <span class="vmp-slug-prefix" style="background:var(--vmp-bg); padding:11px 14px; border:1.5px solid var(--vmp-border); border-right:none; border-radius:6px 0 0 6px; font-size:13px; color:var(--vmp-text-muted);">
                <?php echo esc_url(home_url('/store/')); ?>
            </span>
            <input type="text" id="vmp_store_slug" name="store_slug" class="vmp-input" value="<?php echo esc_attr($store_slug); ?>" required maxlength="60" pattern="[a-z0-9-]+" style="border-radius:0 6px 6px 0; direction:ltr;" autocomplete="off">
        </div>
        <?php echo $get_error('store_slug'); ?>
        <span class="vmp-input-hint"><?php esc_html_e('أحرف إنجليزية صغيرة، أرقام، وشرطات فقط. سيكون رابط متجرك: yoursite.com/store/your-slug', 'vmp'); ?></span>
        <div class="vmp-slug-status" style="display:none; margin-top:6px; font-size:13px;"></div>
    </div>

    <div class="vmp-form-group<?php echo $has_error('store_description'); ?>">
        <label for="vmp_store_description"><?php esc_html_e('وصف المتجر', 'vmp'); ?></label>
        <textarea id="vmp_store_description" name="store_description" class="vmp-textarea" rows="4" maxlength="500"><?php echo esc_textarea($store_description); ?></textarea>
        <?php echo $get_error('store_description'); ?>
        <span class="vmp-input-hint"><?php printf(esc_html__('وصف مختصر لمتجرك (حد أقصى %d حرف).', 'vmp'), 500); ?></span>
        <div class="vmp-char-count" style="text-align:left; font-size:12px; color:var(--vmp-text-muted); margin-top:4px;">
            <span id="vmp_desc_count">0</span> / 500
        </div>
    </div>

    <div class="vmp-form-group<?php echo $has_error('store_address'); ?>">
        <label for="vmp_store_address"><?php esc_html_e('عنوان المتجر', 'vmp'); ?> <span class="required">*</span></label>
        <textarea id="vmp_store_address" name="store_address" class="vmp-textarea" rows="3" required autocomplete="street-address"><?php echo esc_textarea($store_address); ?></textarea>
        <?php echo $get_error('store_address'); ?>
        <span class="vmp-input-hint"><?php esc_html_e('العنوان الكامل للمتجر (يظهر للعملاء).', 'vmp'); ?></span>
    </div>

    <div class="vmp-form-grid">
        <div class="vmp-form-group<?php echo $has_error('store_phone'); ?>">
            <label for="vmp_store_phone"><?php esc_html_e('رقم الهاتف (للتواصل/واتساب)', 'vmp'); ?> <span class="required">*</span></label>
            <input type="tel" id="vmp_store_phone" name="store_phone" class="vmp-input" value="<?php echo esc_attr($store_phone); ?>" required dir="ltr" placeholder="+966500000000" autocomplete="tel">
            <?php echo $get_error('store_phone'); ?>
            <span class="vmp-input-hint"><?php esc_html_e('سيظهر في صفحة المتجر للتواصل المباشر. أدخل الرقم مع رمز الدولة.', 'vmp'); ?></span>
        </div>

        <div class="vmp-form-group<?php echo $has_error('store_email'); ?>">
            <label for="vmp_store_email"><?php esc_html_e('بريد المتجر الإلكتروني', 'vmp'); ?></label>
            <input type="email" id="vmp_store_email" name="store_email" class="vmp-input" value="<?php echo esc_attr($store_email); ?>" autocomplete="email">
            <?php echo $get_error('store_email'); ?>
            <span class="vmp-input-hint"><?php esc_html_e('بريد إلكتروني للتواصل مع المتجر (اختياري).', 'vmp'); ?></span>
        </div>
    </div>

    <div class="vmp-form-group<?php echo $has_error('whatsapp_number'); ?>">
        <label for="vmp_whatsapp_number"><?php esc_html_e('رقم واتساب', 'vmp'); ?></label>
        <input type="tel" id="vmp_whatsapp_number" name="whatsapp_number" class="vmp-input" value="<?php echo esc_attr($whatsapp_number); ?>" dir="ltr" placeholder="+966500000000" autocomplete="tel">
        <?php echo $get_error('whatsapp_number'); ?>
        <span class="vmp-input-hint"><?php esc_html_e('للطلبات عبر واتساب (اختياري، إذا كان مختلفاً عن رقم الهاتف).', 'vmp'); ?></span>
    </div>

    <div class="vmp-form-group">
        <label><?php esc_html_e('ملفات الميديا', 'vmp'); ?></label>
        
        <div class="vmp-media-upload-grid">
            <div class="vmp-media-upload-item">
                <label class="vmp-media-upload-label">
                    <span class="dashicons dashicons-format-image" aria-hidden="true"></span>
                    <span><?php esc_html_e('شعار المتجر', 'vmp'); ?></span>
                    <span class="vmp-media-hint">(اختياري، 512×512 بكسل كحد أقصى)</span>
                </label>
                <div class="vmp-media-preview" data-field="store_logo">
                    <?php if ($store_logo) : ?>
                        <img src="<?php echo esc_url(wp_get_attachment_url($store_logo)); ?>" alt="" style="max-width:100px; max-height:100px;">
                    <?php endif; ?>
                </div>
                <input type="hidden" name="store_logo" id="vmp_store_logo" value="<?php echo esc_attr($store_logo); ?>">
                <button type="button" class="vmp-btn vmp-btn-outline vmp-media-upload-btn" data-field="store_logo" data-type="logo">
                    <?php echo $store_logo ? esc_html__('تغيير', 'vmp') : esc_html__('اختيار صورة', 'vmp'); ?>
                </button>
                <button type="button" class="vmp-btn vmp-btn-outline vmp-media-remove-btn" data-field="store_logo" style="display:<?php echo $store_logo ? 'inline-flex' : 'none'; ?>;">
                    <span class="dashicons dashicons-trash" aria-hidden="true"></span> <?php esc_html_e('إزالة', 'vmp'); ?>
                </button>
            </div>

            <div class="vmp-media-upload-item">
                <label class="vmp-media-upload-label">
                    <span class="dashicons dashicons-cover-image" aria-hidden="true"></span>
                    <span><?php esc_html_e('غلاف المتجر', 'vmp'); ?></span>
                    <span class="vmp-media-hint">(اختياري، 1920×600 بكسل)</span>
                </label>
                <div class="vmp-media-preview" data-field="store_banner">
                    <?php if ($store_banner) : ?>
                        <img src="<?php echo esc_url(wp_get_attachment_url($store_banner)); ?>" alt="" style="max-width:200px; max-height:80px;">
                    <?php endif; ?>
                </div>
                <input type="hidden" name="store_banner" id="vmp_store_banner" value="<?php echo esc_attr($store_banner); ?>">
                <button type="button" class="vmp-btn vmp-btn-outline vmp-media-upload-btn" data-field="store_banner" data-type="banner">
                    <?php echo $store_banner ? esc_html__('تغيير', 'vmp') : esc_html__('اختيار صورة', 'vmp'); ?>
                </button>
                <button type="button" class="vmp-btn vmp-btn-outline vmp-media-remove-btn" data-field="store_banner" style="display:<?php echo $store_banner ? 'inline-flex' : 'none'; ?>;">
                    <span class="dashicons dashicons-trash" aria-hidden="true"></span> <?php esc_html_e('إزالة', 'vmp'); ?>
                </button>
            </div>

            <div class="vmp-media-upload-item">
                <label class="vmp-media-upload-label">
                    <span class="dashicons dashicons-id" aria-hidden="true"></span>
                    <span><?php esc_html_e('رخصة تجارية', 'vmp'); ?></span>
                    <span class="vmp-media-hint">(اختياري، PDF أو صورة)</span>
                </label>
                <div class="vmp-media-preview" data-field="license_file">
                    <?php if ($license_file) : ?>
                        <span class="dashicons dashicons-media-document" style="font-size:48px;"></span>
                    <?php endif; ?>
                </div>
                <input type="hidden" name="license_file" id="vmp_license_file" value="<?php echo esc_attr($license_file); ?>">
                <button type="button" class="vmp-btn vmp-btn-outline vmp-media-upload-btn" data-field="license_file" data-type="license">
                    <?php echo $license_file ? esc_html__('تغيير', 'vmp') : esc_html__('اختيار ملف', 'vmp'); ?>
                </button>
                <button type="button" class="vmp-btn vmp-btn-outline vmp-media-remove-btn" data-field="license_file" style="display:<?php echo $license_file ? 'inline-flex' : 'none'; ?>;">
                    <span class="dashicons dashicons-trash" aria-hidden="true"></span> <?php esc_html_e('إزالة', 'vmp'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- شروط الاستخدام -->
    <div class="vmp-form-group vmp-terms-group<?php echo $has_error('terms_accepted'); ?>">
        <label class="vmp-checkbox-label">
            <input type="checkbox" name="terms_accepted" value="1" required>
            <span class="vmp-checkbox-text">
                <?php printf(esc_html__('أوافق على %sالأحكام والشروط%s وسياسة الخصوصية', 'vmp'), '<a href="' . esc_url($terms_url) . '" target="_blank" rel="noopener">', '</a>'); ?>
                <span class="required">*</span>
            </span>
        </label>
        <?php echo $get_error('terms_accepted'); ?>
    </div>

    <input type="hidden" name="plan_id" value="<?php echo esc_attr($plan_id); ?>">

    <div class="vmp-form-actions">
        <button type="button" class="vmp-btn vmp-btn-outline vmp-btn-prev" data-prev="1">
            <span class="dashicons dashicons-arrow-right-alt2" style="margin-left: 6px;"></span>
            <span><?php esc_html_e('السابق', 'vmp'); ?></span>
        </button>
        <button type="button" class="vmp-btn vmp-btn-primary vmp-btn-next" data-next="3">
            <span><?php esc_html_e('التالي', 'vmp'); ?></span>
            <span class="dashicons dashicons-arrow-left-alt2" style="margin-right: 6px;"></span>
        </button>
    </div>
</div>
