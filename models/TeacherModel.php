<?php
// models/TeacherModel.php

require_once ROOT_PATH . '/models/BaseModel.php';

class TeacherModel extends BaseModel {
    protected string $table = 'teachers';

    public function findByUserId(int $userId): ?array {
        return $this->db->fetchOne("SELECT * FROM teachers WHERE user_id = ?", [$userId]);
    }

    public function create(array $data): int {
        return $this->db->insert(
            "INSERT INTO teachers (user_id, employee_id, first_name, last_name, gender, date_of_birth, phone, address, department_id, qualification, hire_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['user_id'], $data['employee_id'], $data['first_name'], $data['last_name'],
                $data['gender'], $data['date_of_birth'] ?? null, $data['phone'] ?? null,
                $data['address'] ?? null, $data['department_id'] ?? null,
                $data['qualification'] ?? null, $data['hire_date'] ?? date('Y-m-d')
            ]
        );
    }

    public function generateEmployeeId(): string {
        $count = $this->count();
        return 'EMP' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    public function getAllWithDept(): array {
        return $this->db->fetchAll(
            "SELECT t.*, u.username, u.email, u.is_active, d.name as department_name
             FROM teachers t
             JOIN users u ON u.id = t.user_id
             LEFT JOIN departments d ON d.id = t.department_id
             ORDER BY t.last_name, t.first_name"
        );
    }

    public function getAssignedCourses(int $teacherId, int $academicYearId): array {
        return $this->db->fetchAll(
            "SELECT cc.id as class_course_id, c.code, c.name as course_name, c.credits,
                    cl.name as class_name, cl.grade_level,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = cl.id AND e.academic_year_id = cc.academic_year_id AND e.status='active') as student_count
             FROM class_courses cc
             JOIN courses c ON c.id = cc.course_id
             JOIN classes cl ON cl.id = cc.class_id
             WHERE cc.teacher_id = ? AND cc.academic_year_id = ?",
            [$teacherId, $academicYearId]
        );
    }

    public function getStudentsInCourse(int $classCourseId): array {
        return $this->db->fetchAll(
            "SELECT s.id, s.student_id, s.first_name, s.last_name,
                    m.id as mark_id, m.assignments_score, m.quizzes_score,
                    m.midterm_score, m.final_score, m.calculated_grade,
                    m.letter_grade, m.status as mark_status, m.is_pass,
                    m.is_supplementary, m.supplementary_score, m.remarks
             FROM class_courses cc
             JOIN enrollments e ON e.class_id = cc.class_id AND e.academic_year_id = cc.academic_year_id
             JOIN students s ON s.id = e.student_id
             LEFT JOIN marks m ON m.student_id = s.id AND m.class_course_id = cc.id
             WHERE cc.id = ? AND e.status = 'active'
             ORDER BY s.last_name, s.first_name",
            [$classCourseId]
        );
    }

    public function update(int $id, array $data): int {
        return $this->db->execute(
            "UPDATE teachers SET first_name=?, last_name=?, gender=?, phone=?,
             address=?, department_id=?, qualification=? WHERE id=?",
            [
                $data['first_name'], $data['last_name'], $data['gender'], $data['phone'] ?? null,
                $data['address'] ?? null, $data['department_id'] ?? null,
                $data['qualification'] ?? null, $id
            ]
        );
    }
}
