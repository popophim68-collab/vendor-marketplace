<?php
namespace VMP\Modules\VendorRegistration\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use VMP\Modules\VendorRegistration\Repositories\StoreSetupSessionRepository;
use VMP\Modules\VendorRegistration\Repositories\StoreSetupSessionRepositoryInterface;
use VMP\Modules\VendorRegistration\Services\ActivityLogService;

class UploadController
{
    private StoreSetupSessionRepositoryInterface $sessionsRepo;
    private ActivityLogService $logger;

    public function __construct(StoreSetupSessionRepositoryInterface $sessionsRepo, ActivityLogService $logger)
    {
        $this->sessionsRepo = $sessionsRepo;
        $this->logger = $logger;
    }

    public function uploadLogo(WP_REST_Request $request): WP_REST_Response
    {
        return $this->handleUpload($request, 'logo');
    }

    public function uploadBanner(WP_REST_Request $request): WP_REST_Response
    {
        return $this->handleUpload($request, 'banner');
    }

    public function deleteLogo(WP_REST_Request $request): WP_REST_Response
    {
        return $this->handleDelete($request, 'logo');
    }

    public function deleteBanner(WP_REST_Request $request): WP_REST_Response
    {
        return $this->handleDelete($request, 'banner');
    }

    private function handleUpload(WP_REST_Request $request, string $which): WP_REST_Response
    {
        // Permission
        if (!is_user_logged_in()) return new WP_REST_Response(['success'=>false,'error'=>'unauthenticated'], 401);

        $session_uuid = $request->get_header('X-Session-UUID') ?: $request->get_param('session_uuid');
        if (!$session_uuid) return new WP_REST_Response(['success'=>false,'error'=>'session_required'], 400);

        $session = $this->sessionsRepo->findByUuid($session_uuid);
        if (!$session) return new WP_REST_Response(['success'=>false,'error'=>'session_not_found'], 404);

        // Ownership
        $current = get_current_user_id();
        if ((int)$session->user_id !== (int)$current && !current_user_can('manage_vmp_requests')) {
            return new WP_REST_Response(['success'=>false,'error'=>'forbidden'], 403);
        }

        $files = $request->get_file_params();
        if (empty($files) || empty($files['file'])) return new WP_REST_Response(['success'=>false,'error'=>'no_file'], 400);

        $file = $files['file'];
        // validate mime
        $allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($file['type'], $allowed_mimes, true)) return new WP_REST_Response(['success'=>false,'error'=>'invalid_mime'], 422);

        // size limit: 5MB
        $max = 5 * 1024 * 1024;
        if ($file['size'] > $max) return new WP_REST_Response(['success'=>false,'error'=>'file_too_large'], 413);

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $overrides = ['test_form'=>false];
        $movefile = wp_handle_upload($file, $overrides);
        if (isset($movefile['error'])) return new WP_REST_Response(['success'=>false,'error'=>'upload_failed','details'=>$movefile['error']], 500);

        $filename = $movefile['file'];
        $filetype = wp_check_filetype_and_ext($filename, $movefile['url']);

        // Create attachment
        $attachment = [
            'post_mime_type' => $filetype['type'] ?? $file['type'],
            'post_title' => sanitize_file_name(basename($filename)),
            'post_content' => '',
            'post_status' => 'inherit'
        ];
        $attach_id = wp_insert_attachment($attachment, $filename);
        if (is_wp_error($attach_id) || !$attach_id) {
            return new WP_REST_Response(['success'=>false,'error'=>'attach_failed'], 500);
        }

        // Generate metadata (which will create sizes)
        $meta = wp_generate_attachment_metadata($attach_id, $filename);
        wp_update_attachment_metadata($attach_id, $meta);

        // Try to strip EXIF by re-saving via image editor (best effort)
        $editor = wp_get_image_editor($filename);
        if (!is_wp_error($editor)) {
            $saved = $editor->save($filename);
            if (!is_wp_error($saved)) {
                // regenerate metadata
                $meta = wp_generate_attachment_metadata($attach_id, $filename);
                wp_update_attachment_metadata($attach_id, $meta);
            }
        }

        // Update session payload: store attachment id under payload.branding.logo or banner
        $payload = json_decode($session->payload, true) ?: [];
        $payload['branding'] = $payload['branding'] ?? [];
        if ($which === 'logo') {
            // remove old attachment if exists
            if (!empty($payload['branding']['logo'])) { wp_delete_attachment((int)$payload['branding']['logo'], true); }
            $payload['branding']['logo'] = $attach_id;
        } else {
            if (!empty($payload['branding']['banner'])) { wp_delete_attachment((int)$payload['branding']['banner'], true); }
            $payload['branding']['banner'] = $attach_id;
        }

        // save payload via repository
        $this->sessionsRepo->saveStep((int)$session->id, (int)$session->current_step ?: 2, ['branding' => $payload['branding']]);

        // Audit log
        $this->logger->log((int)get_current_user_id(), ucfirst($which) . ' uploaded', ['attachment_id' => $attach_id, 'session_id' => $session->id]);

        $res = [
            'success' => true,
            'attachment_id' => $attach_id,
            'url' => wp_get_attachment_url($attach_id),
            'sizes' => [],
        ];
        if (!empty($meta['sizes'])) {
            foreach ($meta['sizes'] as $size => $data) {
                $res['sizes'][$size] = wp_get_attachment_image_url($attach_id, $size);
            }
        }

        return new WP_REST_Response($res, 201);
    }

    private function handleDelete(WP_REST_Request $request, string $which): WP_REST_Response
    {
        if (!is_user_logged_in()) return new WP_REST_Response(['success'=>false,'error'=>'unauthenticated'], 401);
        $session_uuid = $request->get_header('X-Session-UUID') ?: $request->get_param('session_uuid');
        if (!$session_uuid) return new WP_REST_Response(['success'=>false,'error'=>'session_required'], 400);
        $session = $this->sessionsRepo->findByUuid($session_uuid);
        if (!$session) return new WP_REST_Response(['success'=>false,'error'=>'session_not_found'], 404);
        $current = get_current_user_id();
        if ((int)$session->user_id !== (int)$current && !current_user_can('manage_vmp_requests')) {
            return new WP_REST_Response(['success'=>false,'error'=>'forbidden'], 403);
        }

        $payload = json_decode($session->payload, true) ?: [];
        if (empty($payload['branding'][$which])) return new WP_REST_Response(['success'=>false,'error'=>'not_found'], 404);
        $att = (int)$payload['branding'][$which];
        // delete attachment
        wp_delete_attachment($att, true);
        // remove from payload
        unset($payload['branding'][$which]);
        $this->sessionsRepo->saveStep((int)$session->id, (int)$session->current_step ?: 2, ['branding' => $payload['branding']]);

        $this->logger->log($current, ucfirst($which) . ' deleted', ['attachment_id'=>$att, 'session_id'=>$session->id]);

        return new WP_REST_Response(['success'=>true], 200);
    }
}
