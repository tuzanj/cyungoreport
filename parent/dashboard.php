<?php
// parent/dashboard.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_PARENT);

require_once ROOT_PATH . '/controllers/ParentController.php';

$parentCtrl = new ParentController();
$data       = $parentCtrl->getDashboard(currentUserId());

if (empty($data)) { setFlash('danger', 'Parent profile not found.'); redirect('/index.php'); }

include ROOT_PATH . '/views/parent/dashboard.php';
