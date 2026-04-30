<?php
// admin/audit.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_ADMIN);

require_once ROOT_PATH . '/models/AuditModel.php';
$auditModel = new AuditModel();
$logs = $auditModel->getRecent(200);

include ROOT_PATH . '/views/admin/audit.php';
