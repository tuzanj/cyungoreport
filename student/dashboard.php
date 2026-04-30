<?php
// student/dashboard.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_STUDENT);

require_once ROOT_PATH . '/controllers/StudentController.php';

$studentCtrl = new StudentController();
$data        = $studentCtrl->getDashboard(currentUserId());

if (empty($data)) { setFlash('danger', 'Student profile not found.'); redirect('/index.php'); }

include ROOT_PATH . '/views/student/dashboard.php';
