<?php
// student/schedule.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_STUDENT);

require_once ROOT_PATH . '/controllers/StudentController.php';

$studentCtrl = new StudentController();
$schedule    = $studentCtrl->getSchedule(currentUserId());

include ROOT_PATH . '/views/student/schedule.php';
