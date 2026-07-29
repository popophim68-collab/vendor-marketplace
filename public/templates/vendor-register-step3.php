<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * قالب الخطوة 3: الشروط والأحكام + خطة الاشتراك
 *
 * @package VMP\Templates
 * @var string $step_key      مفتاح الخطوة الحالي
 * @var array  $form_data     بيانات النموذج المحفوظة
 * @var array  $errors        أخطاء التحقق
 * @var array  $plans         خطط الاشتراك المتاحة
 * @var int    $default_plan_id خطة الاشتراك الافتراضية
 * @var array  $settings      إعدادات الإضافة
 */

$step_key       = $step_key ?? 'step3';
$form_data      = $form_data ?? [];
$errors         = $errors ?? [];
$plans          = $plans ?? [];
$default_plan_id = $default_plan_id ?? 0;
$settings       = $settings ?? get_option('vmp_settings', []);

$selected_plan_id = isset($form_data['plan_id']) ? (int) $form_data['plan_id'] : $default_plan_id;
$terms_accepted   = !empty($form_data['terms_accepted']);

// دالة مساعدة لجلب خطأ حقل معين
$get_error = function ($field) use ($errors) {
    return isset($errors[$field]) ? '<span class="vmp-field-error">' . esc_html($errors[$field]) . '</span>' : '';
};

$has_error = function ($field) use ($errors) {
    return isset($errors[$field]) ? ' vmp-has-error' : '';
};

// صفحة الأحكام والشروط
$terms_page_id = $settings['registration']['terms_page_url'] ?? $settings['terms_page_url'] ?? 0;
$terms_url = $terms_page_id ? get_permalink($terms_page_id) : home_url('/terms/');

// دالة محلية لتنسيق السعر
$format_price_sar = function($amount) {
    $formatted = function_exists('wc_price') ? wc_price($amount) : number_format($amount, 2) . ' ر.س';
    $formatted = preg_replace('/\.00\s*<span/', ' <span', $formatted);
    return $formatted;
};

// تسميات المميزات العربية
$feature_labels = [
    'whatsapp_button'      => __('طلب عبر واتساب', 'vmp'),
    'store_address'        => __('عنوان المتجر مع خريطة', 'vmp'),
    'social_links'         => __('روابط التواصل الاجتماعي', 'vmp'),
    'product_video'        => __('فيديو تعريفي للمنتج', 'vmp'),
    'ai_product_generator'   => __('إنشاء منتج بالذكاء الاصطناعي', 'vmp'),
    'unlimited_products'   => __('منتجات غير محدودة', 'vmp'),
    'custom_domain'        => __('نطاق مخصص', 'vmp'),
    'advanced_analytics'   => __('تحليلات متقدمة', 'vmp'),
    'coupons'              => __('كوبونات خصم', 'vmp'),
    'trusted_badge'        => __('شارة موثوق', 'vmp'),
    'priority_support'     => __('دعم أولوية', 'vmp'),
    'api_access'           => __('وصول API', 'vmp'),
    'multi_vendor'         => __('متعدد البائعين', 'vmp'),
    'commission_management' => __('إدارة العمولات', 'vmp'),
];

// Nonce للخطوة
wp_nonce_field('vmp_vendor_register_step3', 'vmp_step3_nonce');
?>

<div class="vmp-step-content" data-step="3" style="display: none;">
    <h2 class="vmp-step-title"><?php esc_html_e('الموافقة على الشروط واختيار الخطة', 'vmp'); ?></h2>
    <p class="vmp-step-desc"><?php esc_html_e('راجع الشروط والأحكام واختر خطة الاشتراك المناسبة لمتجرك.', 'vmp'); ?></p>

    <!-- شروط الاستخدام -->
    <div class="vmp-form-group vmp-terms-group<?php echo $has_error('terms_accepted'); ?>">
        <div class="vmp-terms-box">
            <h4><?php esc_html_e('الشروط والأحكام', 'vmp'); ?></h4>
            <div class="vmp-terms-content" style="max-height: 300px; overflow-y: auto; padding: 16px; border: 1px solid var(--vmp-border); border-radius: 8px; background: var(--vmp-bg); margin-bottom: 16px;">
                <?php
                if ($terms_page_id) {
                    $terms_post = get_post($terms_page_id);
                    if ($terms_post) {
                        echo apply_filters('the_content', $terms_post->post_content);
                    } else {
                        echo '<p>' . esc_html__('صفحة الشروط والأحكام غير موجودة. يرجى مراجعة إعدادات الإضافة.', 'vmp') . '</p>';
                    }
                } else {
                    echo '<p>' . esc_html__('لم يتم تعيين صفحة الشروط والأحكام. يرجى مراجعة إعدادات الإضافة.', 'vmp') . '</p>';
                }
                ?>
            </div>
            <label class="vmp-checkbox-label vmp-checkbox-large">
                <input type="checkbox" name="terms_accepted" value="1" required <?php checked($terms_accepted); ?>>
                <span class="vmp-checkbox-text">
                    <?php printf(esc_html__('أقر بأنني قرأت ووافقت على %sالشروط والأحكام%s وسياسة الخصوصية', 'vmp'), '<a href="' . esc_url($terms_url) . '" target="_blank" rel="noopener">', '</a>'); ?>
                    <span class="required">*</span>
                </span>
            </label>
            <?php echo $get_error('terms_accepted'); ?>
        </div>
    </div>

    <!-- اختيار خطة الاشتراك -->
    <div class="vmp-form-group<?php echo $has_error('plan_id'); ?>">
        <h4 style="margin-bottom: 16px;"><?php esc_html_e('اختر خطة الاشتراك', 'vmp'); ?></h4>
        <?php if (empty($plans)) : ?>
            <div class="vmp-notice vmp-notice-info">
                <span class="dashicons dashicons-info"></span>
                <p><?php esc_html_e('لا توجد خطط اشتراك معرفة حالياً. يمكنك المتابعة بالتسجيل وسيتم تعيين الخطة الافتراضية.', 'vmp'); ?></p>
            </div>
            <input type="hidden" name="plan_id" value="0">
        <?php else : ?>
            <div class="vmp-plans-grid" role="radiogroup" aria-label="<?php esc_attr_e('اختر خطة الاشتراك', 'vmp'); ?>">
                <?php foreach ($plans as $i => $plan) : 
                    $plan_id = (int) $plan->id;
                    $is_selected = $plan_id === $selected_plan_id;
                    $is_free = (float) $plan->price === 0.0;
                    $is_popular = !empty($plan->is_popular) || (isset($plan->meta['is_popular']) && $plan->meta['is_popular']);
                    
                    // تحويل المميزات من JSON إلى مصفوفة
                    $features = is_string($plan->features) 
                        ? json_decode($plan->features, true) 
                        : (is_array($plan->features) ? $plan->features : []);
                    
                    // استخراج أسماء المميزات المفعلة فقط
                    $active_features = [];
                    if (is_array($features)) {
                        foreach ($features as $key => $value) {
                            if ($value === true || $value === 1 || $value === '1') {
                                $active_features[] = $key;
                            }
                        }
                    }
                    
                    // فترة الفوترة
                    $billing_period = $plan->billing_period ?? 'monthly';
                    $period_labels = [
                        'monthly'   => __('شهرياً', 'vmp'),
                        'month'     => __('شهرياً', 'vmp'),
                        'yearly'    => __('سنوياً', 'vmp'),
                        'year'      => __('سنوياً', 'vmp'),
                        'lifetime'  => __('مدى الحياة', 'vmp'),
                    ];
                    $period_label = $period_labels[$billing_period] ?? $billing_period;
                ?>
                    <label class="vmp-plan-card<?php echo $is_selected ? ' selected' : ''; ?><?php echo $is_popular ? ' popular' : ''; ?>" 
                           data-plan-id="<?php echo esc_attr($plan_id); ?>"
                           tabindex="0" role="radio" aria-checked="<?php echo $is_selected ? 'true' : 'false'; ?>">
                        
                        <?php if ($is_popular) : ?>
                            <span class="vmp-plan-badge"><?php esc_html_e('الأكثر شيوعاً', 'vmp'); ?></span>
                        <?php endif; ?>
                        
                        <div class="vmp-plan-header">
                            <h3 class="vmp-plan-name"><?php echo esc_html($plan->name); ?></h3>
                            <div class="vmp-plan-price">
                                <?php if ($is_free) : ?>
                                    <span class="vmp-price-free"><?php esc_html_e('مجاني', 'vmp'); ?></span>
                                <?php else : ?>
                                    <span class="vmp-price-amount"><?php echo $format_price_sar($plan->price); ?></span>
                                    <span class="vmp-price-period">/ <?php echo esc_html($period_label); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($plan->description)) : ?>
                            <p class="vmp-plan-desc"><?php echo esc_html($plan->description); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($active_features)) : ?>
                            <ul class="vmp-plan-features" aria-label="<?php esc_attr_e('مميزات الخطة', 'vmp'); ?>">
                                <?php foreach ($active_features as $feature_key) : 
                                    $label = $feature_labels[$feature_key] ?? $feature_key;
                                ?>
                                    <li>
                                        <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                                        <span><?php echo esc_html($label); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <input type="radio" name="plan_id" value="<?php echo esc_attr($plan_id); ?>" 
                               <?php checked($is_selected); ?> class="vmp-plan-radio" aria-hidden="true">
                        <span class="vmp-plan-check" aria-hidden="true">
                            <span class="dashicons dashicons-yes"></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php echo $get_error('plan_id'); ?>
        <?php endif; ?>
    </div>

    <div class="vmp-form-actions">
        <button type="button" class="vmp-btn vmp-btn-outline vmp-btn-prev" data-prev="2">
            <span class="dashicons dashicons-arrow-right-alt2" style="margin-left: 6px;"></span>
            <span><?php esc_html_e('السابق', 'vmp'); ?></span>
        </button>
        <button type="submit" class="vmp-btn vmp-btn-primary vmp-btn-submit" id="vmp_submit_btn">
            <span class="vmp-btn-text"><?php esc_html_e('إرسال طلب التسجيل', 'vmp'); ?></span>
            <span class="vmp-btn-loading" style="display:none;">
                <span class="dashicons dashicons-update spin" style="animation: spin 1s linear infinite;"></span>
                <?php esc_html_e('جاري الإرسال...', 'vmp'); ?>
            </span>
        </button>
    </div>
</div>

<style>
/* أنماط خاصة بالخطوة 3 */
.vmp-terms-box {
    border: 1px solid var(--vmp-border);
    border-radius: 10px;
    padding: 20px;
    background: var(--vmp-bg);
}

.vmp-terms-box h4 {
    margin: 0 0 12px;
    font-size: 16px;
    color: var(--vmp-text);
}

.vmp-terms-content {
    font-size: 14px;
    line-height: 1.7;
    color: var(--vmp-text);
}

.vmp-terms-content p {
    margin: 0 0 12px;
}

.vmp-terms-content h3, .vmp-terms-content h4, .vmp-terms-content h5 {
    margin: 16px 0 8px;
}

.vmp-terms-content ul, .vmp-terms-content ol {
    margin: 8px 0 8px 20px;
}

.vmp-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    font-size: 14px;
}

.vmp-checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: var(--vmp-primary);
    margin-top: 2px;
    flex-shrink: 0;
}

.vmp-checkbox-text {
    color: var(--vmp-text);
}

.vmp-checkbox-large .vmp-checkbox-text {
    font-size: 15px;
    line-height: 1.5;
}

.vmp-plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
    margin: 8px 0 24px;
}

.vmp-plan-card {
    position: relative;
    border: 2px solid var(--vmp-border);
    border-radius: 12px;
    padding: 24px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: var(--vmp-bg);
    display: flex;
    flex-direction: column;
}

.vmp-plan-card:hover {
    border-color: var(--vmp-primary);
    box-shadow: 0 4px 16px rgba(var(--vmp-primary-rgb), 0.1);
}

.vmp-plan-card.selected {
    border-color: var(--vmp-primary);
    box-shadow: 0 0 0 2px rgba(var(--vmp-primary-rgb), 0.2);
}

.vmp-plan-card.popular {
    border-color: var(--vmp-primary);
}

.vmp-plan-badge {
    position: absolute;
    top: -10px;
    right: 20px;
    background: var(--vmp-primary);
    color: white;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    white-space: nowrap;
}

.vmp-plan-header {
    margin-bottom: 12px;
}

.vmp-plan-name {
    margin: 0 0 8px;
    font-size: 18px;
    font-weight: 600;
    color: var(--vmp-text);
}

.vmp-plan-price {
    display: flex;
    align-items: baseline;
    gap: 4px;
}

.vmp-price-free {
    font-size: 24px;
    font-weight: 700;
    color: var(--vmp-success);
}

.vmp-price-amount {
    font-size: 28px;
    font-weight: 700;
    color: var(--vmp-text);
    line-height: 1;
}

.vmp-price-period {
    font-size: 14px;
    color: var(--vmp-text-muted);
}

.vmp-plan-desc {
    margin: 0 0 16px;
    font-size: 14px;
    color: var(--vmp-text-muted);
    line-height: 1.5;
}

.vmp-plan-features {
    list-style: none;
    padding: 0;
    margin: 0 0 20px;
    flex: 1;
}

.vmp-plan-features li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 8px 0;
    font-size: 13px;
    color: var(--vmp-text);
    border-bottom: 1px solid var(--vmp-border);
}

.vmp-plan-features li:last-child {
    border-bottom: none;
}

.vmp-plan-features .dashicons-yes-alt {
    color: var(--vmp-success);
    font-size: 14px;
    width: 14px;
    height: 14px;
    flex-shrink: 0;
    margin-top: 2px;
}

.vmp-plan-radio {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.vmp-plan-check {
    position: absolute;
    bottom: 16px;
    left: 16px;
    width: 24px;
    height: 24px;
    border: 2px solid var(--vmp-border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.vmp-plan-card.selected .vmp-plan-check {
    background: var(--vmp-primary);
    border-color: var(--vmp-primary);
    color: white;
}

.vmp-plan-check .dashicons-yes {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.vmp-btn-loading .spin {
    display: inline-block !important;
}

/* RTL Support */
[dir="rtl"] .vmp-plan-badge {
    right: auto;
    left: 20px;
}

[dir="rtl"] .vmp-plan-check {
    left: auto;
    right: 16px;
}
</style>
