<?php
// views/auth/forgot_password.php
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-900 to-blue-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-6">
                <div class="w-14 h-14 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-key text-indigo-600 text-2xl"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-800">Reset Password</h2>
                <p class="text-sm text-slate-500 mt-1">Enter your email to receive a reset link</p>
            </div>

            <?php if ($flash): ?>
            <?php $alertBg = ['success'=>'bg-green-50 border-green-300 text-green-800','danger'=>'bg-red-50 border-red-300 text-red-800','warning'=>'bg-yellow-50 border-yellow-300 text-yellow-800','info'=>'bg-blue-50 border-blue-300 text-blue-800']; ?>
            <div class="mb-4 px-4 py-3 rounded-lg border <?= $alertBg[$flash['type']] ?? '' ?> text-sm">
                <?= $flash['message'] ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/index.php?action=process_forgot_password">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
                    <input type="email" name="email" required
                           class="w-full px-4 py-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="you@school.edu">
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-lg transition">
                    Send Reset Link
                </button>
            </form>
            <div class="mt-4 text-center">
                <a href="<?= BASE_URL ?>/index.php" class="text-sm text-indigo-600 hover:underline">
                    <i class="fa-solid fa-arrow-left mr-1"></i>Back to Sign In
                </a>
            </div>
        </div>
    </div>
</body>
</html>
