<?php
// index.php - Front controller / Router

define('ROOT_PATH', __DIR__);

require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();

// Session timeout check
if (!empty($_SESSION['user_id']) && !isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php?msg=session_expired');
    exit;
}

$action = $_GET['action'] ?? '';

require_once ROOT_PATH . '/controllers/AuthController.php';
$auth = new AuthController();

switch ($action) {
    case 'login':
        $auth->login();
        break;
    case 'logout':
        $auth->logout();
        break;
    case 'forgot_password':
        $auth->showForgotPassword();
        break;
    case 'process_forgot_password':
        $auth->processForgotPassword();
        break;
    case 'reset_password':
        $token = $_GET['token'] ?? '';
        $auth->showResetPassword($token);
        break;
    case 'process_reset_password':
        $auth->processResetPassword();
        break;
    default:
        $auth->showLogin();
        break;
}
