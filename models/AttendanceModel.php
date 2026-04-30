<?php
// models/AttendanceModel.php

require_once ROOT_PATH . '/models/BaseModel.php';

class AttendanceModel extends BaseModel {
    protected string $table = 'attendance';

    public function record(int $studentId, int $classCourseId, string $date, string $status, ?string $remarks = null): void {
        $existing = $this->db->fetchOne(
            "SELECT id FROM attendance WHERE student_id=? AND class_course_id=? AND date=?",
            [$studentId, $classCourseId, $date]
        );
        if ($existing) {
            $this->db->execute(
                "UPDATE attendance SET status=?, remarks=?, recorded_by=? WHERE id=?",
                [$status, $remarks, currentUserId(), $existing['id']]
            );
        } else {
            $this->db->insert(
                "INSERT INTO attendance (student_id, class_course_id, date, status, remarks, recorded_by) VALUES (?,?,?,?,?,?)",
                [$studentId, $classCourseId, $date, $status, $remarks, currentUserId()]
            );
        }
    }

    public function getForDate(int $classCourseId, string $date): array {
        return $this->db->fetchAll(
            "SELECT a.*, s.first_name, s.last_name, s.student_id as student_code
             FROM attendance a JOIN students s ON s.id = a.student_id
             WHERE a.class_course_id=? AND a.date=?
             ORDER BY s.last_name",
            [$classCourseId, $date]
        );
    }

    public function getSummaryForStudent(int $studentId, int $classCourseId): array {
        return $this->db->fetchAll(
            "SELECT date, status, remarks FROM attendance
             WHERE student_id=? AND class_course_id=?
             ORDER BY date DESC",
            [$studentId, $classCourseId]
        );
    }

    public function getAttendanceStats(int $studentId, int $classCourseId): array {
        $row = $this->db->fetchOne(
            "SELECT
                COUNT(*) as total,
                SUM(status='present') as present,
                SUM(status='absent') as absent,
                SUM(status='late') as late,
                SUM(status='excused') as excused
             FROM attendance WHERE student_id=? AND class_course_id=?",
            [$studentId, $classCourseId]
        );
        return $row ?: ['total'=>0,'present'=>0,'absent'=>0,'late'=>0,'excused'=>0];
    }

    public function getMonthlyReport(int $classCourseId, int $month, int $year): array {
        return $this->db->fetchAll(
            "SELECT s.student_id as code, s.first_name, s.last_name,
                    SUM(a.status='present') as present,
                    SUM(a.status='absent') as absent,
                    SUM(a.status='late') as late,
                    COUNT(a.id) as total
             FROM students s
             JOIN enrollments e ON e.student_id=s.id
             JOIN class_courses cc ON cc.class_id=e.class_id AND cc.id=?
             LEFT JOIN attendance a ON a.student_id=s.id AND a.class_course_id=?
                 AND MONTH(a.date)=? AND YEAR(a.date)=?
             WHERE e.status='active'
             GROUP BY s.id
             ORDER BY s.last_name",
            [$classCourseId, $classCourseId, $month, $year]
        );
    }
}
