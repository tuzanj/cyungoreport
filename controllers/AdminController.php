<?php
// controllers/AdminController.php

require_once ROOT_PATH . '/models/UserModel.php';
require_once ROOT_PATH . '/models/CourseModel.php';
require_once ROOT_PATH . '/models/ClassModel.php';
require_once ROOT_PATH . '/models/TeacherModel.php';
require_once ROOT_PATH . '/models/AuditModel.php';
require_once ROOT_PATH . '/models/NotificationModel.php';

class AdminController {
    private CourseModel $courseModel;
    private ClassModel $classModel;
    private TeacherModel $teacherModel;
    private UserModel $userModel;
    private AuditModel $auditModel;
    private NotificationModel $notifModel;
    private Database $db;

    public function __construct() {
        $this->courseModel  = new CourseModel();
        $this->classModel   = new ClassModel();
        $this->teacherModel = new TeacherModel();
        $this->userModel    = new UserModel();
        $this->auditModel   = new AuditModel();
        $this->notifModel   = new NotificationModel();
        $this->db           = Database::getInstance();
    }

    public function createCourse(array $data): array {
        if ($this->courseModel->codeExists($data['code'])) {
            return ['success' => false, 'error' => 'Course code already exists.'];
        }
        $id = $this->courseModel->create($data);
        $this->auditModel->log('course_created', 'courses', $id, null, $data);
        return ['success' => true, 'id' => $id, 'message' => 'Course created successfully.'];
    }

    public function updateCourse(int $id, array $data): array {
        if ($this->courseModel->codeExists($data['code'], $id)) {
            return ['success' => false, 'error' => 'Course code already in use.'];
        }
        $old = $this->courseModel->findById($id);
        $this->courseModel->update($id, $data);
        $this->auditModel->log('course_updated', 'courses', $id, $old, $data);
        return ['success' => true, 'message' => 'Course updated.'];
    }

    public function deleteCourse(int $id): array {
        $old = $this->courseModel->findById($id);
        if (!$old) return ['success' => false, 'error' => 'Course not found.'];
        $this->courseModel->delete($id);
        $this->auditModel->log('course_deleted', 'courses', $id, $old);
        return ['success' => true, 'message' => 'Course deleted.'];
    }

    public function setGradingCriteria(int $courseId, int $yearId, array $data): array {
        $total = (float)$data['assignments_weight'] + (float)$data['quizzes_weight']
               + (float)$data['midterm_weight'] + (float)$data['final_weight'];
        if (abs($total - 100) > 0.01) {
            return ['success' => false, 'error' => 'Weights must sum to 100%.'];
        }
        $this->courseModel->setGradingCriteria($courseId, $yearId, $data);
        $this->auditModel->log('grading_criteria_set', 'grading_criteria', $courseId, null, $data);
        return ['success' => true, 'message' => 'Grading criteria saved.'];
    }

    public function createTeacher(array $data): array {
        $data['username'] = trim($data['username'] ?? '');
        $data['email'] = trim($data['email'] ?? '');

        if (empty($data['username']) || empty($data['email'])) {
            return ['success' => false, 'error' => 'Username and email are required.'];
        }

        if ($this->userModel->usernameExists($data['username'])) {
            return ['success' => false, 'error' => 'Username already exists. Please choose another one.'];
        }

        if ($this->userModel->emailExists($data['email'])) {
            return ['success' => false, 'error' => 'Email already exists. Please use a different email address.'];
        }

        // Create user account
        $password = bin2hex(random_bytes(4));
        $userId = $this->userModel->createUser([
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => $password,
            'role'     => ROLE_TEACHER,
        ]);

        $empId = $this->teacherModel->generateEmployeeId();
        $tId = $this->teacherModel->create(array_merge($data, ['user_id' => $userId, 'employee_id' => $empId]));

        $this->notifModel->send($userId, 'Teacher Account Created', "Welcome! Username: {$data['username']}, Password: {$password}", 'success');
        $this->auditModel->log('teacher_created', 'teachers', $tId, null, ['employee_id' => $empId]);

        return ['success' => true, 'employee_id' => $empId, 'password' => $password, 'message' => 'Teacher account created.'];
    }

    public function assignCourseToTeacher(int $classId, int $courseId, int $teacherId, int $yearId): array {
        $ccId = $this->classModel->assignCourse($classId, $courseId, $teacherId, $yearId);
        $this->auditModel->log('course_assigned', 'class_courses', $ccId);
        return ['success' => true, 'message' => 'Course assigned to teacher.'];
    }

    public function createClass(array $data): array {
        $id = $this->classModel->create($data);
        $this->auditModel->log('class_created', 'classes', $id, null, $data);
        return ['success' => true, 'id' => $id, 'message' => 'Class created.'];
    }

    public function addSchedule(int $classCourseId, array $data): array {
        $id = $this->classModel->addSchedule($classCourseId, $data['day'], $data['start_time'], $data['end_time'], $data['room'] ?? null);
        $this->auditModel->log('schedule_added', 'schedules', $id, null, $data);
        return ['success' => true, 'message' => 'Schedule added.'];
    }

    public function getDashboardStats(): array {
        $db = $this->db;
        return [
            'total_students'  => $db->fetchOne("SELECT COUNT(*) as c FROM students WHERE status='active'")['c'] ?? 0,
            'total_teachers'  => $db->fetchOne("SELECT COUNT(*) as c FROM teachers")['c'] ?? 0,
            'total_courses'   => $db->fetchOne("SELECT COUNT(*) as c FROM courses")['c'] ?? 0,
            'total_classes'   => $db->fetchOne("SELECT COUNT(*) as c FROM classes")['c'] ?? 0,
            'pending_claims'  => $db->fetchOne("SELECT COUNT(*) as c FROM grade_claims WHERE status='pending'")['c'] ?? 0,
            'recent_logs'     => $db->fetchAll("SELECT al.*, u.username FROM audit_logs al LEFT JOIN users u ON u.id=al.user_id ORDER BY al.created_at DESC LIMIT 5"),
            'enrollment_by_class' => $db->fetchAll(
                "SELECT c.name, COUNT(e.id) as cnt FROM classes c
                 LEFT JOIN enrollments e ON e.class_id=c.id AND e.status='active'
                 WHERE c.academic_year_id=(SELECT id FROM academic_years WHERE is_current=1 LIMIT 1)
                 GROUP BY c.id ORDER BY cnt DESC LIMIT 6"
            ),
        ];
    }

    public function createAcademicYear(array $data): array {
        if (!empty($data['is_current'])) {
            $this->db->execute("UPDATE academic_years SET is_current = 0");
        }
        $id = $this->db->insert(
            "INSERT INTO academic_years (name, start_date, end_date, is_current) VALUES (?,?,?,?)",
            [$data['name'], $data['start_date'], $data['end_date'], !empty($data['is_current']) ? 1 : 0]
        );
        $this->auditModel->log('academic_year_created', 'academic_years', $id, null, $data);
        return ['success' => true, 'id' => $id, 'message' => 'Academic year created.'];
    }

    public function createDepartment(string $name, string $description = ''): array {
        $id = $this->db->insert(
            "INSERT INTO departments (name, description) VALUES (?,?)",
            [$name, $description]
        );
        return ['success' => true, 'id' => $id];
    }

    public function toggleUserStatus(int $userId): array {
        $user = $this->userModel->findById($userId);
        if (!$user) return ['success' => false, 'error' => 'User not found.'];
        $newStatus = $user['is_active'] ? 0 : 1;
        $this->userModel->updateStatus($userId, $newStatus);
        $this->auditModel->log($newStatus ? 'user_activated' : 'user_deactivated', 'users', $userId);
        return ['success' => true, 'is_active' => $newStatus];
    }

    public function resolveGradeClaim(int $claimId, string $response, string $status): array {
        $this->db->execute(
            "UPDATE grade_claims SET status=?, response=?, resolved_by=?, resolved_at=NOW() WHERE id=?",
            [$status, $response, currentUserId(), $claimId]
        );
        $claim = $this->db->fetchOne("SELECT * FROM grade_claims WHERE id=?", [$claimId]);
        if ($claim) {
            $this->notifModel->send($claim['student_id'], 'Grade Claim Updated', "Your grade claim has been {$status}.", $status === 'resolved' ? 'success' : 'warning');
        }
        return ['success' => true, 'message' => 'Claim updated.'];
    }
}
