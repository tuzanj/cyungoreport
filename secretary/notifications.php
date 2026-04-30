<?php
// secretary/notifications.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_SECRETARY);

require_once ROOT_PATH . '/models/NotificationModel.php';
$notifModel = new NotificationModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.'); redirect('/secretary/notifications.php');
    }
    $fa = $_POST['form_action'] ?? '';
    if ($fa === 'mark_read')     $notifModel->markRead((int)$_POST['notif_id'], currentUserId());
    if ($fa === 'mark_all_read') $notifModel->markAllRead(currentUserId());
    redirect('/secretary/notifications.php');
}
if (isset($_GET['mark_all'])) { $notifModel->markAllRead(currentUserId()); redirect('/secretary/notifications.php'); }
if (isset($_GET['id']))       { $notifModel->markRead((int)$_GET['id'], currentUserId()); redirect('/secretary/notifications.php'); }

$notifications = $notifModel->getForUser(currentUserId());
$role = 'secretary';
include ROOT_PATH . '/views/shared/notifications.php';
