<?php
// admin/teachers.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_ADMIN);

require_once ROOT_PATH . '/controllers/AdminController.php';
require_once ROOT_PATH . '/models/TeacherModel.php';
require_once ROOT_PATH . '/models/CourseModel.php';
require_once ROOT_PATH . '/models/ClassModel.php';

$adminCtrl    = new AdminController();
$teacherModel = new TeacherModel();
$courseModel  = new CourseModel();
$classModel   = new ClassModel();
$db           = Database::getInstance();

$action  = $_GET['action'] ?? '';
$teacher = null;

$currentYear   = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$currentYearId = $currentYear ? (int)$currentYear['id'] : 0;
$departments   = $db->fetchAll("SELECT * FROM departments ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.');
        redirect('/admin/teachers.php');
    }

    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'create') {
        $result = $adminCtrl->createTeacher($_POST);
        if ($result['success']) {
            setFlash('success', "Teacher created. Employee ID: {$result['employee_id']}, Password: {$result['password']}");
        } else {
            setFlash('danger', $result['error'] ?? 'Error creating teacher.');
        }
        redirect('/admin/teachers.php');
    }

    if ($formAction === 'update') {
        $id = (int)($_POST['teacher_id'] ?? 0);
        $teacherModel->update($id, $_POST);
        (new \AuditModel())->log('teacher_updated', 'teachers', $id);
        setFlash('success', 'Teacher updated.');
        redirect('/admin/teachers.php');
    }

    if ($formAction === 'assign_course') {
        $result = $adminCtrl->assignCourseToTeacher(
            (int)$_POST['class_id'],
            (int)$_POST['course_id'],
            (int)$_POST['teacher_id'],
            $currentYearId
        );
        setFlash($result['success'] ? 'success' : 'danger', $result['message'] ?? $result['error']);
        redirect('/admin/teachers.php');
    }
}

if ($action === 'toggle') {
    $userId = (int)($_GET['user_id'] ?? 0);
    $adminCtrl->toggleUserStatus($userId);
    setFlash('success', 'User status updated.');
    redirect('/admin/teachers.php');
}

if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $teacher = $teacherModel->findById($id);
}

$teachers = $teacherModel->getAllWithDept();
$courses  = $courseModel->getAllWithDept();
$classes  = $classModel->getForYear($currentYearId);

include ROOT_PATH . '/views/admin/teachers.php';
