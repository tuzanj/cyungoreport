<?php
// secretary/register_student.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_SECRETARY);

require_once ROOT_PATH . '/controllers/SecretaryController.php';
require_once ROOT_PATH . '/models/ClassModel.php';

$secCtrl    = new SecretaryController();
$classModel = new ClassModel();
$db         = Database::getInstance();

$currentYear   = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$currentYearId = $currentYear ? (int)$currentYear['id'] : 0;
$years         = $db->fetchAll("SELECT * FROM academic_years ORDER BY start_date DESC");
$classes       = $classModel->getForYear($currentYearId);
$trades        = $db->fetchAll("SELECT * FROM trades ORDER BY name");
$result        = null;
$errors        = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.'); redirect('/secretary/register_student.php');
    }
    $out = $secCtrl->registerStudent();
    if ($out['success']) {
        $result = $out;
        setFlash('success', $out['message']);
    } else {
        $errors = $out['errors'];
        setFlash('danger', implode('<br>', $errors));
    }
}

include ROOT_PATH . '/views/secretary/register_student.php';
