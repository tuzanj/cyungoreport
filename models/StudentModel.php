<?php
// models/StudentModel.php

require_once ROOT_PATH . '/models/BaseModel.php';

class StudentModel extends BaseModel {
    protected string $table = 'students';

    public function findByStudentId(string $studentId): ?array {
        return $this->db->fetchOne(
            "SELECT s.*, u.username, u.email FROM students s
             JOIN users u ON u.id = s.user_id
             WHERE s.student_id = ?",
            [$studentId]
        );
    }

    public function findByUserId(int $userId): ?array {
        return $this->db->fetchOne("SELECT * FROM students WHERE user_id = ?", [$userId]);
    }

    public function checkDuplicate(string $firstName, string $lastName, string $dob): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM students WHERE first_name = ? AND last_name = ? AND date_of_birth = ?",
            [$firstName, $lastName, $dob]
        );
    }

    public function generateStudentId(): string {
        $year = date('Y');
        $count = $this->count("student_id LIKE ?", ["{$year}%"]);
        return $year . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data): int {
        return $this->db->insert(
            "INSERT INTO students (user_id, student_id, first_name, last_name, gender, date_of_birth, phone, address, emergency_contact, trade_id, enrollment_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['user_id'], $data['student_id'], $data['first_name'], $data['last_name'],
                $data['gender'], $data['date_of_birth'], $data['phone'] ?? null,
                $data['address'] ?? null, $data['emergency_contact'] ?? null,
                $data['trade_id'] ?? null,
                $data['enrollment_date'] ?? date('Y-m-d')
            ]
        );
    }

    public function update(int $id, array $data): int {
        return $this->db->execute(
            "UPDATE students SET first_name=?, last_name=?, gender=?, date_of_birth=?,
             phone=?, address=?, emergency_contact=?, trade_id=?, status=? WHERE id=?",
            [
                $data['first_name'], $data['last_name'], $data['gender'], $data['date_of_birth'],
                $data['phone'] ?? null, $data['address'] ?? null, $data['emergency_contact'] ?? null,
                $data['trade_id'] ?? null,
                $data['status'] ?? 'active', $id
            ]
        );
    }

    public function getWithUser(int $studentId): ?array {
        return $this->db->fetchOne(
            "SELECT s.*, u.username, u.email, u.is_active FROM students s
             JOIN users u ON u.id = s.user_id WHERE s.id = ?",
            [$studentId]
        );
    }

    public function getAllWithDetails(): array {
        return $this->db->fetchAll(
            "SELECT s.*, u.username, u.email, u.is_active,
                    c.name as class_name
             FROM students s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
             LEFT JOIN classes c ON c.id = e.class_id
             ORDER BY s.last_name, s.first_name"
        );
    }

    public function getEnrolledCourses(int $studentId, int $academicYearId): array {
        return $this->db->fetchAll(
            "SELECT cc.id as class_course_id, c.code, c.name as course_name, c.credits,
                    t.first_name as teacher_first, t.last_name as teacher_last,
                    cl.name as class_name
             FROM enrollments e
             JOIN class_courses cc ON cc.class_id = e.class_id AND cc.academic_year_id = e.academic_year_id
             JOIN courses c ON c.id = cc.course_id
             JOIN teachers t ON t.id = cc.teacher_id
             JOIN classes cl ON cl.id = e.class_id
             WHERE e.student_id = ? AND e.academic_year_id = ? AND e.status = 'active'",
            [$studentId, $academicYearId]
        );
    }

    public function getGpa(int $studentId, int $academicYearId): float {
        $marks = $this->db->fetchAll(
            "SELECT m.calculated_grade, c.credits FROM marks m
             JOIN class_courses cc ON cc.id = m.class_course_id
             JOIN courses c ON c.id = cc.course_id
             JOIN enrollments e ON e.class_id = cc.class_id AND e.student_id = m.student_id
             WHERE m.student_id = ? AND cc.academic_year_id = ? AND m.status = 'published'",
            [$studentId, $academicYearId]
        );

        $totalPoints = 0.0;
        $totalCredits = 0;
        foreach ($marks as $m) {
            $letter = getLetterGrade((float)$m['calculated_grade']);
            $gpa = getGpaPoint($letter);
            $credits = (int)$m['credits'];
            $totalPoints += $gpa * $credits;
            $totalCredits += $credits;
        }

        return $totalCredits > 0 ? round($totalPoints / $totalCredits, 2) : 0.0;
    }

    public function getParents(int $studentId): array {
        return $this->db->fetchAll(
            "SELECT p.*, u.email as parent_email FROM parent_student ps
             JOIN parents p ON p.id = ps.parent_id
             JOIN users u ON u.id = p.user_id
             WHERE ps.student_id = ?",
            [$studentId]
        );
    }
}
