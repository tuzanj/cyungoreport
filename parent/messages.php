<?php
// parent/messages.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_PARENT);

require_once ROOT_PATH . '/controllers/ParentController.php';
require_once ROOT_PATH . '/models/TeacherModel.php';

$parentCtrl   = new ParentController();
$teacherModel = new TeacherModel();
$db           = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.'); redirect('/parent/messages.php');
    }
    if ($_POST['form_action'] === 'send_message') {
        $result = $parentCtrl->sendMessageToTeacher(
            currentUserId(),
            (int)$_POST['receiver_id'],
            trim($_POST['subject'] ?? ''),
            trim($_POST['body'] ?? '')
        );
        setFlash($result['success'] ? 'success' : 'danger', $result['message'] ?? $result['error']);
        redirect('/parent/messages.php');
    }
}

$messages = $parentCtrl->getMessages(currentUserId());
$teachers = $teacherModel->getAllWithDept();

include ROOT_PATH . '/views/parent/messages.php';
