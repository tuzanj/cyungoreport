<?php
// parent/children.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_PARENT);

require_once ROOT_PATH . '/models/ParentModel.php';
require_once ROOT_PATH . '/models/StudentModel.php';

$parentModel  = new ParentModel();
$studentModel = new StudentModel();
$db           = Database::getInstance();

$parent = $parentModel->findByUserId(currentUserId());
if (!$parent) { setFlash('danger', 'Parent profile not found.'); redirect('/index.php'); }

$children = $parentModel->getChildren((int)$parent['id']);

$currentYear   = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$currentYearId = $currentYear ? (int)$currentYear['id'] : 0;

$pageTitle  = 'My Children';
$activePage = '/parent/children.php';
$role = 'parent';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-slate-800">My Children</h2>
    <p class="text-sm text-slate-500 mt-0.5"><?= count($children) ?> linked student<?= count($children) !== 1 ? 's' : '' ?></p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($children as $child): ?>
    <?php
    $gpa = $studentModel->getGpa((int)$child['id'], $currentYearId);
    $attRow = $db->fetchOne(
        "SELECT COUNT(*) as total, SUM(status='present') as present, SUM(status='absent') as absent
         FROM attendance WHERE student_id=?", [$child['id']]
    );
    $attPct = ($attRow['total'] ?? 0) > 0 ? round(($attRow['present'] / $attRow['total']) * 100) : null;
    ?>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden card-hover">
        <div class="bg-gradient-to-r from-green-600 to-teal-600 p-5 text-white">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center text-xl font-bold">
                    <?= strtoupper(substr($child['first_name'],0,1).substr($child['last_name'],0,1)) ?>
                </div>
                <div>
                    <div class="font-bold text-lg"><?= e($child['first_name'].' '.$child['last_name']) ?></div>
                    <div class="text-green-200 text-xs font-mono"><?= e($child['student_id']) ?></div>
                    <?php if ($child['class_name']): ?>
                    <div class="text-green-100 text-xs mt-0.5"><?= e($child['class_name']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="p-4 grid grid-cols-2 gap-3">
            <div class="bg-slate-50 rounded-xl p-3 text-center">
                <div class="text-xl font-bold <?= $gpa >= 2.0 ? 'text-green-600' : 'text-red-500' ?>"><?= number_format($gpa,2) ?></div>
                <div class="text-xs text-slate-500">GPA</div>
            </div>
            <div class="bg-slate-50 rounded-xl p-3 text-center">
                <div class="text-xl font-bold <?= ($attPct ?? 100) >= 75 ? 'text-blue-600' : 'text-orange-500' ?>">
                    <?= $attPct !== null ? $attPct.'%' : '—' ?>
                </div>
                <div class="text-xs text-slate-500">Attendance</div>
            </div>
        </div>
        <div class="px-4 pb-4 flex gap-2">
            <a href="<?= BASE_URL ?>/parent/performance.php?student=<?= $child['id'] ?>"
               class="flex-1 bg-green-50 hover:bg-green-100 text-green-700 py-2 rounded-lg text-xs font-semibold text-center transition-colors">
                <i class="fa-solid fa-chart-line mr-1"></i>Performance
            </a>
            <a href="<?= BASE_URL ?>/parent/messages.php?student=<?= $child['id'] ?>"
               class="flex-1 bg-teal-50 hover:bg-teal-100 text-teal-700 py-2 rounded-lg text-xs font-semibold text-center transition-colors">
                <i class="fa-solid fa-envelope mr-1"></i>Message
            </a>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($children)): ?>
    <div class="col-span-3 bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm border border-slate-100">
        <i class="fa-solid fa-child-reaching text-4xl text-slate-200 mb-3"></i>
        <p>No children linked to your account. Contact the school secretary.</p>
    </div>
    <?php endif; ?>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
