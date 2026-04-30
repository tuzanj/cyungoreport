<?php
// admin/academic_years.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_ADMIN);

require_once ROOT_PATH . '/controllers/AdminController.php';
require_once ROOT_PATH . '/models/AuditModel.php';

$adminCtrl = new AdminController();
$db        = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.'); redirect('/admin/academic_years.php');
    }
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'create') {
        $result = $adminCtrl->createAcademicYear([
            'name'       => trim($_POST['name'] ?? ''),
            'start_date' => $_POST['start_date'] ?? '',
            'end_date'   => $_POST['end_date'] ?? '',
            'is_current' => isset($_POST['is_current']) ? 1 : 0,
        ]);
        setFlash($result['success'] ? 'success' : 'danger', $result['message'] ?? $result['error'] ?? '');
        redirect('/admin/academic_years.php');
    }

    if ($formAction === 'set_current') {
        $id = (int)($_POST['year_id'] ?? 0);
        $db->execute("UPDATE academic_years SET is_current=0");
        $db->execute("UPDATE academic_years SET is_current=1 WHERE id=?", [$id]);
        (new AuditModel())->log('academic_year_set_current', 'academic_years', $id);
        setFlash('success', 'Active academic year updated.');
        redirect('/admin/academic_years.php');
    }

    if ($formAction === 'delete') {
        $id = (int)($_POST['year_id'] ?? 0);
        $db->execute("DELETE FROM academic_years WHERE id=? AND is_current=0", [$id]);
        setFlash('success', 'Academic year deleted (only non-current years can be deleted).');
        redirect('/admin/academic_years.php');
    }
}

$years = $db->fetchAll(
    "SELECT ay.*,
        (SELECT COUNT(*) FROM classes c WHERE c.academic_year_id=ay.id) as class_count,
        (SELECT COUNT(*) FROM enrollments e WHERE e.academic_year_id=ay.id) as enrollment_count
     FROM academic_years ay
     ORDER BY ay.start_date DESC"
);

$pageTitle  = 'Academic Years';
$activePage = '/admin/academic_years.php';
$role = 'admin';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Academic Year Management</h2>
        <p class="text-sm text-slate-500 mt-0.5">Manage academic periods and activate the current year</p>
    </div>
    <a href="?action=new" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
        <i class="fa-solid fa-plus"></i> New Year
    </a>
</div>

<?php if (($_GET['action'] ?? '') === 'new'): ?>
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
    <h3 class="font-semibold text-slate-800 mb-4">Create Academic Year</h3>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="form_action" value="create">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Name *</label>
                <input type="text" name="name" required placeholder="e.g. 2025-2026"
                       class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Start Date *</label>
                <input type="date" name="start_date" required
                       class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">End Date *</label>
                <input type="date" name="end_date" required
                       class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_current" value="1"
                           class="w-4 h-4 text-indigo-600 rounded border-slate-300">
                    <span class="text-sm font-medium text-slate-700">Set as current</span>
                </label>
            </div>
        </div>
        <div class="flex gap-3 mt-4">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">
                <i class="fa-solid fa-floppy-disk mr-1"></i>Create
            </button>
            <a href="" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Name</th>
                    <th class="px-5 py-3 text-left">Start Date</th>
                    <th class="px-5 py-3 text-left">End Date</th>
                    <th class="px-5 py-3 text-center">Classes</th>
                    <th class="px-5 py-3 text-center">Enrollments</th>
                    <th class="px-5 py-3 text-center">Status</th>
                    <th class="px-5 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($years as $y): ?>
                <tr class="hover:bg-slate-50 <?= $y['is_current'] ? 'bg-indigo-50/30' : '' ?>">
                    <td class="px-5 py-3 font-semibold"><?= e($y['name']) ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= formatDate($y['start_date']) ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= formatDate($y['end_date']) ?></td>
                    <td class="px-5 py-3 text-center"><?= $y['class_count'] ?></td>
                    <td class="px-5 py-3 text-center"><?= $y['enrollment_count'] ?></td>
                    <td class="px-5 py-3 text-center">
                        <?php if ($y['is_current']): ?>
                        <span class="badge bg-green-100 text-green-700">
                            <i class="fa-solid fa-circle-check mr-1"></i>Current
                        </span>
                        <?php else: ?>
                        <span class="badge bg-slate-100 text-slate-500">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex gap-2">
                            <?php if (!$y['is_current']): ?>
                            <form method="POST" action="" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="form_action" value="set_current">
                                <input type="hidden" name="year_id" value="<?= $y['id'] ?>">
                                <button type="submit"
                                        class="text-xs bg-indigo-50 text-indigo-700 hover:bg-indigo-100 px-2.5 py-1.5 rounded-lg transition-colors font-medium">
                                    Set Current
                                </button>
                            </form>
                            <form method="POST" action="" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="form_action" value="delete">
                                <input type="hidden" name="year_id" value="<?= $y['id'] ?>">
                                <button type="submit" onclick="return confirm('Delete this academic year?')"
                                        class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="text-xs text-slate-300 italic">Active year</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($years)): ?>
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">No academic years.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
