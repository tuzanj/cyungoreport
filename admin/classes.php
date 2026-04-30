<?php
// admin/classes.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_ADMIN);

require_once ROOT_PATH . '/controllers/AdminController.php';
require_once ROOT_PATH . '/models/ClassModel.php';

$adminCtrl  = new AdminController();
$classModel = new ClassModel();
$db         = Database::getInstance();

$currentYear   = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$currentYearId = $currentYear ? (int)$currentYear['id'] : 0;
$years         = $db->fetchAll("SELECT * FROM academic_years ORDER BY start_date DESC");
$action        = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.');
        redirect('/admin/classes.php');
    }
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'create') {
        $result = $adminCtrl->createClass([
            'name'             => trim($_POST['name'] ?? ''),
            'grade_level'      => trim($_POST['grade_level'] ?? ''),
            'section'          => trim($_POST['section'] ?? ''),
            'academic_year_id' => (int)($_POST['academic_year_id'] ?? $currentYearId),
            'max_students'     => (int)($_POST['max_students'] ?? 40),
        ]);
        setFlash($result['success'] ? 'success' : 'danger', $result['message'] ?? $result['error'] ?? '');
        redirect('/admin/classes.php');
    }

    if ($formAction === 'delete') {
        $id = (int)($_POST['class_id'] ?? 0);
        $classModel->delete($id);
        setFlash('success', 'Class deleted.');
        redirect('/admin/classes.php');
    }
}

$classes = $classModel->getForYear($currentYearId);

// inline view
$pageTitle  = 'Classes';
$activePage = '/admin/classes.php';
$role = 'admin';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Class Management</h2>
        <p class="text-sm text-slate-500 mt-0.5">Current year: <?= e($currentYear['name'] ?? '—') ?></p>
    </div>
    <a href="?action=new" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
        <i class="fa-solid fa-plus"></i> New Class
    </a>
</div>

<?php if ($action === 'new'): ?>
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
    <h3 class="font-semibold text-slate-800 mb-4">Create New Class</h3>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="form_action" value="create">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Class Name *</label>
                <input type="text" name="name" required placeholder="e.g. Grade 10A"
                       class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Grade Level</label>
                <input type="text" name="grade_level" placeholder="e.g. Grade 10"
                       class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Section</label>
                <input type="text" name="section" placeholder="e.g. A"
                       class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Max Students</label>
                <input type="number" name="max_students" value="40" min="1"
                       class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Academic Year</label>
                <select name="academic_year_id" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <?php foreach ($years as $y): ?>
                    <option value="<?= $y['id'] ?>" <?= $y['is_current'] ? 'selected' : '' ?>><?= e($y['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex gap-3 mt-4">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">
                <i class="fa-solid fa-floppy-disk mr-1"></i>Create Class
            </button>
            <a href="" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-slate-800">All Classes <span class="text-slate-400 font-normal text-sm">(<?= count($classes) ?>)</span></h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Class Name</th>
                    <th class="px-5 py-3 text-left">Grade Level</th>
                    <th class="px-5 py-3 text-left">Section</th>
                    <th class="px-5 py-3 text-center">Enrolled</th>
                    <th class="px-5 py-3 text-center">Capacity</th>
                    <th class="px-5 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($classes as $c): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-semibold"><?= e($c['name']) ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= e($c['grade_level'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= e($c['section'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-center">
                        <span class="font-semibold <?= $c['enrolled'] >= $c['max_students'] ? 'text-red-600' : 'text-green-600' ?>">
                            <?= $c['enrolled'] ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-center text-slate-500"><?= $c['max_students'] ?></td>
                    <td class="px-5 py-3">
                        <div class="flex gap-2">
                            <a href="<?= BASE_URL ?>/admin/timetable.php?class_id=<?= $c['id'] ?>"
                               class="p-1.5 text-teal-600 hover:bg-teal-50 rounded-lg transition-colors" title="Timetable">
                                <i class="fa-solid fa-calendar-days"></i>
                            </a>
                            <form method="POST" action="" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="form_action" value="delete">
                                <input type="hidden" name="class_id" value="<?= $c['id'] ?>">
                                <button type="submit" onclick="return confirm('Delete this class?')"
                                        class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($classes)): ?>
                <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">No classes found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
