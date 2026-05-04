<?php
// controllers/AdminController.php

require_once ROOT_PATH . '/models/UserModel.php';
require_once ROOT_PATH . '/models/CourseModel.php';
require_once ROOT_PATH . '/models/ClassModel.php';
require_once ROOT_PATH . '/models/TeacherModel.php';
require_once ROOT_PATH . '/models/StudentModel.php';
require_once ROOT_PATH . '/models/AuditModel.php';
require_once ROOT_PATH . '/models/NotificationModel.php';
require_once ROOT_PATH . '/classes/ExcelStudentImporter.php';
require_once ROOT_PATH . '/models/ReportModel.php';
require_once ROOT_PATH . '/classes/PDFReportGenerator.php';
require_once ROOT_PATH . '/classes/ExcelReportGenerator.php';

class AdminController {
    private CourseModel $courseModel;
    private ClassModel $classModel;
    private TeacherModel $teacherModel;
    private StudentModel $studentModel;
    private UserModel $userModel;
    private AuditModel $auditModel;
    private NotificationModel $notifModel;
    private ReportModel $reportModel;
    private Database $db;

    public function __construct() {
        $this->courseModel  = new CourseModel();
        $this->classModel   = new ClassModel();
        $this->teacherModel = new TeacherModel();
        $this->studentModel = new StudentModel();
        $this->userModel    = new UserModel();
        $this->auditModel   = new AuditModel();
        $this->notifModel   = new NotificationModel();
        $this->reportModel  = new ReportModel();
        $this->db           = Database::getInstance();
    }

    public function createCourse(array $data): array {
        try {
            $data['trade_id'] = $data['trade_id'] ?? $data['department_id'] ?? null;
            if ($this->courseModel->codeExists($data['code'])) {
                return ['success' => false, 'error' => 'Course code already exists.'];
            }
            $id = $this->courseModel->create($data);
            $this->auditModel->log('course_created', 'courses', $id, null, $data);
            return ['success' => true, 'id' => $id, 'message' => 'Course created.'];
        } catch (Exception $e) {
            error_log("Error creating course: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function updateCourse(int $id, array $data): array {
        try {
            $data['trade_id'] = $data['trade_id'] ?? $data['department_id'] ?? null;
            if ($this->courseModel->codeExists($data['code'], $id)) {
                return ['success' => false, 'error' => 'Course code already in use.'];
            }
            $old = $this->courseModel->findById($id);
            $this->courseModel->update($id, $data);
            $this->auditModel->log('course_updated', 'courses', $id, $old, $data);
            return ['success' => true, 'message' => 'Course updated.'];
        } catch (Exception $e) {
            error_log("Error updating course: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
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
        $data['trade_id'] = $data['trade_id'] ?? $data['department_id'] ?? null;
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
        if ($name === '') {
            return ['success' => false, 'error' => 'Trade name is required.'];
        }

        $id = $this->db->insert(
            "INSERT INTO trades (name, description) VALUES (?,?)",
            [$name, $description]
        );
        $this->auditModel->log('trade_created', 'trades', $id, null, ['name' => $name, 'description' => $description]);
        return ['success' => true, 'id' => $id, 'message' => 'Trade created.'];
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

    // ============================================================
    // STUDENT REGISTRATION & MANAGEMENT
    // ============================================================

    /**
     * Register a single student
     */
    public function createStudent(array $data): array {
        $data['username'] = trim($data['username'] ?? '');
        $data['email'] = trim($data['email'] ?? '');

        if (empty($data['first_name']) || empty($data['last_name'])) {
            return ['success' => false, 'error' => 'First name and last name are required.'];
        }

        if (empty($data['username']) || empty($data['email'])) {
            return ['success' => false, 'error' => 'Username and email are required.'];
        }

        if ($this->userModel->usernameExists($data['username'])) {
            return ['success' => false, 'error' => 'Username already exists.'];
        }

        if ($this->userModel->emailExists($data['email'])) {
            return ['success' => false, 'error' => 'Email already in use.'];
        }

        try {
            // Create user account
            $password = bin2hex(random_bytes(4));
            $userId = $this->userModel->createUser([
                'username' => $data['username'],
                'email'    => $data['email'],
                'password' => $password,
                'role'     => 'student',
            ]);

            // Generate student ID
            $studentId = $this->studentModel->generateStudentId();

            // Create student record
            $studentData = [
                'user_id' => $userId,
                'student_id' => $studentId,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'emergency_contact' => $data['emergency_contact'] ?? null,
            ];

            $sId = $this->studentModel->create($studentData);

            // Enroll in class if provided
            if (!empty($data['class_id'])) {
                $currentYear = $this->db->fetchOne(
                    "SELECT id FROM academic_years WHERE is_current = 1"
                );

                if ($currentYear) {
                    $this->db->insert(
                        "INSERT INTO enrollments (student_id, class_id, academic_year_id, enrollment_date)
                         VALUES (?, ?, ?, ?)",
                        [$sId, $data['class_id'], $currentYear['id'], date('Y-m-d')]
                    );
                }
            }

            // Send notification
            $this->notifModel->send($userId, 'Student Account Created', 
                "Welcome! Username: {$data['username']}, Password: {$password}", 'success');
            
            $this->auditModel->log('student_created', 'students', $sId, null, ['student_id' => $studentId]);

            return [
                'success' => true,
                'student_id' => $studentId,
                'password' => $password,
                'message' => 'Student registered successfully.'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error creating student: ' . $e->getMessage()];
        }
    }

    /**
     * Bulk import students from CSV file
     */
    public function importStudentsFromCSV(string $filePath): array {
        $importer = new ExcelStudentImporter();
        return $importer->importFromCSV($filePath);
    }

    /**
     * Generate student import template
     */
    public function getStudentImportTemplate(): string {
        return ExcelStudentImporter::getTemplateCSV();
    }

    // ============================================================
    // USER ROLE MANAGEMENT
    // ============================================================

    /**
     * Get all users with their roles
     */
    public function getAllUsers(): array {
        return $this->db->fetchAll(
            "SELECT u.*, 
                    CASE 
                        WHEN u.role = 'teacher' THEN t.first_name || ' ' || t.last_name
                        WHEN u.role = 'student' THEN s.first_name || ' ' || s.last_name
                        ELSE u.username
                    END as full_name
             FROM users u
             LEFT JOIN teachers t ON u.id = t.user_id
             LEFT JOIN students s ON u.id = s.user_id
             ORDER BY u.created_at DESC"
        );
    }

    /**
     * Update user role
     */
    public function updateUserRole(int $userId, string $newRole): array {
        $validRoles = ['admin', 'secretary', 'teacher', 'student', 'parent', 'discipline_master'];
        
        if (!in_array($newRole, $validRoles)) {
            return ['success' => false, 'error' => 'Invalid role.'];
        }

        $user = $this->userModel->findById($userId);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found.'];
        }

        $this->db->execute(
            "UPDATE users SET role = ? WHERE id = ?",
            [$newRole, $userId]
        );

        $this->auditModel->log('user_role_updated', 'users', $userId, 
            ['role' => $user['role']], ['role' => $newRole]);

        return ['success' => true, 'message' => 'User role updated successfully.'];
    }

    /**
     * Create discipline master account
     */
    public function createDisciplineMaster(array $data): array {
        $data['username'] = trim($data['username'] ?? '');
        $data['email'] = trim($data['email'] ?? '');

        if (empty($data['username']) || empty($data['email'])) {
            return ['success' => false, 'error' => 'Username and email are required.'];
        }

        if ($this->userModel->usernameExists($data['username'])) {
            return ['success' => false, 'error' => 'Username already exists.'];
        }

        if ($this->userModel->emailExists($data['email'])) {
            return ['success' => false, 'error' => 'Email already in use.'];
        }

        try {
            $password = bin2hex(random_bytes(4));
            $userId = $this->userModel->createUser([
                'username' => $data['username'],
                'email'    => $data['email'],
                'password' => $password,
                'role'     => 'discipline_master',
            ]);

            $this->notifModel->send($userId, 'Discipline Master Account Created',
                "Welcome! Username: {$data['username']}, Password: {$password}", 'success');

            $this->auditModel->log('discipline_master_created', 'users', $userId, null, $data);

            return [
                'success' => true,
                'user_id' => $userId,
                'password' => $password,
                'message' => 'Discipline Master account created successfully.'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error creating account: ' . $e->getMessage()];
        }
    }

    // ============================================================
    // REPORT GENERATION
    // ============================================================

    /**
     * Generate student report in PDF format
     */
    public function generateStudentReportPDF(int $studentId, int $academicYearId, string $format = 'pdf'): void {
        $reportData = $this->reportModel->getStudentTermReport($studentId, $academicYearId);
        
        if (!$reportData) {
            http_response_code(404);
            die(json_encode(['error' => 'Student or report data not found.']));
        }

        $student = $reportData['student'];
        $fileName = strtolower(str_replace(' ', '_', $student['first_name'] . '_' . $student['student_id']));

        if ($format === 'pdf') {
            $generator = new PDFReportGenerator($reportData, $fileName);
        } else {
            $generator = new ExcelReportGenerator($reportData, $fileName);
        }

        $generator->generate();
    }

    /**
     * Generate class report
     */
    public function generateClassReport(int $classId, int $academicYearId, string $format = 'pdf'): void {
        $reportData = $this->reportModel->getClassReport($classId, $academicYearId);
        
        if (empty($reportData['students'])) {
            http_response_code(404);
            die(json_encode(['error' => 'Class not found or has no students.']));
        }

        $class = $reportData['class'];
        $fileName = strtolower(str_replace(' ', '_', $class['name'] . '_report'));

        if ($format === 'pdf') {
            $generator = new PDFReportGenerator($reportData, $fileName);
        } else {
            $generator = new ExcelReportGenerator($reportData, $fileName);
        }

        $generator->generate();
    }

    /**
     * Update student record
     */
    public function updateStudent(int $studentId, array $data): array {
        $student = $this->studentModel->findById($studentId);
        if (!$student) {
            return ['success' => false, 'error' => 'Student not found.'];
        }

        $this->studentModel->update($studentId, $data);
        $this->auditModel->log('student_updated', 'students', $studentId, $student, $data);

        return ['success' => true, 'message' => 'Student updated successfully.'];
    }
}
