<?php
// controllers/ParentController.php

require_once ROOT_PATH . '/models/ParentModel.php';
require_once ROOT_PATH . '/models/StudentModel.php';
require_once ROOT_PATH . '/models/AttendanceModel.php';
require_once ROOT_PATH . '/models/NotificationModel.php';
require_once ROOT_PATH . '/models/AuditModel.php';

class ParentController {
    private ParentModel $parentModel;
    private StudentModel $studentModel;
    private AttendanceModel $attendModel;
    private NotificationModel $notifModel;
    private AuditModel $auditModel;
    private Database $db;

    public function __construct() {
        $this->parentModel  = new ParentModel();
        $this->studentModel = new StudentModel();
        $this->attendModel  = new AttendanceModel();
        $this->notifModel   = new NotificationModel();
        $this->auditModel   = new AuditModel();
        $this->db           = Database::getInstance();
    }

    public function getDashboard(int $userId): array {
        $parent = $this->parentModel->findByUserId($userId);
        if (!$parent) return [];

        $children = $this->parentModel->getChildren($parent['id']);
        $childrenData = [];

        $year = $this->db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
        $yearId = $year ? (int)$year['id'] : 0;

        foreach ($children as $child) {
            $marks = $this->parentModel->getChildReport((int)$child['id'], $yearId);
            $gpa   = $this->studentModel->getGpa((int)$child['id'], $yearId);

            // Attendance summary across all courses
            $attendance = $this->db->fetchOne(
                "SELECT
                    COUNT(*) as total,
                    SUM(status='present') as present,
                    SUM(status='absent') as absent
                 FROM attendance WHERE student_id=?",
                [$child['id']]
            );

            $childrenData[] = [
                'info'       => $child,
                'marks'      => $marks,
                'gpa'        => $gpa,
                'attendance' => $attendance,
            ];
        }

        $notifs = $this->notifModel->getForUser($userId, true);

        return [
            'parent'   => $parent,
            'children' => $childrenData,
            'notifs'   => $notifs,
            'year'     => $year,
        ];
    }

    public function sendMessageToTeacher(int $senderUserId, int $teacherUserId, string $subject, string $body): array {
        try {
            $teacher = $this->db->fetchOne(
                "SELECT t.id FROM teachers t JOIN users u ON u.id = t.user_id WHERE u.id = ? AND u.role = ? AND u.is_active = 1",
                [$teacherUserId, ROLE_TEACHER]
            );
            if (!$teacher) {
                return ['success' => false, 'error' => 'Please select a valid active teacher.'];
            }

            $subject = trim($subject) ?: 'Message from parent';
            $body = trim($body);
            if ($body === '') {
                return ['success' => false, 'error' => 'Message body is required.'];
            }

            $this->db->insert(
                "INSERT INTO messages (sender_id, receiver_id, subject, body) VALUES (?,?,?,?)",
                [$senderUserId, $teacherUserId, $subject, $body]
            );
            $this->notifModel->send($teacherUserId, 'New Message from Parent', "Subject: {$subject}", 'info', '/teacher/notifications.php');
            return ['success' => true, 'message' => 'Message sent.'];
        } catch (Throwable $e) {
            error_log("Parent Message Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Unable to send message. Please try again.'];
        }
    }

    public function getMessages(int $userId): array {
        return $this->db->fetchAll(
            "SELECT m.*, u.username as sender_name FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE m.receiver_id = ? OR m.sender_id = ?
             ORDER BY m.created_at DESC",
            [$userId, $userId]
        );
    }
}
