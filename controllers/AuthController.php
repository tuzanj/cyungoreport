<?php
// controllers/AuthController.php

require_once ROOT_PATH . '/models/UserModel.php';
require_once ROOT_PATH . '/models/AuditModel.php';

class AuthController {
    private UserModel $userModel;
    private AuditModel $auditModel;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->auditModel = new AuditModel();
    }

    public function showLogin(): void {
        if (isLoggedIn()) {
            $this->redirectByRole(currentRole());
        }
        include ROOT_PATH . '/views/auth/login.php';
    }

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/index.php');
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($csrf)) {
            setFlash('danger', 'Invalid request. Please try again.');
            redirect('/index.php');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            setFlash('danger', 'Username and password are required.');
            redirect('/index.php');
        }

        $user = $this->userModel->findByUsernameOrEmail($username);

        if (!$user) {
            $this->auditModel->log('login_failed', 'users', null, null, ['username_or_email' => $username, 'reason' => 'not_found']);
            setFlash('danger', 'Invalid credentials.');
            redirect('/index.php');
        }

        $isLocked = $this->userModel->isLocked($user);
        $passwordValid = password_verify($password, $user['password_hash']);

        // Check active status before login
        if (!$user['is_active']) {
            setFlash('danger', 'Your account has been deactivated. Contact administrator.');
            redirect('/index.php');
        }

        if ($passwordValid) {
            if ($isLocked) {
                $this->userModel->resetFailedAttempts($user['id']);
            }

            createAuthenticatedSession($user);

            $this->userModel->resetFailedAttempts($user['id']);
            $this->auditModel->log('login_success', 'users', $user['id']);
            session_write_close();
            $this->redirectByRole($user['role']);
        }

        if ($isLocked) {
            $unlockTime = date('H:i', strtotime($user['locked_until']));
            setFlash('warning', "Account is locked. Try again after {$unlockTime}.");
            redirect('/index.php');
        }

        $this->userModel->incrementFailedAttempts($user['id']);
        $remaining = MAX_LOGIN_ATTEMPTS - ($user['failed_attempts'] + 1);

        if ($remaining <= 0) {
            $this->userModel->lockAccount($user['id'], LOCK_DURATION_MINUTES);
            $this->auditModel->log('account_locked', 'users', $user['id']);
            setFlash('danger', 'Too many failed attempts. Account locked for ' . LOCK_DURATION_MINUTES . ' minutes.');
        } else {
            setFlash('danger', "Invalid credentials. {$remaining} attempt(s) remaining.");
        }
        redirect('/index.php');
    }

    public function logout(): void {
        if (isLoggedIn()) {
            $this->auditModel->log('logout', 'users', currentUserId());
        }
        destroySecureSession();
        redirect('/index.php?msg=logged_out');
    }

    public function showForgotPassword(): void {
        include ROOT_PATH . '/views/auth/forgot_password.php';
    }

    public function processForgotPassword(): void {
        $email = trim($_POST['email'] ?? '');
        $user = $this->userModel->findByEmail($email);

        // Always show success to prevent email enumeration
        if ($user) {
            $token = $this->userModel->createPasswordReset($user['id']);
            $resetUrl = BASE_URL . "/index.php?action=reset_password&token={$token}";
            // Simulate sending email
            $this->auditModel->log('password_reset_requested', 'users', $user['id']);
            setFlash('success', "Password reset link: <a href='{$resetUrl}' class='underline'>Click here</a> (simulated - valid 1 hour)");
        } else {
            setFlash('success', 'If the email exists, a reset link has been sent.');
        }
        redirect('/index.php?action=forgot_password');
    }

    public function showResetPassword(string $token): void {
        $reset = $this->userModel->findValidResetToken($token);
        if (!$reset) {
            setFlash('danger', 'Invalid or expired reset link.');
            redirect('/index.php');
        }
        include ROOT_PATH . '/views/auth/reset_password.php';
    }

    public function processResetPassword(): void {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $reset = $this->userModel->findValidResetToken($token);
        if (!$reset) {
            setFlash('danger', 'Invalid or expired reset link.');
            redirect('/index.php');
        }

        if (strlen($password) < 8) {
            setFlash('danger', 'Password must be at least 8 characters.');
            redirect("/index.php?action=reset_password&token={$token}");
        }

        if ($password !== $confirm) {
            setFlash('danger', 'Passwords do not match.');
            redirect("/index.php?action=reset_password&token={$token}");
        }

        $this->userModel->updatePassword((int)$reset['user_id'], $password);
        $this->userModel->markResetTokenUsed($token);
        $this->auditModel->log('password_reset_completed', 'users', (int)$reset['user_id']);

        setFlash('success', 'Password updated successfully. Please log in.');
        redirect('/index.php');
    }

    private function redirectByRole(string $role): never {
        match($role) {
            ROLE_ADMIN             => redirect('/admin/dashboard.php'),
            ROLE_SECRETARY         => redirect('/secretary/dashboard.php'),
            ROLE_TEACHER           => redirect('/teacher/dashboard.php'),
            ROLE_STUDENT           => redirect('/student/dashboard.php'),
            ROLE_PARENT            => redirect('/parent/dashboard.php'),
            ROLE_DISCIPLINE_MASTER => redirect('/admin/dashboard.php'),
            default                => redirect('/index.php?msg=unauthorized'),
        };
    }
}
