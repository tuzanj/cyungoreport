<?php
// secretary/link_parent.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_SECRETARY);

require_once ROOT_PATH . '/controllers/SecretaryController.php';
require_once ROOT_PATH . '/models/StudentModel.php';
require_once ROOT_PATH . '/models/ParentModel.php';

$secCtrl      = new SecretaryController();
$studentModel = new StudentModel();
$parentModel  = new ParentModel();
$db           = Database::getInstance();

$preselectedStudentId = (int)($_GET['student_id'] ?? 0);
$students = $studentModel->getAllWithDetails();
$result   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.'); redirect('/secretary/link_parent.php');
    }
    $studentId = (int)($_POST['student_id'] ?? 0);
    $out = $secCtrl->linkParent($studentId, $_POST);
    setFlash($out['success'] ? 'success' : 'danger', $out['success'] ? $out['message'] : $out['error']);
    redirect('/secretary/link_parent.php?student_id='.$studentId);
}

// Load existing parents for selected student
$existingParents = $preselectedStudentId ? $studentModel->getParents($preselectedStudentId) : [];
$selectedStudent = $preselectedStudentId ? $studentModel->getWithUser($preselectedStudentId) : null;

$pageTitle  = 'Link Parent';
$activePage = '/secretary/link_parent.php';
$role = 'secretary';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-slate-800">Link Parent / Guardian</h2>
    <p class="text-sm text-slate-500 mt-0.5">Associate a parent account with a student</p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <h3 class="font-semibold text-slate-800 mb-4">Add Parent Link</h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Select Student *</label>
                    <select name="student_id" required
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">Choose a student…</option>
                        <?php foreach ($students as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $s['id'] == $preselectedStudentId ? 'selected' : '' ?>>
                            <?= e($s['student_id'].' — '.$s['first_name'].' '.$s['last_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <hr class="border-slate-100">
                <p class="text-xs text-slate-400">Fill in parent details below to create a new parent account.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name *</label>
                        <input type="text" name="first_name" required
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name *</label>
                        <input type="text" name="last_name" required
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Relationship</label>
                    <select name="relationship"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="guardian">Guardian</option>
                        <option value="father">Father</option>
                        <option value="mother">Mother</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email *</label>
                    <input type="email" name="email" required
                           class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone</label>
                    <input type="tel" name="phone"
                           class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
            </div>
            <button type="submit" class="mt-5 w-full bg-teal-600 hover:bg-teal-700 text-white py-2.5 rounded-lg text-sm font-semibold transition-colors flex items-center justify-center gap-2">
                <i class="fa-solid fa-link"></i>Link Parent
            </button>
        </form>
    </div>

    <?php if ($selectedStudent): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <h3 class="font-semibold text-slate-800 mb-1">
            Parents of <?= e($selectedStudent['first_name'].' '.$selectedStudent['last_name']) ?>
        </h3>
        <p class="text-xs text-slate-400 mb-4 font-mono"><?= e($selectedStudent['student_id']) ?></p>
        <?php if (empty($existingParents)): ?>
        <p class="text-sm text-slate-400 text-center py-8">No parents linked yet.</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($existingParents as $p): ?>
            <div class="flex items-center gap-3 bg-slate-50 rounded-xl p-3">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-700 font-bold">
                    <?= strtoupper(substr($p['first_name'],0,1).substr($p['last_name'],0,1)) ?>
                </div>
                <div>
                    <div class="font-medium text-sm"><?= e($p['first_name'].' '.$p['last_name']) ?></div>
                    <div class="text-xs text-slate-400"><?= ucfirst($p['relationship']) ?> · <?= e($p['parent_email'] ?? $p['email'] ?? '—') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
