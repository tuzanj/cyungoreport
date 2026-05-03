<?php
// config/app.php - Global application constants and helpers

define('APP_NAME', 'CYUNGO TSS REPORT');
define('APP_LOGO_URL', 'http://cyungotss.ac.rw/assets/img/logo.jpeg');
define('APP_ADDRESS', 'http://cyungotss.ac.rw/report');
define('APP_VERSION', '1.0.0');

if (!defined('BASE_URL')) {
    if (!empty($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443' ? 'https' : 'http';
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        define('BASE_URL', $scheme . '://' . $_SERVER['HTTP_HOST'] . $basePath);
    } else {
        define('BASE_URL', 'http://cyungotss.ac.rw/report');
    }
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Session lifetime in seconds (2 hours)
define('SESSION_LIFETIME', 7200);

// Max failed login attempts before lock
define('MAX_LOGIN_ATTEMPTS', 5);

// Lock duration in minutes
define('LOCK_DURATION_MINUTES', 30);

// Upload directories
define('UPLOAD_DIR', ROOT_PATH . '/public/uploads/');
define('UPLOAD_URL', BASE_URL . '/public/uploads/');

// Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_SECRETARY', 'secretary');
define('ROLE_TEACHER', 'teacher');
define('ROLE_STUDENT', 'student');
define('ROLE_PARENT', 'parent');
define('ROLE_DISCIPLINE_MASTER', 'discipline_master');

// Start secure session
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
}

// Redirect helper
function redirect(string $url): never {
    header("Location: " . BASE_URL . $url);
    exit;
}

// Check if user is logged in
function isLoggedIn(): bool {
    startSecureSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Require login or redirect
function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect('/index.php?msg=session_expired');
    }
}

// Require specific role
function requireRole(string|array $roles): void {
    requireLogin();
    $allowed = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['role'], $allowed)) {
        redirect('/index.php?msg=unauthorized');
    }
}

// Current logged-in user id
function currentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

// Current role
function currentRole(): string {
    return $_SESSION['role'] ?? '';
}

// Current user name
function currentUserName(): string {
    return $_SESSION['username'] ?? '';
}

// CSRF token generation/validation
function generateCsrfToken(): string {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Flash message
function setFlash(string $type, string $message): void {
    startSecureSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    startSecureSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Sanitize output
function e(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

// Format date
function formatDate(string $date, string $format = 'd M Y'): string {
    return date($format, strtotime($date));
}

// Get letter grade from numeric score
function getLetterGrade(float $score): string {
    if ($score >= 90) return 'A+';
    if ($score >= 85) return 'A';
    if ($score >= 80) return 'A-';
    if ($score >= 75) return 'B+';
    if ($score >= 70) return 'B';
    if ($score >= 65) return 'B-';
    if ($score >= 60) return 'C+';
    if ($score >= 55) return 'C';
    if ($score >= 50) return 'C-';
    if ($score >= 45) return 'D';
    return 'F';
}

// Get GPA point from letter grade
function getGpaPoint(string $letter): float {
    $map = [
        'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
        'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
        'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
        'D'  => 1.0, 'F' => 0.0,
    ];
    return $map[$letter] ?? 0.0;
}
