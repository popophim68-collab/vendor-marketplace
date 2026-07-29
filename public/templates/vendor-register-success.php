<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * قالب نجاح التسجيل (يمكن استخدامه كصفحة منفصلة)
 *
 * @package VMP\Templates
 */

$request_id = isset($_GET['request_id']) ? absint($_GET['request_id']) : 0;
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending';

$messages = [
    'pending' => [
        'title' => __('تم استلام طلبك', 'vmp'),
        'icon'  => 'dashicons-clock',
        'color' => 'var(--vmp-warning)',
        'text'  => __('سيتم مراجعة طلبك والتواصل معك خلال 24-48 ساعة.', 'vmp'),
    ],
    'approved' => [
        'title' => __('تهانينا! تم قبول طلبك', 'vmp'),
        'icon'  => 'dashicons-yes-alt',
        'color' => 'var(--vmp-success)',
        'text'  => __('متجرك جاهز الآن. يمكنك الدخول إلى لوحة التحكم لبدء إضافة منتجاتك.', 'vmp'),
    ],
    'rejected' => [
        'title' => __('عذراً، تم رفض طلبك', 'vmp'),
        'icon'  => 'dashicons-dismiss',
        'color' => 'var(--vmp-error)',
        'text'  => __('يمكنك مراجعة الأسباب في البريد الإلكتروني أو التواصل مع الدعم.', 'vmp'),
    ],
];

$msg = $messages[$status] ?? $messages['pending'];
?>

<div class="vmp-wrap vmp-register-success-wrap">
    <div class="vmp-container" style="max-width: 600px; text-align: center;">
        <div class="vmp-card" style="padding: 48px 32px;">
            <div class="vmp-success-icon" style="font-size: 72px; width: 72px; height: 72px; color: <?php echo esc_attr($msg['color']); ?>; margin: 0 auto 24px;">
                <span class="dashicons <?php echo esc_attr($msg['icon']); ?>"></span>
            </div>
            <h1 style="margin-bottom: 16px;"><?php echo esc_html($msg['title']); ?></h1>
            <p style="font-size: 18px; color: var(--vmp-text-muted); margin-bottom: 32px;"><?php echo esc_html($msg['text']); ?></p>

            <?php if ($status === 'approved') : ?>
                <?php
                $settings = get_option('vmp_settings', []);
                $dashboard_page_id = !empty($settings['display']['dashboard_page']) ? (int) $settings['display']['dashboard_page'] : 0;
                $dashboard_url = $dashboard_page_id && get_post($dashboard_page_id) ? get_permalink($dashboard_page_id) : home_url('/vendor-dashboard/');
                ?>
                <a href="<?php echo esc_url($dashboard_url); ?>" class="vmp-btn vmp-btn-primary" style="padding: 14px 32px; font-size: 16px;">
                    <span class="dashicons dashicons-admin-generic" style="margin-right: 8px;"></span>
                    <?php esc_html_e('الذهاب إلى لوحة البائع', 'vmp'); ?>
                </a>
            <?php elseif ($status === 'pending') : ?>
                <button type="button" class="vmp-btn vmp-btn-outline" onclick="window.history.back()">
                    <?php esc_html_e('العودة', 'vmp'); ?>
                </button>
            <?php endif; ?>

            <?php if ($request_id) : ?>
                <p style="margin-top: 24px; font-size: 13px; color: var(--vmp-text-muted);">
                    <?php printf(esc_html__('رقم الطلب: %d', 'vmp'), $request_id); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>