<?php
// views/secretary/register_student.php
$pageTitle  = 'Register Student';
$activePage = '/secretary/register_student.php';
$role = 'secretary';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="max-w-4xl">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-800">Register New Student</h2>
        <p class="text-sm text-slate-500 mt-0.5">Fill in all required fields to create a new student account</p>
    </div>

    <?php if (!empty($result)): ?>
    <div class="mb-6 bg-green-50 border border-green-200 rounded-2xl p-5">
        <div class="flex items-start gap-3">
            <i class="fa-solid fa-circle-check text-green-500 text-xl mt-0.5"></i>
            <div>
                <h4 class="font-semibold text-green-800">Student Registered Successfully!</h4>
                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
                    <div class="bg-white rounded-lg p-3 border border-green-200">
                        <div class="text-xs text-slate-500">Student ID</div>
                        <div class="font-mono font-bold text-teal-700"><?= e($result['student_id']) ?></div>
                    </div>
                    <div class="bg-white rounded-lg p-3 border border-green-200">
                        <div class="text-xs text-slate-500">Username</div>
                        <div class="font-semibold"><?= e($result['username']) ?></div>
                    </div>
                    <div class="bg-white rounded-lg p-3 border border-green-200">
                        <div class="text-xs text-slate-500">Password</div>
                        <div class="font-mono font-bold text-indigo-700"><?= e($result['password']) ?></div>
                    </div>
                </div>
                <p class="text-xs text-green-700 mt-2"><i class="fa-solid fa-envelope mr-1"></i>Credentials have been sent to the student's email.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <form method="POST" action="<?= BASE_URL ?>/secretary/register_student.php" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <!-- Personal Information -->
            <h4 class="font-semibold text-slate-700 text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fa-solid fa-user text-teal-500"></i> Personal Information
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name *</label>
                    <input type="text" name="first_name" required value="<?= e($_POST['first_name'] ?? '') ?>"
                           class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name *</label>
                    <input type="text" name="last_name" required value="<?= e($_POST['last_name'] ?? '') ?>"
                           class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Gender *</label>
                    <select name="gender" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">Select gender</option>
                        <option value="male" <?= (($_POST['gender']??'')=='male')?'selected':'' ?>>Male</option>
                        <option value="female" <?= (($_POST['gender']??'')=='female')?'selected':'' ?>>Female</option>
                        <option value="other" <?= (($_POST['gender']??'')=='other')?'selected':'' ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Date of Birth *</label>
                    <input type="date" name="date_of_birth" required value="<?= e($_POST['date_of_birth'] ?? '') ?>"
                           max="<?= date('Y-m-d') ?>"
                           class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone</label>
                    <input type="tel" name="phone" value="<?= e($_POST['phone'] ?? '') ?>"
                           class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email *</label>
                    <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"
                           class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Address</label>
                    <input type="text" name="address" value="<?= e($_POST['address'] ?? '') ?>"
                           class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Emergency Contact</label>
                    <input type="text" name="emergency_contact" value="<?= e($_POST['emergency_contact'] ?? '') ?>"
                           class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                           placeholder="Name & phone">
                </div>
            </div>

            <hr class="border-slate-100 mb-6">

            <!-- Enrollment -->
            <h4 class="font-semibold text-slate-700 text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fa-solid fa-graduation-cap text-teal-500"></i> Enrollment
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Trade *</label>
                    <select name="trade_id" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">Select trade</option>
                        <?php foreach ($trades ?? [] as $tr): ?>
                        <option value="<?= $tr['id'] ?>" <?= (($_POST['trade_id']??'')==$tr['id'])?'selected':'' ?>><?= e($tr['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Class *</label>
                    <select name="class_id" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">Select class</option>
                        <?php foreach ($classes ?? [] as $cl): ?>
                        <option value="<?= $cl['id'] ?>" <?= (($_POST['class_id']??'')==$cl['id'])?'selected':'' ?>><?= e($cl['name']) ?> (<?= $cl['enrolled'] ?>/<?= $cl['max_students'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Academic Year *</label>
                    <select name="academic_year_id" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <?php foreach ($years ?? [] as $y): ?>
                        <option value="<?= $y['id'] ?>" <?= $y['is_current'] ? 'selected' : '' ?>><?= e($y['name']) ?> <?= $y['is_current'] ? '(Current)' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                    <i class="fa-solid fa-user-plus"></i> Register Student
                </button>
                <button type="reset" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">Clear Form</button>
            </div>
        </form>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
