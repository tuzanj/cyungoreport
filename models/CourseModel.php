<?php
// models/CourseModel.php

require_once ROOT_PATH . '/models/BaseModel.php';

class CourseModel extends BaseModel {
    protected string $table = 'courses';

    public function create(array $data): int {
        return $this->db->insert(
            "INSERT INTO courses (code, name, description, type, credits, module_weight, trade_id) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$data['code'], $data['name'], $data['description'] ?? null, $data['type'] ?? 'specific', $data['credits'] ?? 3, $data['module_weight'] ?? 0, $data['trade_id'] ?? null]
        );
    }

    public function update(int $id, array $data): int {
        return $this->db->execute(
            "UPDATE courses SET code=?, name=?, description=?, type=?, credits=?, module_weight=?, trade_id=? WHERE id=?",
            [$data['code'], $data['name'], $data['description'] ?? null, $data['type'], $data['credits'], $data['module_weight'], $data['trade_id'] ?? null, $id]
        );
    }

    public function codeExists(string $code, int $excludeId = 0): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM courses WHERE code = ? AND id != ?",
            [$code, $excludeId]
        );
        return (bool)$row;
    }

    public function getAllWithTrade(): array {
        return $this->db->fetchAll(
            "SELECT c.*, d.name as trade_name FROM courses c
             LEFT JOIN trades d ON d.id = c.trade_id
             ORDER BY c.name"
        );
    }

    public function getAllWithDept(): array {
        return $this->getAllWithTrade();
    }

    public function setGradingCriteria(int $courseId, int $yearId, array $data): void {
        $existing = $this->db->fetchOne(
            "SELECT id FROM grading_criteria WHERE course_id = ? AND academic_year_id = ?",
            [$courseId, $yearId]
        );
        if ($existing) {
            $this->db->execute(
                "UPDATE grading_criteria SET assignments_weight=?, quizzes_weight=?, midterm_weight=?, final_weight=?, passing_score=?
                 WHERE course_id=? AND academic_year_id=?",
                [$data['assignments_weight'], $data['quizzes_weight'], $data['midterm_weight'], $data['final_weight'], $data['passing_score'], $courseId, $yearId]
            );
        } else {
            $this->db->insert(
                "INSERT INTO grading_criteria (course_id, academic_year_id, assignments_weight, quizzes_weight, midterm_weight, final_weight, passing_score)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$courseId, $yearId, $data['assignments_weight'], $data['quizzes_weight'], $data['midterm_weight'], $data['final_weight'], $data['passing_score']]
            );
        }
    }

    public function getGradingCriteria(int $courseId, int $yearId): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM grading_criteria WHERE course_id = ? AND academic_year_id = ?",
            [$courseId, $yearId]
        );
    }
}
