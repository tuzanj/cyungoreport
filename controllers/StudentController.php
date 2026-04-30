<?php
// controllers/StudentController.php

require_once ROOT_PATH . '/models/StudentModel.php';
require_once ROOT_PATH . '/models/MarkModel.php';
require_once ROOT_PATH . '/models/AttendanceModel.php';
require_once ROOT_PATH . '/models/NotificationModel.php';
require_once ROOT_PATH . '/models/AuditModel.php';

class StudentController {
    private StudentModel $studentModel;
    private MarkModel $markModel;
    private AttendanceModel $attendModel;
    private NotificationModel $notifModel;
    private AuditModel $auditModel;
    private Database $db;

    public function __construct() {
        $this->studentModel = new StudentModel();
        $this->markModel    = new MarkModel();
        $this->attendModel  = new AttendanceModel();
        $this->notifModel   = new NotificationModel();
        $this->auditModel   = new AuditModel();
        $this->db           = Database::getInstance();
    }

    public function getDashboard(int $userId): array {
        $student = $this->studentModel->findByUserId($userId);
        if (!$student) return [];

        $year = $this->db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
        $yearId = $year ? (int)$year['id'] : 0;

        $courses    = $this->studentModel->getEnrolledCourses($student['id'], $yearId);
        $marks      = $this->markModel->getStudentMarks($student['id'], $yearId);
        $gpa        = $this->studentModel->getGpa($student['id'], $yearId);
        $notifs     = $this->notifModel->getForUser($userId, true);

        return [
            'student'  => $student,
            'year'     => $year,
            'courses'  => $courses,
            'marks'    => $marks,
            'gpa'      => $gpa,
            'notifs'   => $notifs,
        ];
    }

    public function updateProfile(int $userId, array $data): array {
        $student = $this->studentModel->findByUserId($userId);
        if (!$student) return ['success' => false, 'error' => 'Profile not found.'];

        $this->studentModel->update($student['id'], array_merge($student, $data));
        $this->auditModel->log('profile_updated', 'students', $student['id']);
        return ['success' => true, 'message' => 'Profile updated.'];
    }

    public function raiseGradeClaim(int $markId, int $studentId, string $reason): array {
        $mark = $this->markModel->findById($markId);
        if (!$mark || $mark['student_id'] != $studentId) {
            return ['success' => false, 'error' => 'Mark not found.'];
        }

        $existing = $this->db->fetchOne(
            "SELECT id FROM grade_claims WHERE mark_id=? AND student_id=? AND status IN ('pending','under_review')",
            [$markId, $studentId]
        );
        if ($existing) {
            return ['success' => false, 'error' => 'A claim is already pending for this mark.'];
        }

        $this->db->insert(
            "INSERT INTO grade_claims (mark_id, student_id, reason) VALUES (?,?,?)",
            [$markId, $studentId, $reason]
        );
        $this->auditModel->log('grade_claim_raised', 'grade_claims', $markId, null, ['reason' => $reason]);
        return ['success' => true, 'message' => 'Grade claim submitted.'];
    }

    public function getTranscriptData(int $userId): array {
        $student = $this->studentModel->findByUserId($userId);
        if (!$student) return [];

        $allYears = $this->db->fetchAll("SELECT * FROM academic_years ORDER BY start_date");
        $transcript = [];

        foreach ($allYears as $year) {
            $marks = $this->markModel->getStudentMarks($student['id'], (int)$year['id']);
            if (empty($marks)) continue;
            $gpa = $this->studentModel->getGpa($student['id'], (int)$year['id']);
            $transcript[] = [
                'year'  => $year,
                'marks' => $marks,
                'gpa'   => $gpa,
            ];
        }

        return ['student' => $student, 'transcript' => $transcript];
    }

    public function getSchedule(int $userId): array {
        $student = $this->studentModel->findByUserId($userId);
        if (!$student) return [];

        $year = $this->db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
        if (!$year) return [];

        $enrollment = $this->db->fetchOne(
            "SELECT class_id FROM enrollments WHERE student_id=? AND academic_year_id=? AND status='active'",
            [$student['id'], $year['id']]
        );
        if (!$enrollment) return [];

        return $this->db->fetchAll(
            "SELECT s.day_of_week, s.start_time, s.end_time, s.room,
                    c.name as course_name, c.code,
                    t.first_name as teacher_first, t.last_name as teacher_last
             FROM schedules s
             JOIN class_courses cc ON cc.id = s.class_course_id
             JOIN courses c ON c.id = cc.course_id
             JOIN teachers t ON t.id = cc.teacher_id
             WHERE cc.class_id=? AND cc.academic_year_id=?
             ORDER BY FIELD(s.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), s.start_time",
            [$enrollment['class_id'], $year['id']]
        );
    }
}
