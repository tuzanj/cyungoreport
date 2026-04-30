<?php
// teacher/dashboard.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_TEACHER);

require_once ROOT_PATH . '/controllers/TeacherController.php';
require_once ROOT_PATH . '/models/TeacherModel.php';

$teacherModel = new TeacherModel();
$teacherCtrl  = new TeacherController();

$teacher = $teacherModel->findByUserId(currentUserId());
if (!$teacher) { setFlash('danger', 'Teacher profile not found.'); redirect('/index.php'); }

$db          = Database::getInstance();
$currentYear = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$yearId      = $currentYear ? (int)$currentYear['id'] : 0;

$data = $teacherCtrl->getDashboard((int)$teacher['id'], $yearId);

include ROOT_PATH . '/views/teacher/dashboard.php';
