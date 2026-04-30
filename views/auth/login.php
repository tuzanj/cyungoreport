<?php
// views/auth/login.php
$pageTitle = 'Sign In';
$flash = getFlash();

$msgMap = [
    'session_expired' => ['type' => 'warning', 'message' => 'Your session has expired. Please sign in again.'],
    'unauthorized'    => ['type' => 'danger',  'message' => 'Access denied. Insufficient permissions.'],
    'logged_out'      => ['type' => 'info',    'message' => 'You have been signed out successfully.'],
];
$queryMsg = $_GET['msg'] ?? null;
if ($queryMsg && isset($msgMap[$queryMsg])) {
    $flash = $flash ?: $msgMap[$queryMsg];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-900 via-indigo-800 to-blue-900 flex items-center justify-center p-4">

    <!-- Background decoration -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-white/5 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-blue-400/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-md">
        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-8 py-8 text-center">
                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-school text-white text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white"><?= APP_NAME ?></h1>
                <p class="text-indigo-200 text-sm mt-1">School Management System</p>
            </div>

            <!-- Form -->
            <div class="px-8 py-8" x-data="{ showPass: false }">
                <h2 class="text-xl font-semibold text-slate-800 mb-6">Welcome back</h2>

                <?php if ($flash): ?>
                <?php
                $alertBg = ['success'=>'bg-green-50 border-green-300 text-green-800','danger'=>'bg-red-50 border-red-300 text-red-800','warning'=>'bg-yellow-50 border-yellow-300 text-yellow-800','info'=>'bg-blue-50 border-blue-300 text-blue-800'];
                $bg = $alertBg[$flash['type']] ?? $alertBg['info'];
                ?>
                <div class="mb-4 px-4 py-3 rounded-lg border <?= $bg ?> text-sm">
                    <?= $flash['message'] ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>/index.php?action=login">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                    <!-- Username -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Username</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <i class="fa-solid fa-user text-sm"></i>
                            </span>
                            <input type="text" name="username" required autocomplete="username"
                                   value="<?= e($_POST['username'] ?? '') ?>"
                                   class="w-full pl-9 pr-4 py-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                   placeholder="Enter your username">
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <i class="fa-solid fa-lock text-sm"></i>
                            </span>
                            <input :type="showPass ? 'text' : 'password'" name="password" required autocomplete="current-password"
                                   class="w-full pl-9 pr-11 py-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                   placeholder="Enter your password">
                            <button type="button" @click="showPass = !showPass"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                                <i class="fa-solid" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        Sign In
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <a href="<?= BASE_URL ?>/index.php?action=forgot_password"
                       class="text-sm text-indigo-600 hover:text-indigo-800 hover:underline transition-colors">
                        <i class="fa-solid fa-key mr-1"></i>Forgot password?
                    </a>
                </div>

                <div class="mt-6 text-center text-slate-500 text-sm">
                    Don't have an account?
                    <a href="<?= BASE_URL ?>/index.php?action=register"
                       class="text-indigo-600 hover:text-indigo-800 hover:underline transition-colors">
                        <i class="fa-solid fa-user-plus mr-1"></i>Register here
                    </a>
            </div>
        </div>

        <p class="text-center text-white/40 text-xs mt-6">&copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?></p>
    </div>
</body>
</html>
