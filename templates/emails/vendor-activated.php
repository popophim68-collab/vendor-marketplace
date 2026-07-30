<?php
// Email template: vendor activated
// Variables available: $payloadForTemplate (array) containing keys described in design
// Expect: 'vendor','request','session','admin','review_url','wizard_url','status_url'
$req = $payloadForTemplate['request'] ?? null;
$vendor = $payloadForTemplate['vendor'] ?? null;
$admin = $payloadForTemplate['admin'] ?? null;
$wizard = $payloadForTemplate['wizard_url'] ?? '';
$review = $payloadForTemplate['review_url'] ?? '';
?>
<!doctype html>
<html>
  <body>
    <p>مرحبا <?php echo esc_html($vendor?->display_name ?? ''); ?>,</p>
    <p>لقد تم تفعيل طلب البائع رقم #<?php echo intval($req->id ?? 0); ?> بنجاح من قبل <?php echo esc_html($admin?->display_name ?? ''); ?>.</p>
    <?php if ($wizard): ?>
      <p>يمكنك متابعة إعداد المتجر من هنا: <a href="<?php echo esc_url($wizard); ?>"><?php echo esc_html($wizard); ?></a></p>
    <?php endif; ?>
    <p>للمراجعة عبر لوحة المشرف: <a href="<?php echo esc_url($review); ?>"><?php echo esc_html($review); ?></a></p>
    <p>مع تحيات فريقنا.</p>
  </body>
</html>
