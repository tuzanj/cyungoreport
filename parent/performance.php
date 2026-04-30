<?php
// parent/performance.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_PARENT);

require_once ROOT_PATH . '/models/ParentModel.php';
require_once ROOT_PATH . '/models/StudentModel.php';
require_once ROOT_PATH . '/models/MarkModel.php';
require_once ROOT_PATH . '/models/AttendanceModel.php';

$parentModel  = new ParentModel();
$studentModel = new StudentModel();
$markModel    = new MarkModel();
$attendModel  = new AttendanceModel();
$db           = Database::getInstance();

$parent = $parentModel->findByUserId(currentUserId());
if (!$parent) redirect('/index.php');

$currentYear   = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$currentYearId = $currentYear ? (int)$currentYear['id'] : 0;

// Validate child belongs to this parent
$selectedStudentId = (int)($_GET['student'] ?? 0);
$children = $parentModel->getChildren((int)$parent['id']);
$childIds = array_column($children, 'id');

if ($selectedStudentId && !in_array($selectedStudentId, $childIds)) {
    setFlash('danger', 'Access denied.'); redirect('/parent/children.php');
}

if (!$selectedStudentId && !empty($childIds)) {
    $selectedStudentId = $childIds[0]; // default to first child
}

$selectedChild = null;
foreach ($children as $c) {
    if ($c['id'] == $selectedStudentId) { $selectedChild = $c; break; }
}

$marks       = $selectedStudentId ? $markModel->getStudentMarks($selectedStudentId, $currentYearId) : [];
$gpa         = $selectedStudentId ? $studentModel->getGpa($selectedStudentId, $currentYearId) : 0;
$years       = $db->fetchAll("SELECT * FROM academic_years ORDER BY start_date DESC");

// Attendance per course
$attendanceSummary = [];
if ($selectedStudentId) {
    $enrolledCourses = $studentModel->getEnrolledCourses($selectedStudentId, $currentYearId);
    foreach ($enrolledCourses as $ec) {
        $stats = $attendModel->getAttendanceStats($selectedStudentId, (int)$ec['class_course_id']);
        $attendanceSummary[] = array_merge($ec, $stats);
    }
}

$pageTitle  = 'Performance';
$activePage = '/parent/performance.php';
$role = 'parent';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Student Performance</h2>
        <?php if ($selectedChild): ?>
        <p class="text-sm text-slate-500 mt-0.5"><?= e($selectedChild['first_name'].' '.$selectedChild['last_name']) ?> · <?= e($selectedChild['student_id']) ?></p>
        <?php endif; ?>
    </div>
    <!-- Child switcher -->
    <?php if (count($children) > 1): ?>
    <div class="flex gap-2">
        <?php foreach ($children as $ch): ?>
        <a href="?student=<?= $ch['id'] ?>"
           class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $ch['id'] == $selectedStudentId ? 'bg-green-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
            <?= e($ch['first_name']) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- GPA Banner -->
<div class="bg-gradient-to-r from-green-600 to-teal-600 rounded-2xl p-5 mb-6 text-white flex items-center justify-between">
    <div>
        <p class="text-green-200 text-sm">Current Academic Year GPA</p>
        <p class="text-4xl font-bold mt-1"><?= number_format($gpa, 2) ?></p>
        <p class="text-green-200 text-xs mt-1"><?= $currentYear ? e($currentYear['name']) : '' ?></p>
    </div>
    <div class="hidden sm:block">
        <?php
        $passCount = count(array_filter($marks, fn($m) => $m['is_pass'] == 1 && $m['status'] === 'published'));
        $failCount = count(array_filter($marks, fn($m) => $m['is_pass'] == 0 && $m['status'] === 'published'));
        ?>
        <div class="text-center">
            <div class="text-2xl font-bold text-green-200"><?= $passCount ?> / <?= $passCount + $failCount ?></div>
            <div class="text-green-300 text-xs">Subjects Passed</div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-5">
    <!-- Marks Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Academic Results</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left">Course</th>
                        <th class="px-4 py-3 text-center">Grade</th>
                        <th class="px-4 py-3 text-center">Letter</th>
                        <th class="px-4 py-3 text-center">Result</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($marks as $m): ?>
                    <?php if ($m['status'] !== 'published') continue; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2.5">
                            <div class="font-medium text-sm"><?= e($m['course_name']) ?></div>
                            <div class="text-xs text-slate-400"><?= e($m['code']) ?></div>
                        </td>
                        <td class="px-4 py-2.5 text-center font-bold <?= $m['is_pass'] ? 'text-green-600' : 'text-red-500' ?>">
                            <?= $m['calculated_grade'] !== null ? number_format((float)$m['calculated_grade'],1) : '—' ?>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="badge <?= $m['is_pass'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= e($m['letter_grade'] ?? '—') ?>
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="badge <?= $m['is_pass'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= $m['is_pass'] ? 'PASS' : 'FAIL' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty(array_filter($marks, fn($m) => $m['status'] === 'published'))): ?>
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">No published results.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Attendance per Course -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Attendance Summary</h3>
        </div>
        <div class="divide-y divide-slate-50">
            <?php foreach ($attendanceSummary as $as): ?>
            <?php $pct = ($as['total'] ?? 0) > 0 ? round(($as['present'] / $as['total']) * 100) : 0; ?>
            <div class="px-5 py-4">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <div class="font-medium text-sm"><?= e($as['course_name']) ?></div>
                        <div class="text-xs text-slate-400"><?= e($as['code']) ?></div>
                    </div>
                    <span class="font-bold text-sm <?= $pct >= 75 ? 'text-green-600' : 'text-orange-500' ?>"><?= $pct ?>%</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full <?= $pct >= 75 ? 'bg-green-500' : 'bg-orange-400' ?>"
                         style="width:<?= $pct ?>%"></div>
                </div>
                <div class="flex gap-4 mt-1.5 text-xs text-slate-400">
                    <span class="text-green-600"><?= $as['present'] ?> present</span>
                    <span class="text-red-500"><?= $as['absent'] ?> absent</span>
                    <span><?= $as['total'] ?> total</span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($attendanceSummary)): ?>
            <div class="px-5 py-8 text-center text-slate-400 text-sm">No attendance data.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
