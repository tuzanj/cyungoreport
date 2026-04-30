<?php
// student/notifications.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_STUDENT);

require_once ROOT_PATH . '/models/NotificationModel.php';

$notifModel = new NotificationModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.'); redirect('/student/notifications.php');
    }
    $formAction = $_POST['form_action'] ?? '';
    if ($formAction === 'mark_read') {
        $notifModel->markRead((int)$_POST['notif_id'], currentUserId());
    }
    if ($formAction === 'mark_all_read') {
        $notifModel->markAllRead(currentUserId());
    }
    redirect('/student/notifications.php');
}

// Mark all read via GET (from topbar link)
if (isset($_GET['mark_all'])) {
    $notifModel->markAllRead(currentUserId());
    redirect('/student/notifications.php');
}

// Mark single read via GET
if (isset($_GET['id'])) {
    $notifModel->markRead((int)$_GET['id'], currentUserId());
    redirect('/student/notifications.php');
}

$notifications = $notifModel->getForUser(currentUserId());
$role = 'student';

include ROOT_PATH . '/views/shared/notifications.php';
