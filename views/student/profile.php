<?php
// views/student/profile.php
$pageTitle  = 'My Profile';
$activePage = '/student/profile.php';
$role = 'student';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="max-w-2xl">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-800">My Profile</h2>
        <p class="text-sm text-slate-500 mt-0.5">Update your personal information</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <!-- Profile Header -->
        <div class="bg-gradient-to-r from-violet-600 to-indigo-600 px-6 py-8 text-center text-white">
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3 text-3xl font-bold">
                <?= strtoupper(substr($student['first_name'] ?? '', 0, 1) . substr($student['last_name'] ?? '', 0, 1)) ?>
            </div>
            <div class="text-xl font-bold"><?= e(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')) ?></div>
            <div class="text-violet-200 text-sm mt-1 font-mono"><?= e($student['student_id'] ?? '') ?></div>
            <span class="mt-2 inline-block badge bg-white/20 text-white"><?= ucfirst($student['status'] ?? 'active') ?></span>
        </div>

        <div class="p-6">
            <form method="POST" action="<?= BASE_URL ?>/student/profile.php">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="form_action" value="update_profile">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name</label>
                        <input type="text" name="first_name" value="<?= e($student['first_name'] ?? '') ?>"
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name</label>
                        <input type="text" name="last_name" value="<?= e($student['last_name'] ?? '') ?>"
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone</label>
                        <input type="tel" name="phone" value="<?= e($student['phone'] ?? '') ?>"
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="<?= e($student['date_of_birth'] ?? '') ?>"
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Address</label>
                        <input type="text" name="address" value="<?= e($student['address'] ?? '') ?>"
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Emergency Contact</label>
                        <input type="text" name="emergency_contact" value="<?= e($student['emergency_contact'] ?? '') ?>"
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>

                <div class="mt-5 flex gap-3">
                    <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors">
                        <i class="fa-solid fa-floppy-disk mr-1"></i>Save Changes
                    </button>
                </div>
            </form>

            <hr class="border-slate-100 my-6">

            <!-- Change Password -->
            <h4 class="font-semibold text-slate-700 mb-4">Change Password</h4>
            <form method="POST" action="<?= BASE_URL ?>/student/profile.php">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="form_action" value="change_password">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">New Password</label>
                        <input type="password" name="new_password" minlength="8"
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                               placeholder="Min 8 characters">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm Password</label>
                        <input type="password" name="confirm_password" minlength="8"
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>
                <button type="submit" class="mt-4 bg-slate-700 hover:bg-slate-800 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">
                    Update Password
                </button>
            </form>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
