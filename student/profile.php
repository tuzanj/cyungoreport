<?php
// student/profile.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_STUDENT);

require_once ROOT_PATH . '/controllers/StudentController.php';
require_once ROOT_PATH . '/models/StudentModel.php';
require_once ROOT_PATH . '/models/UserModel.php';

$studentCtrl  = new StudentController();
$studentModel = new StudentModel();
$userModel    = new UserModel();

$student = $studentModel->findByUserId(currentUserId());
if (!$student) redirect('/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.'); redirect('/student/profile.php');
    }
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'update_profile') {
        $result = $studentCtrl->updateProfile(currentUserId(), $_POST);
        setFlash($result['success'] ? 'success' : 'danger', $result['message'] ?? $result['error']);
        redirect('/student/profile.php');
    }

    if ($formAction === 'change_password') {
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (strlen($newPass) < 8) {
            setFlash('danger', 'Password must be at least 8 characters.'); redirect('/student/profile.php');
        }
        if ($newPass !== $confirm) {
            setFlash('danger', 'Passwords do not match.'); redirect('/student/profile.php');
        }
        $userModel->updatePassword(currentUserId(), $newPass);
        setFlash('success', 'Password updated successfully.');
        redirect('/student/profile.php');
    }
}

// Reload student after possible update
$student = $studentModel->findByUserId(currentUserId());

include ROOT_PATH . '/views/student/profile.php';
