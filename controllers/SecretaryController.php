<?php
// controllers/SecretaryController.php

require_once ROOT_PATH . '/models/UserModel.php';
require_once ROOT_PATH . '/models/StudentModel.php';
require_once ROOT_PATH . '/models/ParentModel.php';
require_once ROOT_PATH . '/models/EnrollmentModel.php';
require_once ROOT_PATH . '/models/ClassModel.php';
require_once ROOT_PATH . '/models/NotificationModel.php';
require_once ROOT_PATH . '/models/AuditModel.php';

class SecretaryController {
    private UserModel $userModel;
    private StudentModel $studentModel;
    private ParentModel $parentModel;
    private EnrollmentModel $enrollModel;
    private ClassModel $classModel;
    private NotificationModel $notifModel;
    private AuditModel $auditModel;

    public function __construct() {
        $this->userModel   = new UserModel();
        $this->studentModel = new StudentModel();
        $this->parentModel  = new ParentModel();
        $this->enrollModel  = new EnrollmentModel();
        $this->classModel   = new ClassModel();
        $this->notifModel   = new NotificationModel();
        $this->auditModel   = new AuditModel();
    }

    public function registerStudent(): array {
        $data = [
            'first_name'        => trim($_POST['first_name'] ?? ''),
            'last_name'         => trim($_POST['last_name'] ?? ''),
            'gender'            => $_POST['gender'] ?? '',
            'date_of_birth'     => $_POST['date_of_birth'] ?? '',
            'phone'             => trim($_POST['phone'] ?? ''),
            'address'           => trim($_POST['address'] ?? ''),
            'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
            'email'             => trim($_POST['email'] ?? ''),
            'class_id'          => (int)($_POST['class_id'] ?? 0),
            'academic_year_id'  => (int)($_POST['academic_year_id'] ?? 0),
        ];

        $errors = $this->validateStudentData($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Duplicate check
        $duplicate = $this->studentModel->checkDuplicate($data['first_name'], $data['last_name'], $data['date_of_birth']);
        if ($duplicate) {
            return ['success' => false, 'errors' => ['Student with same name and birth date already exists. Student ID: ' . $duplicate['student_id']]];
        }

        // Create user account
        $username = strtolower($data['first_name'] . '.' . $data['last_name'] . rand(10, 99));
        $password = bin2hex(random_bytes(4)); // 8-char random password
        $userId = $this->userModel->createUser([
            'username' => $username,
            'email'    => $data['email'],
            'password' => $password,
            'role'     => ROLE_STUDENT,
        ]);

        // Create student record
        $studentId = $this->studentModel->generateStudentId();
        $sId = $this->studentModel->create(array_merge($data, ['user_id' => $userId, 'student_id' => $studentId]));

        // Enroll in class
        if ($data['class_id'] && $data['academic_year_id']) {
            $this->enrollModel->enroll($sId, $data['class_id'], $data['academic_year_id']);
        }

        // Log credentials
        Database::getInstance()->insert(
            "INSERT INTO credentials_log (student_id, sent_to, method) VALUES (?, ?, 'email')",
            [$sId, $data['email']]
        );

        // Notification
        $this->notifModel->send($userId, 'Welcome to ' . APP_NAME, "Your account has been created. Username: {$username}, Password: {$password}", 'success');

        $this->auditModel->log('student_registered', 'students', $sId, null, ['student_id' => $studentId, 'name' => $data['first_name'] . ' ' . $data['last_name']]);

        return [
            'success'    => true,
            'student_id' => $studentId,
            'username'   => $username,
            'password'   => $password,
            'message'    => 'Student registered successfully.',
        ];
    }

    public function linkParent(int $studentId, array $parentData): array {
        // Check if user account needed
        $parentUserId = (int)($parentData['existing_user_id'] ?? 0);

        if (!$parentUserId) {
            $email = trim($parentData['email'] ?? '');
            $password = bin2hex(random_bytes(4));
            $username = strtolower($parentData['first_name'] . '.' . $parentData['last_name'] . rand(10,99));

            $parentUserId = $this->userModel->createUser([
                'username' => $username,
                'email'    => $email,
                'password' => $password,
                'role'     => ROLE_PARENT,
            ]);

            $this->parentModel->create(array_merge($parentData, ['user_id' => $parentUserId]));
            $parentDbId = $this->parentModel->findByUserId($parentUserId)['id'];

            $this->notifModel->send($parentUserId, 'Parent Account Created', "Username: {$username}, Password: {$password}", 'success');
        } else {
            $parentRecord = $this->parentModel->findByUserId($parentUserId);
            $parentDbId = $parentRecord ? $parentRecord['id'] : null;
        }

        if (!$parentDbId) {
            return ['success' => false, 'error' => 'Parent record not found.'];
        }

        $this->parentModel->linkStudent($parentDbId, $studentId, true);
        $this->auditModel->log('parent_linked', 'parent_student', $studentId);

        return ['success' => true, 'message' => 'Parent linked successfully.'];
    }

    private function validateStudentData(array $data): array {
        $errors = [];
        if (empty($data['first_name'])) $errors[] = 'First name is required.';
        if (empty($data['last_name'])) $errors[] = 'Last name is required.';
        if (empty($data['date_of_birth'])) $errors[] = 'Date of birth is required.';
        if (empty($data['gender'])) $errors[] = 'Gender is required.';
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (!empty($data['email']) && $this->userModel->findByEmail($data['email'])) $errors[] = 'Email already in use.';
        return $errors;
    }

    public function getEnrollmentReport(int $yearId): array {
        return $this->enrollModel->generateEnrollmentReport($yearId);
    }
}
