<?php
// views/auth/reset_password.php
// Variables: $reset (array from DB)
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-900 to-blue-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8" x-data="{ showPass: false, showConfirm: false }">
            <div class="text-center mb-6">
                <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-lock-open text-green-600 text-2xl"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-800">Set New Password</h2>
            </div>

            <?php if ($flash): ?>
            <?php $alertBg = ['success'=>'bg-green-50 border-green-300 text-green-800','danger'=>'bg-red-50 border-red-300 text-red-800']; ?>
            <div class="mb-4 px-4 py-3 rounded-lg border <?= $alertBg[$flash['type']] ?? '' ?> text-sm">
                <?= $flash['message'] ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/index.php?action=process_reset_password">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="token" value="<?= e($reset['token'] ?? '') ?>">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">New Password</label>
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'" name="password" required minlength="8"
                               class="w-full pr-10 px-4 py-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               placeholder="Minimum 8 characters">
                        <button type="button" @click="showPass=!showPass" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="fa-solid" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm Password</label>
                    <div class="relative">
                        <input :type="showConfirm ? 'text' : 'password'" name="confirm_password" required minlength="8"
                               class="w-full pr-10 px-4 py-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               placeholder="Repeat your password">
                        <button type="button" @click="showConfirm=!showConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="fa-solid" :class="showConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-lg transition">
                    Update Password
                </button>
            </form>
        </div>
    </div>
</body>
</html>
