<?php
// models/BehaviorModel.php

require_once ROOT_PATH . '/models/BaseModel.php';

class BehaviorModel extends BaseModel {
    protected string $table = 'behavior_records';

    /**
     * Record student behavior
     */
    public function recordBehavior(int $studentId, int $classCourseId, int $term, int $academicYearId, array $data): int {
        $existing = $this->db->fetchOne(
            "SELECT id FROM behavior_records 
             WHERE student_id = ? AND class_course_id = ? AND term = ? AND academic_year_id = ?",
            [$studentId, $classCourseId, $term, $academicYearId]
        );

        if ($existing) {
            $this->db->execute(
                "UPDATE behavior_records SET behavior_grade = ?, conduct_score = ?, remarks = ?, updated_at = NOW()
                 WHERE student_id = ? AND class_course_id = ? AND term = ? AND academic_year_id = ?",
                [
                    $data['behavior_grade'] ?? null,
                    $data['conduct_score'] ?? null,
                    $data['remarks'] ?? null,
                    $studentId, $classCourseId, $term, $academicYearId
                ]
            );
            return (int)$existing['id'];
        }

        return $this->db->insert(
            "INSERT INTO behavior_records 
             (student_id, class_course_id, term, academic_year_id, behavior_grade, conduct_score, remarks, recorded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $studentId, $classCourseId, $term, $academicYearId,
                $data['behavior_grade'] ?? null,
                $data['conduct_score'] ?? null,
                $data['remarks'] ?? null,
                $data['recorded_by'] ?? currentUserId()
            ]
        );
    }

    /**
     * Get student behavior records
     */
    public function getStudentBehavior(int $studentId, int $academicYearId): array {
        return $this->db->fetchAll(
            "SELECT b.*, c.name as course_name, t.first_name, t.last_name
             FROM behavior_records b
             JOIN class_courses cc ON cc.id = b.class_course_id
             JOIN courses c ON c.id = cc.course_id
             LEFT JOIN teachers t ON t.id = cc.teacher_id
             WHERE b.student_id = ? AND b.academic_year_id = ?
             ORDER BY b.term, c.name",
            [$studentId, $academicYearId]
        );
    }

    /**
     * Get overall behavior grade for a student in a term
     */
    public function getTermBehaviorGrade(int $studentId, int $academicYearId, int $term = 1): ?array {
        return $this->db->fetchOne(
            "SELECT 
                AVG(CAST(conduct_score AS DECIMAL(5,2))) as average_conduct,
                COUNT(*) as course_count,
                GROUP_CONCAT(DISTINCT behavior_grade) as grades
             FROM behavior_records
             WHERE student_id = ? AND academic_year_id = ? AND term = ?",
            [$studentId, $academicYearId, $term]
        );
    }

    /**
     * Get all students' behavior for a class
     */
    public function getClassBehavior(int $classId, int $academicYearId, int $term = 1): array {
        return $this->db->fetchAll(
            "SELECT b.*, s.first_name, s.last_name, s.student_id, c.name as course_name
             FROM behavior_records b
             JOIN students s ON s.id = b.student_id
             JOIN enrollments e ON e.student_id = s.id
             JOIN class_courses cc ON cc.id = b.class_course_id
             JOIN courses c ON c.id = cc.course_id
             WHERE e.class_id = ? AND b.academic_year_id = ? AND b.term = ?
             ORDER BY s.last_name, s.first_name, c.name",
            [$classId, $academicYearId, $term]
        );
    }
}
