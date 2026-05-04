<?php
// admin/courses.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_ADMIN);

require_once ROOT_PATH . '/controllers/AdminController.php';
require_once ROOT_PATH . '/models/CourseModel.php';
require_once ROOT_PATH . '/models/AuditModel.php';

$adminCtrl   = new AdminController();
$courseModel = new CourseModel();
$db          = Database::getInstance();

$action = $_GET['action'] ?? '';
$course = null;
$gradingCriteria = null;

// Current academic year
try {
    $currentYear     = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
    $currentYearId   = $currentYear ? (int)$currentYear['id'] : 0;
    $departments     = $db->fetchAll("SELECT * FROM trades ORDER BY name");
    $classes         = $db->fetchAll("SELECT * FROM classrooms ORDER BY name");
} catch (Exception $e) {
    error_log("Database error in admin/courses.php: " . $e->getMessage());
    die("Database error. Please ensure migrations are run. Error: " . e($e->getMessage()));
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.');
        redirect('/admin/courses.php');
    }

    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'create') {
        $result = $adminCtrl->createCourse($_POST);
        setFlash($result['success'] ? 'success' : 'danger', $result['message'] ?? $result['error']);
        redirect('/admin/courses.php');
    }

    if ($formAction === 'update') {
        $id = (int)($_POST['course_id'] ?? 0);
        $result = $adminCtrl->updateCourse($id, $_POST);
        setFlash($result['success'] ? 'success' : 'danger', $result['message'] ?? $result['error']);
        redirect('/admin/courses.php');
    }

    if ($formAction === 'delete') {
        $id = (int)($_POST['course_id'] ?? 0);
        $result = $adminCtrl->deleteCourse($id);
        setFlash($result['success'] ? 'success' : 'danger', $result['message'] ?? $result['error']);
        redirect('/admin/courses.php');
    }

    if ($formAction === 'set_grading') {
        $courseId = (int)($_POST['course_id'] ?? 0);
        $yearId   = (int)($_POST['year_id'] ?? $currentYearId);
        $result   = $adminCtrl->setGradingCriteria($courseId, $yearId, $_POST);
        setFlash($result['success'] ? 'success' : 'danger', $result['message'] ?? $result['error']);
        redirect('/admin/courses.php?action=edit&id=' . $courseId);
    }
}

// Edit mode - load course
if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $course = $courseModel->findById($id);
    if (!$course) {
        setFlash('danger', 'Course not found.');
        redirect('/admin/courses.php');
    }
    $gradingCriteria = $courseModel->getGradingCriteria($id, $currentYearId);
}

$courses = $courseModel->getAllWithDept();

include ROOT_PATH . '/views/admin/courses.php';
