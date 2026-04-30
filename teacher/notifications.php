<?php
// teacher/notifications.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_TEACHER);

require_once ROOT_PATH . '/models/NotificationModel.php';
$notifModel = new NotificationModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.'); redirect('/teacher/notifications.php');
    }
    $fa = $_POST['form_action'] ?? '';
    if ($fa === 'mark_read')     $notifModel->markRead((int)$_POST['notif_id'], currentUserId());
    if ($fa === 'mark_all_read') $notifModel->markAllRead(currentUserId());
    redirect('/teacher/notifications.php');
}
if (isset($_GET['mark_all'])) { $notifModel->markAllRead(currentUserId()); redirect('/teacher/notifications.php'); }
if (isset($_GET['id']))       { $notifModel->markRead((int)$_GET['id'], currentUserId()); redirect('/teacher/notifications.php'); }

$notifications = $notifModel->getForUser(currentUserId());
$role = 'teacher';
include ROOT_PATH . '/views/shared/notifications.php';
