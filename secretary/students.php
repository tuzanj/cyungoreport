<?php
// secretary/students.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_SECRETARY);

require_once ROOT_PATH . '/models/StudentModel.php';

$studentModel = new StudentModel();
$students     = $studentModel->getAllWithDetails();
$search       = trim($_GET['q'] ?? '');

if ($search) {
    $students = array_filter($students, fn($s) =>
        stripos($s['student_id'], $search) !== false ||
        stripos($s['first_name'].' '.$s['last_name'], $search) !== false ||
        stripos($s['email'], $search) !== false
    );
}

$pageTitle  = 'All Students';
$activePage = '/secretary/students.php';
$role = 'secretary';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">All Students</h2>
        <p class="text-sm text-slate-500 mt-0.5"><?= count($students) ?> students registered</p>
    </div>
    <div class="flex gap-2">
        <form method="GET" action="" class="flex gap-2">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search students…"
                   class="px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 w-52">
            <button type="submit" class="bg-teal-600 text-white px-4 py-2.5 rounded-lg text-sm font-semibold">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>
        <a href="<?= BASE_URL ?>/secretary/register_student.php"
           class="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
            <i class="fa-solid fa-user-plus"></i> Register New
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Student ID</th>
                    <th class="px-5 py-3 text-left">Name</th>
                    <th class="px-5 py-3 text-left">Email</th>
                    <th class="px-5 py-3 text-left">Class</th>
                    <th class="px-5 py-3 text-center">Status</th>
                    <th class="px-5 py-3 text-left">Enrolled</th>
                    <th class="px-5 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($students as $s): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-mono font-semibold text-teal-700"><?= e($s['student_id']) ?></td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center text-teal-700 font-bold text-xs">
                                <?= strtoupper(substr($s['first_name'],0,1).substr($s['last_name'],0,1)) ?>
                            </div>
                            <span class="font-medium"><?= e($s['first_name'].' '.$s['last_name']) ?></span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-slate-500"><?= e($s['email']) ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= e($s['class_name'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-center">
                        <?php $statusColor = ['active'=>'bg-green-100 text-green-700','suspended'=>'bg-orange-100 text-orange-700','graduated'=>'bg-blue-100 text-blue-700','withdrawn'=>'bg-red-100 text-red-700']; ?>
                        <span class="badge <?= $statusColor[$s['status']] ?? 'bg-slate-100 text-slate-600' ?>"><?= ucfirst($s['status']) ?></span>
                    </td>
                    <td class="px-5 py-3 text-slate-400"><?= $s['enrollment_date'] ? formatDate($s['enrollment_date']) : '—' ?></td>
                    <td class="px-5 py-3">
                        <a href="<?= BASE_URL ?>/secretary/link_parent.php?student_id=<?= $s['id'] ?>"
                           class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Link Parent">
                            <i class="fa-solid fa-link"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($students)): ?>
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">No students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
