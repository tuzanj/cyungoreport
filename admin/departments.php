<?php
// admin/departments.php

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
        setFlash('danger', 'Invalid CSRF token.'); redirect('/admin/departments.php');
    }
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'create') {
        $result = $adminCtrl->createDepartment(trim($_POST['name'] ?? ''), trim($_POST['description'] ?? ''));
        setFlash(!empty($result['success']) ? 'success' : 'danger', $result['message'] ?? $result['error'] ?? 'Unable to create trade.');
        redirect('/admin/departments.php');
    }

    if ($formAction === 'delete') {
        $id = (int)($_POST['dept_id'] ?? 0);
        $db->execute("DELETE FROM trades WHERE id=?", [$id]);
        (new AuditModel())->log('trade_deleted', 'trades', $id);
        setFlash('success', 'Trade deleted.');
        redirect('/admin/departments.php');
    }
}

$departments = $db->fetchAll(
    "SELECT d.*,
        (SELECT COUNT(*) FROM teachers t WHERE t.trade_id=d.id) as teacher_count,
        (SELECT COUNT(*) FROM courses c WHERE c.trade_id=d.id) as course_count
     FROM trades d ORDER BY d.name"
);

$pageTitle  = 'Trades (RTB)';
$activePage = '/admin/departments.php';
$role = 'admin';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Trades (RTB)</h2>
        <p class="text-sm text-slate-500 mt-0.5">Organize staff and courses by trade - Rwanda TVET Board classification</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <!-- Create Form -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Add Trade</h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="form_action" value="create">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Trade Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Automotive Technology"
                           class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Description</label>
                    <textarea name="description" rows="3" placeholder="Optional description…"
                              class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg text-sm font-semibold transition-colors">
                    <i class="fa-solid fa-plus mr-1"></i>Create Trade
                </button>
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">All Trades <span class="text-slate-400 font-normal text-sm">(<?= count($departments) ?>)</span></h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3 text-left">Trade Name</th>
                        <th class="px-5 py-3 text-left">Description</th>
                        <th class="px-5 py-3 text-center">Teachers</th>
                        <th class="px-5 py-3 text-center">Courses</th>
                        <th class="px-5 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($departments as $d): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-semibold"><?= e($d['name']) ?></td>
                        <td class="px-5 py-3 text-slate-500 text-xs max-w-xs truncate"><?= e($d['description'] ?? '—') ?></td>
                        <td class="px-5 py-3 text-center font-medium text-blue-600"><?= $d['teacher_count'] ?></td>
                        <td class="px-5 py-3 text-center font-medium text-indigo-600"><?= $d['course_count'] ?></td>
                        <td class="px-5 py-3">
                            <form method="POST" action="" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="form_action" value="delete">
                                <input type="hidden" name="dept_id" value="<?= $d['id'] ?>">
                                <button type="submit" onclick="return confirm('Delete this trade?')"
                                        class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($departments)): ?>
                    <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No trades. Create one.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
