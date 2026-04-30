<?php
// models/ParentModel.php

require_once ROOT_PATH . '/models/BaseModel.php';

class ParentModel extends BaseModel {
    protected string $table = 'parents';

    public function findByUserId(int $userId): ?array {
        return $this->db->fetchOne("SELECT * FROM parents WHERE user_id = ?", [$userId]);
    }

    public function create(array $data): int {
        return $this->db->insert(
            "INSERT INTO parents (user_id, first_name, last_name, relationship, phone, email, address) VALUES (?,?,?,?,?,?,?)",
            [$data['user_id'], $data['first_name'], $data['last_name'], $data['relationship'] ?? 'guardian',
             $data['phone'] ?? null, $data['email'] ?? null, $data['address'] ?? null]
        );
    }

    public function linkStudent(int $parentId, int $studentId, bool $isPrimary = false): void {
        $exists = $this->db->fetchOne(
            "SELECT id FROM parent_student WHERE parent_id=? AND student_id=?",
            [$parentId, $studentId]
        );
        if (!$exists) {
            $this->db->insert(
                "INSERT INTO parent_student (parent_id, student_id, is_primary) VALUES (?,?,?)",
                [$parentId, $studentId, $isPrimary ? 1 : 0]
            );
        }
    }

    public function getChildren(int $parentId): array {
        return $this->db->fetchAll(
            "SELECT s.*, u.email, u.username,
                    c.name as class_name, ay.name as year_name
             FROM parent_student ps
             JOIN students s ON s.id = ps.student_id
             JOIN users u ON u.id = s.user_id
             LEFT JOIN enrollments e ON e.student_id = s.id AND e.status='active'
             LEFT JOIN classes c ON c.id = e.class_id
             LEFT JOIN academic_years ay ON ay.id = e.academic_year_id
             WHERE ps.parent_id = ?
             ORDER BY s.first_name",
            [$parentId]
        );
    }

    public function getChildReport(int $studentId, int $yearId): array {
        return $this->db->fetchAll(
            "SELECT m.*, c.name as course_name, c.code
             FROM marks m
             JOIN class_courses cc ON cc.id = m.class_course_id
             JOIN courses c ON c.id = cc.course_id
             WHERE m.student_id = ? AND cc.academic_year_id = ? AND m.status = 'published'
             ORDER BY c.name",
            [$studentId, $yearId]
        );
    }
}
