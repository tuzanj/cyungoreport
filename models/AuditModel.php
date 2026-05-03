<?php
// models/AuditModel.php

require_once ROOT_PATH . '/models/BaseModel.php';

class AuditModel extends BaseModel {
    protected string $table = 'audit_logs';

    public function log(string $action, ?string $tableName = null, ?int $recordId = null,
                        ?array $oldValues = null, ?array $newValues = null): void {
        $userId = isLoggedIn() ? currentUserId() : null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        try {
            $this->db->insert(
                "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $userId, $action, $tableName, $recordId,
                    $oldValues ? json_encode($oldValues) : null,
                    $newValues ? json_encode($newValues) : null,
                    $ip, $ua
                ]
            );
        } catch (Throwable $e) {
            error_log("Audit Log Error: " . $e->getMessage());
        }
    }

    public function getRecent(int $limit = 50): array {
        $limit = max(1, min(500, $limit));
        return $this->db->fetchAll(
            "SELECT al.*, u.username FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC LIMIT {$limit}"
        );
    }

    public function getByUser(int $userId, int $limit = 30): array {
        $limit = max(1, min(500, $limit));
        return $this->db->fetchAll(
            "SELECT * FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT {$limit}",
            [$userId]
        );
    }
}
