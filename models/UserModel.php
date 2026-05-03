<?php
// models/UserModel.php

require_once ROOT_PATH . '/models/BaseModel.php';

class UserModel extends BaseModel {
    protected string $table = 'users';

    public function findByUsername(string $username): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE username = ?",
            [$username]
        );
    }

    public function findByUsernameOrEmail(string $identifier): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE username = ? OR email = ?",
            [$identifier, $identifier]
        );
    }

    public function findByEmail(string $email): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
    }

    public function usernameExists(string $username, ?int $excludeUserId = null): bool {
        $query = "SELECT COUNT(*) as c FROM users WHERE username = ?";
        $params = [$username];
        if ($excludeUserId !== null) {
            $query .= " AND id <> ?";
            $params[] = $excludeUserId;
        }
        $result = $this->db->fetchOne($query, $params);
        return ($result['c'] ?? 0) > 0;
    }

    public function emailExists(string $email, ?int $excludeUserId = null): bool {
        $query = "SELECT COUNT(*) as c FROM users WHERE email = ?";
        $params = [$email];
        if ($excludeUserId !== null) {
            $query .= " AND id <> ?";
            $params[] = $excludeUserId;
        }
        $result = $this->db->fetchOne($query, $params);
        return ($result['c'] ?? 0) > 0;
    }

    public function createUser(array $data): int {
        $username = trim($data['username']);
        $email = trim($data['email']);
        return $this->db->insert(
            "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)",
            [$username, $email, password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]), $data['role']]
        );
    }

    public function incrementFailedAttempts(int $userId): void {
        $this->db->execute(
            "UPDATE users SET failed_attempts = failed_attempts + 1 WHERE id = ?",
            [$userId]
        );
    }

    public function lockAccount(int $userId, int $minutes): void {
        $this->db->execute(
            "UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE), failed_attempts = ? WHERE id = ?",
            [$minutes, MAX_LOGIN_ATTEMPTS, $userId]
        );
    }

    public function resetFailedAttempts(int $userId): void {
        $this->db->execute(
            "UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?",
            [$userId]
        );
    }

    public function isLocked(array $user): bool {
        if ($user['locked_until'] === null) return false;
        return strtotime($user['locked_until']) > time();
    }

    public function updatePassword(int $userId, string $newPassword): void {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->db->execute(
            "UPDATE users SET password_hash = ? WHERE id = ?",
            [$hash, $userId]
        );
    }

    public function updateStatus(int $userId, int $isActive): void {
        $this->db->execute(
            "UPDATE users SET is_active = ? WHERE id = ?",
            [$isActive, $userId]
        );
    }

    public function getAllWithRoles(): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.email, u.role, u.is_active, u.created_at,
                    COALESCE(t.first_name, s.first_name, p.first_name, 'N/A') as first_name,
                    COALESCE(t.last_name, s.last_name, p.last_name, '') as last_name
             FROM users u
             LEFT JOIN teachers t ON t.user_id = u.id
             LEFT JOIN students s ON s.user_id = u.id
             LEFT JOIN parents p ON p.user_id = u.id
             ORDER BY u.created_at DESC"
        );
    }

    public function createPasswordReset(int $userId): string {
        $token = bin2hex(random_bytes(32));
        $this->db->execute("DELETE FROM password_resets WHERE user_id = ?", [$userId]);
        $this->db->insert(
            "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))",
            [$userId, $token]
        );
        return $token;
    }

    public function findValidResetToken(string $token): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0",
            [$token]
        );
    }

    public function markResetTokenUsed(string $token): void {
        $this->db->execute("UPDATE password_resets SET used = 1 WHERE token = ?", [$token]);
    }
}
