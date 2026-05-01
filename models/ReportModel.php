<?php
// models/ReportModel.php

require_once ROOT_PATH . '/models/BaseModel.php';

class ReportModel extends BaseModel {
    protected string $table = 'marks';

    /**
     * Get comprehensive report data for a student across all courses in a term
     */
    public function getStudentTermReport(int $studentId, int $academicYearId, int $term = 1): ?array {
        $student = $this->db->fetchOne(
            "SELECT s.*, u.username FROM students s
             JOIN users u ON u.id = s.user_id
             WHERE s.id = ?",
            [$studentId]
        );

        if (!$student) return null;

        // Get enrollment info
        $enrollment = $this->db->fetchOne(
            "SELECT e.*, c.name as class_name, c.grade_level, ay.name as academic_year
             FROM enrollments e
             JOIN classes c ON c.id = e.class_id
             JOIN academic_years ay ON ay.id = e.academic_year_id
             WHERE e.student_id = ? AND e.academic_year_id = ?",
            [$studentId, $academicYearId]
        );

        // Get all marks/courses for the term
        $marks = $this->db->fetchAll(
            "SELECT m.*, c.code, c.name, t.first_name as teacher_fname, t.last_name as teacher_lname
             FROM marks m
             JOIN class_courses cc ON cc.id = m.class_course_id
             JOIN courses c ON c.id = cc.course_id
             LEFT JOIN teachers t ON t.id = cc.teacher_id
             WHERE m.student_id = ? AND cc.academic_year_id = ?
             ORDER BY c.name",
            [$studentId, $academicYearId]
        );

        // Get behavior records
        $behavior = $this->db->fetchOne(
            "SELECT b.* FROM behavior_records b
             WHERE b.student_id = ? AND b.academic_year_id = ? AND b.term = ?",
            [$studentId, $academicYearId, $term]
        );

        // Get school/institution info
        $schoolInfo = [
            'name' => 'CYUNGOREPORT TECHNICAL SECONDARY SCHOOL',
            'district' => 'GISAGARA',
            'phone' => '0785 443 775',
            'academic_year' => $enrollment['academic_year'] ?? '',
            'class' => $enrollment['class_name'] ?? ''
        ];

        return [
            'student' => $student,
            'enrollment' => $enrollment,
            'marks' => $marks,
            'behavior' => $behavior,
            'school' => $schoolInfo
        ];
    }

    /**
     * Get class-level report (all students in a class)
     */
    public function getClassReport(int $classId, int $academicYearId): array {
        $classInfo = $this->db->fetchOne(
            "SELECT c.*, ay.name as academic_year FROM classes c
             JOIN academic_years ay ON ay.id = c.academic_year_id
             WHERE c.id = ?",
            [$classId]
        );

        $students = $this->db->fetchAll(
            "SELECT s.id, s.student_id, s.first_name, s.last_name
             FROM students s
             JOIN enrollments e ON e.student_id = s.id
             WHERE e.class_id = ? AND e.academic_year_id = ?
             ORDER BY s.last_name, s.first_name",
            [$classId, $academicYearId]
        );

        $courseMarks = [];
        foreach ($students as $student) {
            $marks = $this->db->fetchAll(
                "SELECT m.*, c.code, c.name
                 FROM marks m
                 JOIN class_courses cc ON cc.id = m.class_course_id
                 JOIN courses c ON c.id = cc.course_id
                 WHERE m.student_id = ? AND cc.class_id = ? AND cc.academic_year_id = ?
                 ORDER BY c.name",
                [$student['id'], $classId, $academicYearId]
            );
            $courseMarks[$student['id']] = $marks;
        }

        return [
            'class' => $classInfo,
            'students' => $students,
            'courseMarks' => $courseMarks
        ];
    }

    /**
     * Get courses with termly breakdown
     */
    public function getCoursesByTerm(int $term = 1): array {
        return $this->db->fetchAll(
            "SELECT DISTINCT c.* FROM courses c
             WHERE c.id IN (
                 SELECT cc.course_id FROM class_courses cc
             )
             ORDER BY c.name"
        );
    }

    /**
     * Calculate statistics for reporting
     */
    public function calculateMarkStatistics(array $marks): array {
        $stats = [
            'count' => count($marks),
            'passed' => 0,
            'failed' => 0,
            'average_calculated' => 0,
            'best_subject' => null,
            'worst_subject' => null
        ];

        if (empty($marks)) return $stats;

        $totalCalculated = 0;
        $minGrade = 100;
        $maxGrade = 0;
        $bestSubject = null;
        $worstSubject = null;

        foreach ($marks as $mark) {
            if ($mark['is_pass']) $stats['passed']++;
            else $stats['failed']++;

            $calculated = (float)($mark['calculated_grade'] ?? 0);
            $totalCalculated += $calculated;

            if ($calculated > $maxGrade) {
                $maxGrade = $calculated;
                $bestSubject = $mark['name'];
            }

            if ($calculated < $minGrade) {
                $minGrade = $calculated;
                $worstSubject = $mark['name'];
            }
        }

        $stats['average_calculated'] = round($totalCalculated / $stats['count'], 2);
        $stats['best_subject'] = $bestSubject;
        $stats['best_grade'] = round($maxGrade, 2);
        $stats['worst_subject'] = $worstSubject;
        $stats['worst_grade'] = round($minGrade, 2);

        return $stats;
    }
}
