<?php
namespace VMP\Http\Controllers\Ajax;

defined('ABSPATH') || exit;

use VMP\Services\VendorRegistrationService;
use VMP\DTO\RegisterVendorDTO;
use VMP\Http\Requests\RegisterVendorRequest;
use VMP\Core\Container;

/**
 * AjaxController — معالج طلبات AJAX لتسجيل البائعين
 *
 * يتعامل مع طلبات الخطوات الثلاث، التحقق من السلاگ، والبريد الإلكتروني
 *
 * @package VMP\Http\Controllers\Ajax
 * @since 2.0.0
 */
class AjaxController
{
    private VendorRegistrationService $registrationService;

    public function __construct()
    {
        $container = Container::getInstance();
        $this->registrationService = $container->make(VendorRegistrationService::class);
    }

    /**
     * تسجيل مسارات AJAX
     */
    public function registerAjaxActions(): void
    {
        // الخطوة 1: إنشاء الحساب
        add_action('wp_ajax_vmp_vendor_register_step1', [$this, 'handleStep1']);
        add_action('wp_ajax_nopriv_vmp_vendor_register_step1', [$this, 'handleStep1']);

        // الخطوة 2: بيانات المتجر
        add_action('wp_ajax_vmp_vendor_register_step2', [$this, 'handleStep2']);
        add_action('wp_ajax_nopriv_vmp_vendor_register_step2', [$this, 'handleStep2']);

        // الخطوة 3: الإرسال النهائي
        add_action('wp_ajax_vmp_vendor_register_step3', [$this, 'handleStep3']);
        add_action('wp_ajax_nopriv_vmp_vendor_register_step3', [$this, 'handleStep3']);

        // التحقق من توفر رابط المتجر (slug)
        add_action('wp_ajax_vmp_check_store_slug', [$this, 'checkStoreSlug']);
        add_action('wp_ajax_nopriv_vmp_check_store_slug', [$this, 'checkStoreSlug']);

        // التحقق من توفر البريد الإلكتروني
        add_action('wp_ajax_vmp_check_email', [$this, 'checkEmail']);
        add_action('wp_ajax_nopriv_vmp_check_email', [$this, 'checkEmail']);

        // رفع ملفات الميديا
        add_action('wp_ajax_vmp_upload_media', [$this, 'handleMediaUpload']);
        add_action('wp_ajax_nopriv_vmp_upload_media', [$this, 'handleMediaUpload']);

        // التحقق من حالة الطلب
        add_action('wp_ajax_vmp_check_request_status', [$this, 'checkRequestStatus']);
        add_action('wp_ajax_nopriv_vmp_check_request_status', [$this, 'checkRequestStatus']);
    }

    /**
     * معالجة الخطوة 1 (إنشاء الحساب)
     */
    public function handleStep1(): void
    {
        // التحقق من nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vmp_vendor_register_step1')) {
            $this->sendError(__('رمز التحقق غير صالح. يرجى تحديث الصفحة والمحاولة مرة أخرى.', 'vmp'));
            return;
        }

        // بناء بيانات الطلب
        $request = RegisterVendorRequest::fromPost('vmp_vendor_register_step1', 'vmp_step1_nonce');

        if (!$request->validate()) {
            $this->sendError($request->firstError(), [
                'errors' => $request->errors(),
                'step' => 1,
            ]);
            return;
        }

        $dto = $request->toDTO();
        $cleanData = $dto->toArray();
        
        // حفظ في الجلسة
        $this->saveToSession('step1', $cleanData);

        // إذا كان المستخدم مسجلاً، إضافة معرفه
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $this->saveToSession('user_id', $user->ID);
            $dto->user_id = $user->ID;
            
            // تحديث الاسم الكامل إذا تغير
            if (!empty($dto->full_name)) {
                $this->registrationService->updateUserFullName($user->ID, $dto->full_name);
            }
        }

        $this->sendSuccess([
            'message' => __('تم حفظ البيانات، جاري الانتقال...', 'vmp'),
            'redirect_to' => '#step2',
            'data' => $cleanData,
        ]);
    }

    /**
     * معالجة الخطوة 2 (بيانات المتجر)
     */
    public function handleStep2(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vmp_vendor_register_step2')) {
            $this->sendError(__('رمز التحقق غير صالح.', 'vmp'));
            return;
        }

        $step1Data = $this->getFromSession('step1');
        if (!$step1Data) {
            $this->sendError(__('انتهت صلاحية الجلسة، يرجى البدء من جديد.', 'vmp'), [
                'code' => 'session_expired',
                'step' => 1,
            ]);
            return;
        }

        // دمج بيانات الخطوة 1 مع الخطوة 2
        $allData = array_merge($step1Data, $_POST);
        
        $request = RegisterVendorRequest::fromPost('vmp_vendor_register_step2', 'vmp_step2_nonce');
        $request->data = $allData;

        if (!$request->validate()) {
            $this->sendError($request->firstError(), [
                'errors' => $request->errors(),
                'step' => 2,
            ]);
            return;
        }

        $dto = $request->toDTO();
        $cleanData = $dto->toArray();
        
        $this->saveToSession('step2', $cleanData);

        $this->sendSuccess([
            'message' => __('تم حفظ بيانات المتجر، جاري الانتقال...', 'vmp'),
            'redirect_to' => '#step3',
            'data' => $cleanData,
        ]);
    }

    /**
     * معالجة الخطوة 3 (الإرسال النهائي)
     */
    public function handleStep3(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vmp_vendor_register_step3')) {
            $this->sendError(__('رمز التحقق غير صالح.', 'vmp'));
            return;
        }

        $step1Data = $this->getFromSession('step1');
        $step2Data = $this->getFromSession('step2');

        if (!$step1Data || !$step2Data) {
            $this->sendError(__('انتهت صلاحية الجلسة، يرجى البدء من جديد.', 'vmp'), [
                'code' => 'session_expired',
                'step' => 1,
            ]);
            return;
        }

        // دمج جميع البيانات
        $allData = array_merge($step1Data, $step2Data, $_POST);
        $allData['user_id'] = $step1Data['user_id'] ?? 0;

        $request = RegisterVendorRequest::fromPost('vmp_vendor_register_step3', 'vmp_step3_nonce');
        $request->data = $allData;

        if (!$request->validate()) {
            $this->sendError($request->firstError(), [
                'errors' => $request->errors(),
                'step' => 3,
            ]);
            return;
        }

        $dto = $request->toDTO();
        $requestData = $dto->getRequestData();

        // إنشاء الطلب
        $requestId = $this->registrationService->createRequest($requestData);

        if (!$requestId) {
            $this->sendError(__('فشل إنشاء الطلب، يرجى المحاولة مرة أخرى.', 'vmp'));
            return;
        }

        // تنظيف الجلسة
        $this->clearRegisterSession();

        // رسالة النجاح
        $successMessage = apply_filters('vmp_vendor_register_success_message',
            $this->registrationService->getSetting('register_success_message',
                __('تم تقديم طلب انضمامك بنجاح! وهو الآن قيد المراجعة من قبل الإدارة.', 'vmp')
            ),
            $requestId
        );

        // رابط التوجيه
        $redirectUrl = $this->registrationService->getSetting('redirect_after_submit', home_url('/my-account/'));

        // إطلاق حدث للإشعارات
        do_action('vmp_vendor_request_submitted', $requestId, $dto->user_id, $requestData);

        $this->sendSuccess([
            'message' => $successMessage,
            'request_id' => $requestId,
            'redirect_to' => $redirectUrl,
        ]);
    }

    /**
     * التحقق من توفر رابط المتجر (slug)
     */
    public function checkStoreSlug(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vmp_vendor_check_slug')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
            return;
        }

        $slug = sanitize_title($_POST['slug'] ?? '');
        $excludeUserId = absint($_POST['exclude_user_id'] ?? 0);

        if (empty($slug)) {
            wp_send_json_error(['message' => __('slug فارغ', 'vmp')]);
            return;
        }

        if (strlen($slug) < 3) {
            wp_send_json_success([
                'available' => false,
                'slug' => $slug,
                'message' => __('الحد الأدنى 3 أحرف', 'vmp'),
            ]);
            return;
        }

        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            wp_send_json_success([
                'available' => false,
                'slug' => $slug,
                'message' => __('أحرف وأرقام وشرطات فقط', 'vmp'),
            ]);
            return;
        }

        $exists = $this->registrationService->slugExists($slug, $excludeUserId);

        wp_send_json_success([
            'available' => !$exists,
            'slug' => $slug,
            'message' => $exists ? __('الرابط مستخدم مسبقاً', 'vmp') : __('الرابط متاح', 'vmp'),
        ]);
    }

    /**
     * التحقق من توفر البريد الإلكتروني
     */
    public function checkEmail(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vmp_vendor_check_email')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
            return;
        }

        $email = sanitize_email($_POST['email'] ?? '');
        $excludeUserId = absint($_POST['exclude_user_id'] ?? 0);

        if (empty($email)) {
            wp_send_json_error(['message' => __('بريد إلكتروني فارغ', 'vmp')]);
            return;
        }

        if (!is_email($email)) {
            wp_send_json_success([
                'available' => false,
                'email' => $email,
                'message' => __('بريد إلكتروني غير صحيح', 'vmp'),
            ]);
            return;
        }

        $exists = $this->registrationService->emailExists($email, $excludeUserId);

        wp_send_json_success([
            'available' => !$exists,
            'email' => $email,
            'message' => $exists ? __('هذا البريد الإلكتروني مسجّل مسبقاً', 'vmp') : __('البريد الإلكتروني متاح', 'vmp'),
        ]);
    }

    /**
     * رفع ملفات الميديا (شعار، غلاف، رخصة)
     */
    public function handleMediaUpload(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vmp_vendor_media_upload')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
            return;
        }

        if (!isset($_FILES['file'])) {
            wp_send_json_error(['message' => __('لم يتم رفع ملف', 'vmp')]);
            return;
        }

        $type = sanitize_text_field($_POST['type'] ?? 'logo');
        $result = $this->registrationService->handleMediaUpload($_FILES['file'], $type);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }

        wp_send_json_success($result);
    }

    /**
     * التحقق من حالة طلب المستخدم الحالي
     */
    public function checkRequestStatus(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vmp_vendor_registration_nonce')) {
            wp_send_json_error(['code' => 'invalid_nonce'], 403);
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['code' => 'not_logged_in']);
            return;
        }

        $user = wp_get_current_user();
        $request = $this->registrationService->getPendingRequestByUser($user->ID);
        $vendor = $this->registrationService->getVendorByUser($user->ID);

        if ($vendor) {
            wp_send_json_success([
                'status' => 'approved',
                'vendor_id' => $vendor->id,
                'message' => $this->registrationService->getSetting('approval_message',
                    __('تهانينا! تم قبول طلبك وأصبحت بائعاً.', 'vmp')
                ),
            ]);
            return;
        }

        if ($request) {
            $message = ($request->status === 'rejected')
                ? $this->registrationService->getSetting('rejection_message',
                    __('تم رفض طلبك. السبب: ', 'vmp') . $request->admin_notes
                )
                : $this->registrationService->getSetting('pending_approval_message',
                    __('طلبك قيد المراجعة، يرجى الانتظار.', 'vmp')
                );

            wp_send_json_success([
                'status' => $request->status,
                'request_id' => $request->id,
                'message' => $message,
                'admin_notes' => $request->admin_notes ?? '',
            ]);
            return;
        }

        wp_send_json_success(['status' => 'none']);
    }

    /**
     * حفظ بيانات في الجلسة
     */
    private function saveToSession(string $key, mixed $value): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $prefix = 'vmp_vendor_reg_';
        $_SESSION[$prefix . $key] = $value;
    }

    /**
     * جلب بيانات من الجلسة
     */
    private function getFromSession(string $key): mixed
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $prefix = 'vmp_vendor_reg_';
        return $_SESSION[$prefix . $key] ?? null;
    }

    /**
     * مسح الجلسة
     */
    private function clearRegisterSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $prefix = 'vmp_vendor_reg_';
        foreach ($_SESSION as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                unset($_SESSION[$key]);
            }
        }
    }

    /**
     * إرسال استجابة نجاح
     */
    private function sendSuccess(array $data): void
    {
        wp_send_json_success($data);
    }

    /**
     * إرسال استجابة خطأ
     */
    private function sendError(string $message, array $extra = []): void
    {
        wp_send_json_error(array_merge([
            'message' => $message,
        ], $extra));
    }
}
