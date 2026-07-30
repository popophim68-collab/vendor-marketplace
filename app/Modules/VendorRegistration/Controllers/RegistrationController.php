<?php
namespace VMP\Modules\VendorRegistration\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use VMP\Modules\VendorRegistration\DTOs\NewVendorDTO;
use VMP\Modules\VendorRegistration\Repositories\WpVendorRequestRepository;
use VMP\Modules\VendorRegistration\Services\RegistrationService;

class RegistrationController {
    private RegistrationService $service;

    // file upload constraints
    private const MAX_LICENSE_SIZE = 5242880; // 5MB
    private const ALLOWED_LICENSE_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    public function __construct() {
        $repo = new WpVendorRequestRepository();
        $this->service = new RegistrationService($repo);
    }

    public function register(WP_REST_Request $request): WP_REST_Response {
        // legacy / generic register endpoint (JSON)
        $params = $request->get_json_params();
        $dto = new NewVendorDTO($params);
        // Basic server-side validation should run here
        $created = $this->service->register($dto);
        return new WP_REST_Response(['success' => true, 'request' => $created], 201);
    }

    /**
     * Validate uploaded license file array from $_FILES
     */
    private function validateLicenseFile(array $file): ?\WP_Error
    {
        if (empty($file) || empty($file['name'])) return null; // nothing to validate

        if (!isset($file['size']) || !isset($file['type']) || !isset($file['tmp_name'])) {
            return new \WP_Error('invalid_file', 'Invalid file upload');
        }

        if ($file['size'] > self::MAX_LICENSE_SIZE) {
            return new \WP_Error('file_too_large', 'License file exceeds maximum allowed size of 5MB');
        }

        // use PHP finfo for reliable mime type detection when available
        $detected = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
            }
        }
        // fallback to provided type
        $mime = $detected ?: $file['type'];

        if (!in_array($mime, self::ALLOWED_LICENSE_MIMES, true)) {
            return new \WP_Error('invalid_mime', 'Unsupported license file type. Allowed types: PDF, JPG, PNG');
        }

        return null;
    }

    /**
     * Handle guest registration (creates WP user and vendor request)
     * Accepts multipart/form-data including file upload 'license_document'
     */
    public function registerGuest(WP_REST_Request $request): WP_REST_Response {
        // nonce check
        if (!isset($_POST['vmp_register_guest_nonce']) || !wp_verify_nonce($_POST['vmp_register_guest_nonce'], 'vmp_register_guest')) {
            return new WP_REST_Response(['error' => 'Invalid nonce'], 400);
        }

        $first = sanitize_text_field($_POST['first_name'] ?? '');
        $last = sanitize_text_field($_POST['last_name'] ?? '');
        $username = sanitize_user($_POST['username'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $country = sanitize_text_field($_POST['country'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $accept = isset($_POST['accept_terms']) && $_POST['accept_terms'] == '1';

        if (!$accept) return new WP_REST_Response(['error' => 'You must accept terms'], 400);
        if (empty($first) || empty($last) || empty($username) || empty($email) || empty($password)) {
            return new WP_REST_Response(['error' => 'Missing required fields'], 400);
        }
        if (!is_email($email)) return new WP_REST_Response(['error' => 'Invalid email'], 400);
        if (username_exists($username) || email_exists($email)) {
            return new WP_REST_Response(['error' => 'User or email already exists'], 409);
        }

        // validate uploaded license file before creating user
        if (!empty($_FILES['license_document']) && !empty($_FILES['license_document']['name'])) {
            $err = $this->validateLicenseFile($_FILES['license_document']);
            if (is_wp_error($err)) {
                return new WP_REST_Response(['error' => $err->get_error_message()], 400);
            }
        }

        // create WP user
        $user_id = wp_create_user($username, $password, $email);
        if (is_wp_error($user_id)) {
            return new WP_REST_Response(['error' => $user_id->get_error_message()], 500);
        }
        wp_update_user(['ID' => $user_id, 'first_name' => $first, 'last_name' => $last]);

        // handle file upload safely after validation
        $license_url = null;
        if (!empty($_FILES['license_document']) && !empty($_FILES['license_document']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attachment_id = media_handle_upload('license_document', 0);
            if (is_wp_error($attachment_id)) {
                // continue but log
                error_log('License upload failed: ' . $attachment_id->get_error_message());
            } else {
                $license_url = wp_get_attachment_url($attachment_id);
            }
        }

        // create vendor request
        $repo = new WpVendorRequestRepository();
        $data = [
            'user_id' => $user_id,
            'first_name' => $first,
            'last_name' => $last,
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
            'country' => $country,
            'license_document' => $license_url,
            'status' => 'submitted',
        ];
        $requestObj = $repo->create($data);

        // send notification emails
        wp_mail($email, __('تم استلام طلبك', 'vmp'), __('تم استلام طلب تسجيلك كبائع وسيتم مراجعته من قبل المشرف.', 'vmp'));

        // notify admins with capability
        $admins = get_users(['role__in' => ['administrator', 'shop_manager']]);
        foreach ($admins as $admin) {
            if (user_can($admin, 'manage_vmp_requests')) {
                wp_mail($admin->user_email, __('طلب بائع جديد', 'vmp'), sprintf(__('تم استلام طلب بائع جديد من %s. راجع لوحة التحكم.' , 'vmp'), $username));
            }
        }

        return new WP_REST_Response(['success' => true, 'message' => __('تم إرسال طلبك، سنتواصل معك عبر البريد.', 'vmp')]);
    }

    /**
     * Handle apply for existing logged-in user
     */
    public function apply(WP_REST_Request $request): WP_REST_Response {
        if (!is_user_logged_in()) return new WP_REST_Response(['error' => 'Unauthorized'], 401);
        if (!isset($_POST['vmp_register_apply_nonce']) || !wp_verify_nonce($_POST['vmp_register_apply_nonce'], 'vmp_register_apply')) {
            return new WP_REST_Response(['error' => 'Invalid nonce'], 400);
        }
        $user_id = get_current_user_id();
        $first = sanitize_text_field($_POST['first_name'] ?? '');
        $last = sanitize_text_field($_POST['last_name'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $country = sanitize_text_field($_POST['country'] ?? '');
        $accept = isset($_POST['accept_terms']) && $_POST['accept_terms'] == '1';
        if (!$accept) return new WP_REST_Response(['error' => 'You must accept terms'], 400);

        // validate uploaded license file before processing
        if (!empty($_FILES['license_document']) && !empty($_FILES['license_document']['name'])) {
            $err = $this->validateLicenseFile($_FILES['license_document']);
            if (is_wp_error($err)) {
                return new WP_REST_Response(['error' => $err->get_error_message()], 400);
            }
        }

        // handle file
        $license_url = null;
        if (!empty($_FILES['license_document']) && !empty($_FILES['license_document']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attachment_id = media_handle_upload('license_document', 0);
            if (is_wp_error($attachment_id)) {
                error_log('License upload failed: ' . $attachment_id->get_error_message());
            } else {
                $license_url = wp_get_attachment_url($attachment_id);
            }
        }

        $repo = new WpVendorRequestRepository();
        $existing = $repo->findByUser($user_id);
        $data = [
            'first_name' => $first,
            'last_name' => $last,
            'phone' => $phone,
            'country' => $country,
            'license_document' => $license_url,
            'status' => 'submitted',
        ];
        if ($existing) {
            $repo->update($existing->id, $data);
        } else {
            $data['user_id'] = $user_id;
            $repo->create($data);
        }

        // send emails
        $user = get_userdata($user_id);
        wp_mail($user->user_email, __('تم استلام طلبك', 'vmp'), __('تم استلام طلب ترقية حسابك وسيتم مراجعته.', 'vmp'));
        $admins = get_users(['role__in' => ['administrator', 'shop_manager']]);
        foreach ($admins as $admin) {
            if (user_can($admin, 'manage_vmp_requests')) {
                wp_mail($admin->user_email, __('طلب بائع جديد', 'vmp'), sprintf(__('المستخدم %s قدم طلب ترقية إلى بائع. راجع لوحة التحكم.' , 'vmp'), $user->user_login));
            }
        }

        return new WP_REST_Response(['success' => true, 'message' => __('تم إرسال طلبك، سنتواصل معك عبر البريد.', 'vmp')]);
    }

    public function saveDraft(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response(['error' => 'Unauthorized'], 401);
        }
        $params = $request->get_json_params();
        $repo = new WpVendorRequestRepository();
        $existing = $repo->findByUser($user_id);
        $data = ['draft_data' => wp_json_encode($params)];
        if ($existing) {
            $repo->update($existing->id, $data);
            return new WP_REST_Response(['success' => true]);
        }
        $data = array_merge($data, ['user_id' => $user_id, 'status' => 'draft']);
        $repo->create($data);
        return new WP_REST_Response(['success' => true], 201);
    }
}
