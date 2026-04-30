<?php
// student/marks.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_STUDENT);

require_once ROOT_PATH . '/controllers/StudentController.php';
require_once ROOT_PATH . '/models/StudentModel.php';
require_once ROOT_PATH . '/models/MarkModel.php';

$studentCtrl  = new StudentController();
$studentModel = new StudentModel();
$markModel    = new MarkModel();
$db           = Database::getInstance();

$student = $studentModel->findByUserId(currentUserId());
if (!$student) redirect('/index.php');

$currentYear = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$yearId      = $currentYear ? (int)$currentYear['id'] : 0;
$yearName    = $currentYear ? $currentYear['name'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.'); redirect('/student/marks.php');
    }
    if ($_POST['form_action'] === 'raise_claim') {
        $result = $studentCtrl->raiseGradeClaim((int)$_POST['mark_id'], (int)$student['id'], trim($_POST['reason'] ?? ''));
        setFlash($result['success'] ? 'success' : 'danger', $result['message'] ?? $result['error']);
        redirect('/student/marks.php');
    }
}

$marks = $markModel->getStudentMarks((int)$student['id'], $yearId);
$gpa   = $studentModel->getGpa((int)$student['id'], $yearId);

include ROOT_PATH . '/views/student/marks.php';
