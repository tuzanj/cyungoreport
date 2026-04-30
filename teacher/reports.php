<?php
// teacher/reports.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/config/report.php';

startSecureSession();
requireRole(ROLE_TEACHER);

require_once ROOT_PATH . '/models/TeacherModel.php';
require_once ROOT_PATH . '/models/MarkModel.php';
require_once ROOT_PATH . '/models/AttendanceModel.php';

$teacherModel  = new TeacherModel();
$markModel     = new MarkModel();
$attendModel   = new AttendanceModel();
$db            = Database::getInstance();

$teacher     = $teacherModel->findByUserId(currentUserId());
if (!$teacher) redirect('/index.php');

$currentYear = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$yearId      = $currentYear ? (int)$currentYear['id'] : 0;
$courses     = $teacherModel->getAssignedCourses((int)$teacher['id'], $yearId);

$selectedCcId = (int)($_GET['cc'] ?? 0);
$classReport  = [];
$courseName   = '';

if ($selectedCcId) {
    $classReport = $markModel->getClassReport($selectedCcId);
    $cc = $db->fetchOne("SELECT c.name as course_name FROM class_courses cc JOIN courses c ON c.id=cc.course_id WHERE cc.id=?", [$selectedCcId]);
    $courseName = $cc['course_name'] ?? '';
}

if ($selectedCcId && isset($_GET['export']) && in_array($_GET['export'], ['excel', 'pdf', 'csv'], true)) {
    $headers = ['Student', 'Student ID', 'Assignments', 'Quiz', 'Midterm', 'Final', 'Total', 'Letter', 'Result'];
    $rows = [];
    foreach ($classReport as $r) {
        $rows[] = [
            $r['first_name'] . ' ' . $r['last_name'],
            $r['student_id'],
            $r['assignments_score'] !== null ? number_format((float)$r['assignments_score'], 1) : '—',
            $r['quizzes_score'] !== null ? number_format((float)$r['quizzes_score'], 1) : '—',
            $r['midterm_score'] !== null ? number_format((float)$r['midterm_score'], 1) : '—',
            $r['final_score'] !== null ? number_format((float)$r['final_score'], 1) : '—',
            $r['calculated_grade'] !== null ? number_format((float)$r['calculated_grade'], 1) : '—',
            $r['letter_grade'] ?? '—',
            $r['status'] === 'published' ? ($r['is_pass'] ? 'PASS' : 'FAIL') : 'Draft',
        ];
    }

    if ($_GET['export'] === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="class_report_' . date('Ymd') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    if ($_GET['export'] === 'excel') {
        sendExcel('class_report_' . date('Ymd') . '.xls', 'Class Report', $headers, $rows);
    }

    if ($_GET['export'] === 'pdf') {
        $content = buildPdf('Class Report', $headers, $rows);
        sendPdf('class_report_' . date('Ymd') . '.pdf', $content);
    }
}

$pageTitle  = 'Reports';
$activePage = '/teacher/reports.php';
$role = 'teacher';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Class Reports</h2>
        <p class="text-sm text-slate-500 mt-0.5">Grade and performance summaries</p>
    </div>
    <form method="GET" action="" class="flex gap-2">
        <select name="cc" onchange="this.form.submit()"
                class="px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-[200px]">
            <option value="">Select course…</option>
            <?php foreach ($courses as $c): ?>
            <option value="<?= $c['class_course_id'] ?>" <?= $selectedCcId == $c['class_course_id'] ? 'selected' : '' ?>>
                <?= e($c['course_name'].' ('.$c['class_name'].')') ?>
            </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($selectedCcId && !empty($classReport)): ?>
<?php
$passCount = count(array_filter($classReport, fn($r) => $r['is_pass'] == 1));
$failCount = count(array_filter($classReport, fn($r) => $r['is_pass'] == 0));
$avg       = count($classReport) > 0 ? array_sum(array_column($classReport, 'calculated_grade')) / count($classReport) : 0;
?>
<div class="grid grid-cols-3 gap-4 mb-5">
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 text-center">
        <div class="text-2xl font-bold text-blue-600"><?= count($classReport) ?></div>
        <div class="text-xs text-slate-500 mt-0.5">Total Students</div>
    </div>
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 text-center">
        <div class="text-2xl font-bold text-green-600"><?= $passCount ?></div>
        <div class="text-xs text-slate-500 mt-0.5">Passed</div>
    </div>
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 text-center">
        <div class="text-2xl font-bold text-slate-700"><?= number_format($avg, 1) ?></div>
        <div class="text-xs text-slate-500 mt-0.5">Class Average</div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h3 class="font-semibold text-slate-800"><?= e($courseName) ?> — Results</h3>
        <div class="flex flex-wrap gap-2">
            <button onclick="window.print()" class="inline-flex items-center gap-2 text-xs text-slate-500 hover:text-slate-700">
                <i class="fa-solid fa-print"></i>Print
            </button>
            <a href="?cc=<?= $selectedCcId ?>&export=excel" class="inline-flex items-center gap-2 text-xs text-emerald-700 hover:text-emerald-900">
                <i class="fa-solid fa-file-excel"></i>Excel
            </a>
            <a href="?cc=<?= $selectedCcId ?>&export=pdf" class="inline-flex items-center gap-2 text-xs text-rose-700 hover:text-rose-900">
                <i class="fa-solid fa-file-pdf"></i>PDF
            </a>
            <a href="?cc=<?= $selectedCcId ?>&export=csv" class="inline-flex items-center gap-2 text-xs text-indigo-700 hover:text-indigo-900">
                <i class="fa-solid fa-file-csv"></i>CSV
            </a>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Student</th>
                    <th class="px-5 py-3 text-center">Assign.</th>
                    <th class="px-5 py-3 text-center">Quiz</th>
                    <th class="px-5 py-3 text-center">Midterm</th>
                    <th class="px-5 py-3 text-center">Final</th>
                    <th class="px-5 py-3 text-center">Total</th>
                    <th class="px-5 py-3 text-center">Letter</th>
                    <th class="px-5 py-3 text-center">Result</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($classReport as $r): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3">
                        <div class="font-medium"><?= e($r['first_name'].' '.$r['last_name']) ?></div>
                        <div class="text-xs text-slate-400 font-mono"><?= e($r['student_id']) ?></div>
                    </td>
                    <?php foreach (['assignments_score','quizzes_score','midterm_score','final_score'] as $f): ?>
                    <td class="px-5 py-3 text-center text-slate-600">
                        <?= $r[$f] !== null ? number_format((float)$r[$f],1) : '—' ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="px-5 py-3 text-center font-bold <?= $r['is_pass'] ? 'text-green-600' : 'text-red-600' ?>">
                        <?= $r['calculated_grade'] !== null ? number_format((float)$r['calculated_grade'],1) : '—' ?>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="badge <?= $r['is_pass'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            <?= e($r['letter_grade'] ?? '—') ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <?php if ($r['status'] === 'published'): ?>
                        <span class="badge <?= $r['is_pass'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            <?= $r['is_pass'] ? 'PASS' : 'FAIL' ?>
                        </span>
                        <?php else: ?>
                        <span class="badge bg-yellow-100 text-yellow-700">Draft</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<style>@media print { aside,header,.no-print{display:none!important} main{margin-left:0!important;padding-top:0!important} }</style>
<?php elseif ($selectedCcId): ?>
<div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm border border-slate-100">
    <p>No marks recorded for this course yet.</p>
</div>
<?php else: ?>
<div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm border border-slate-100">
    <i class="fa-solid fa-chart-bar text-4xl text-slate-200 mb-3"></i>
    <p>Select a course to view its report.</p>
</div>
<?php endif; ?>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
