<?php
// admin/dashboard.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_ADMIN);

require_once ROOT_PATH . '/controllers/AdminController.php';

$adminCtrl = new AdminController();
$stats = $adminCtrl->getDashboardStats();

include ROOT_PATH . '/views/admin/dashboard.php';
