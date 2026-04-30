<?php
// models/NotificationModel.php

require_once ROOT_PATH . '/models/BaseModel.php';

class NotificationModel extends BaseModel {
    protected string $table = 'notifications';

    public function send(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void {
        $this->db->insert(
            "INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)",
            [$userId, $title, $message, $type, $link]
        );
    }

    public function sendBulk(array $userIds, string $title, string $message, string $type = 'info'): void {
        foreach ($userIds as $uid) {
            $this->send($uid, $title, $message, $type);
        }
    }

    public function getForUser(int $userId, bool $unreadOnly = false): array {
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        if ($unreadOnly) $sql .= " AND is_read = 0";
        $sql .= " ORDER BY created_at DESC LIMIT 50";
        return $this->db->fetchAll($sql, [$userId]);
    }

    public function countUnread(int $userId): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    public function markRead(int $notifId, int $userId): void {
        $this->db->execute(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
            [$notifId, $userId]
        );
    }

    public function markAllRead(int $userId): void {
        $this->db->execute(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ?",
            [$userId]
        );
    }
}
