<?php
namespace VMP\Modules\VendorRegistration\Repositories;

use WP_Error;

class WpVendorRequestRepository implements VendorRequestRepositoryInterface {
    private \wpdb $wpdb;
    private string $table_requests;
    private string $table_logs;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_requests = $wpdb->prefix . 'vmp_vendor_requests';
        $this->table_logs = $wpdb->prefix . 'vmp_vendor_request_logs';
    }

    public function find(int $id): ?object {
        $row = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_requests} WHERE id = %d", $id));
        return $row ?: null;
    }

    public function findByUser(int $userId): ?object {
        $row = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_requests} WHERE user_id = %d ORDER BY created_at DESC LIMIT 1", $userId));
        return $row ?: null;
    }

    public function create(array $data): object {
        $defaults = [
            'user_id' => 0,
            'status' => 'draft',
            'draft_data' => null,
            'email' => null,
            'username' => null,
            'first_name' => null,
            'last_name' => null,
        ];
        $data = array_merge($defaults, $data);
        $inserted = $this->wpdb->insert($this->table_requests, $data);
        if ($inserted === false) {
            throw new \RuntimeException('DB insert failed');
        }
        return $this->find((int)$this->wpdb->insert_id);
    }

    public function update(int $id, array $data): bool {
        $updated = $this->wpdb->update($this->table_requests, $data, ['id' => $id]);
        return $updated !== false;
    }

    public function updateStatus(int $id, string $status, ?string $reason = null): bool {
        $this->wpdb->query('START TRANSACTION');
        $request = $this->find($id);
        $from = $request->status ?? '';
        $ok = $this->update($id, ['status' => $status]);
        $this->logTransition($id, $from, $status, get_current_user_id(), $reason);
        if ($ok) {
            $this->wpdb->query('COMMIT');
            return true;
        }
        $this->wpdb->query('ROLLBACK');
        return false;
    }

    public function logTransition(int $requestId, string $from, string $to, int $changedBy = 0, ?string $reason = null): void {
        $this->wpdb->insert($this->table_logs, [
            'request_id' => $requestId,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $changedBy,
            'reason' => $reason,
            'metadata' => null,
        ]);
    }
}
