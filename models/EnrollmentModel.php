<?php
// models/EnrollmentModel.php

require_once ROOT_PATH . '/models/BaseModel.php';

class EnrollmentModel extends BaseModel {
    protected string $table = 'enrollments';

    public function enroll(int $studentId, int $classId, int $yearId): bool {
        $existing = $this->db->fetchOne(
            "SELECT id FROM enrollments WHERE student_id=? AND class_id=? AND academic_year_id=?",
            [$studentId, $classId, $yearId]
        );
        if ($existing) return false;

        $this->db->insert(
            "INSERT INTO enrollments (student_id, class_id, academic_year_id, enrollment_date) VALUES (?, ?, ?, CURDATE())",
            [$studentId, $classId, $yearId]
        );
        return true;
    }

    public function drop(int $studentId, int $classId, int $yearId): void {
        $this->db->execute(
            "UPDATE enrollments SET status='dropped' WHERE student_id=? AND class_id=? AND academic_year_id=?",
            [$studentId, $classId, $yearId]
        );
    }

    public function getStudentsInClass(int $classId, int $yearId): array {
        return $this->db->fetchAll(
            "SELECT s.*, e.enrollment_date, e.status as enrollment_status
             FROM enrollments e
             JOIN students s ON s.id = e.student_id
             WHERE e.class_id = ? AND e.academic_year_id = ? AND e.status = 'active'
             ORDER BY s.last_name",
            [$classId, $yearId]
        );
    }

    public function getClassesForStudent(int $studentId): array {
        return $this->db->fetchAll(
            "SELECT c.*, ay.name as year_name, e.status as enrollment_status
             FROM enrollments e
             JOIN classes c ON c.id = e.class_id
             JOIN academic_years ay ON ay.id = e.academic_year_id
             WHERE e.student_id = ?
             ORDER BY ay.start_date DESC",
            [$studentId]
        );
    }

    public function generateEnrollmentReport(int $yearId): array {
        return $this->db->fetchAll(
            "SELECT c.name as class_name, c.grade_level, c.section,
                    COUNT(e.id) as total_students,
                    SUM(CASE WHEN s.gender='male' THEN 1 ELSE 0 END) as male_count,
                    SUM(CASE WHEN s.gender='female' THEN 1 ELSE 0 END) as female_count
             FROM classes c
             LEFT JOIN enrollments e ON e.class_id = c.id AND e.academic_year_id = ? AND e.status='active'
             LEFT JOIN students s ON s.id = e.student_id
             WHERE c.academic_year_id = ?
             GROUP BY c.id
             ORDER BY c.name",
            [$yearId, $yearId]
        );
    }
}
