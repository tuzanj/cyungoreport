<?php
// models/BaseModel.php - Abstract base model

require_once ROOT_PATH . '/config/database.php';

abstract class BaseModel {
    protected Database $db;
    protected string $table = '';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array {
        return $this->db->fetchOne("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    public function findAll(string $orderBy = 'id DESC'): array {
        return $this->db->fetchAll("SELECT * FROM {$this->table} ORDER BY {$orderBy}");
    }

    public function delete(int $id): int {
        return $this->db->execute("DELETE FROM {$this->table} WHERE id = ?", [$id]);
    }

    public function count(string $where = '', array $params = []): int {
        $sql = "SELECT COUNT(*) as cnt FROM {$this->table}";
        if ($where) $sql .= " WHERE {$where}";
        $row = $this->db->fetchOne($sql, $params);
        return (int)($row['cnt'] ?? 0);
    }
}
