<?php
// controllers/TeacherController.php

require_once ROOT_PATH . '/models/TeacherModel.php';
require_once ROOT_PATH . '/models/MarkModel.php';
require_once ROOT_PATH . '/models/AttendanceModel.php';
require_once ROOT_PATH . '/models/AuditModel.php';
require_once ROOT_PATH . '/models/NotificationModel.php';

class TeacherController {
    private TeacherModel $teacherModel;
    private MarkModel $markModel;
    private AttendanceModel $attendModel;
    private AuditModel $auditModel;
    private NotificationModel $notifModel;
    private Database $db;

    public function __construct() {
        $this->teacherModel = new TeacherModel();
        $this->markModel    = new MarkModel();
        $this->attendModel  = new AttendanceModel();
        $this->auditModel   = new AuditModel();
        $this->notifModel   = new NotificationModel();
        $this->db           = Database::getInstance();
    }

    public function saveMarks(int $classCourseId, array $marksData): array {
        $saved = 0;
        foreach ($marksData as $studentId => $scores) {
            $this->markModel->upsert((int)$studentId, $classCourseId, $scores);
            $saved++;
        }
        $this->auditModel->log('marks_saved', 'marks', $classCourseId, null, ['count' => $saved]);
        return ['success' => true, 'saved' => $saved, 'message' => "{$saved} mark(s) saved."];
    }

    public function publishResults(int $classCourseId): array {
        $count = $this->markModel->publishAll($classCourseId);

        // Notify all students
        $students = $this->teacherModel->getStudentsInCourse($classCourseId);
        $userIds = array_map(fn($s) => $this->db->fetchOne("SELECT user_id FROM students WHERE id=?", [$s['id']])['user_id'] ?? null, $students);
        $userIds = array_filter($userIds);

        $this->notifModel->sendBulk($userIds, 'Results Published', 'Your results have been published. Log in to view your grades.', 'success');
        $this->auditModel->log('results_published', 'marks', $classCourseId, null, ['count' => $count]);

        return ['success' => true, 'published' => $count, 'message' => "{$count} result(s) published."];
    }

    public function submitSupplementary(int $markId, float $score): array {
        if ($score < 0 || $score > 100) {
            return ['success' => false, 'message' => 'Supplementary score must be between 0 and 100.'];
        }

        $mark = $this->markModel->findById($markId);
        if (!$mark) {
            return ['success' => false, 'message' => 'Mark record not found.'];
        }

        $letterGrade = getLetterGrade($score);
        $this->markModel->submitSupplementary($markId, $score, $letterGrade);
        $this->auditModel->log('supplementary_submitted', 'marks', $markId, null, ['score' => $score, 'letter_grade' => $letterGrade]);

        return ['success' => true, 'message' => 'Supplementary exam score recorded successfully.'];
    }

    public function deleteMark(int $markId): array {
        $deleted = $this->markModel->deleteIfUnpublished($markId);
        if ($deleted) {
            $this->auditModel->log('mark_deleted', 'marks', $markId);
            return ['success' => true, 'message' => 'Mark deleted.'];
        }
        return ['success' => false, 'error' => 'Cannot delete published marks.'];
    }

    public function recordAttendance(int $classCourseId, string $date, array $attendanceData): array {
        foreach ($attendanceData as $studentId => $info) {
            $this->attendModel->record((int)$studentId, $classCourseId, $date, $info['status'], $info['remarks'] ?? null);
        }
        $this->auditModel->log('attendance_recorded', 'attendance', $classCourseId, null, ['date' => $date]);
        return ['success' => true, 'message' => 'Attendance recorded.'];
    }

    public function getDashboard(int $teacherId, int $yearId): array {
        $courses = $this->teacherModel->getAssignedCourses($teacherId, $yearId);
        $totalStudents = array_sum(array_column($courses, 'student_count'));

        $pendingClaims = $this->db->fetchAll(
            "SELECT gc.*, s.first_name, s.last_name, c.name as course_name
             FROM grade_claims gc
             JOIN marks m ON m.id = gc.mark_id
             JOIN class_courses cc ON cc.id = m.class_course_id
             JOIN students s ON s.id = gc.student_id
             JOIN courses c ON c.id = cc.course_id
             WHERE cc.teacher_id = ? AND gc.status = 'pending'
             ORDER BY gc.created_at DESC",
            [$teacherId]
        );

        return [
            'courses'        => $courses,
            'total_students' => $totalStudents,
            'pending_claims' => $pendingClaims,
            'total_claims'   => count($pendingClaims),
        ];
    }

    public function getClassroomMarks(int $classCourseId): array {
        return $this->teacherModel->getStudentsInCourse($classCourseId);
    }
}
