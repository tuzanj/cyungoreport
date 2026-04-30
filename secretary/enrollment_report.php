<?php
// secretary/enrollment_report.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/config/report.php';

startSecureSession();
requireRole(ROLE_SECRETARY);

require_once ROOT_PATH . '/controllers/SecretaryController.php';

$secCtrl = new SecretaryController();
$db      = Database::getInstance();
$years   = $db->fetchAll("SELECT * FROM academic_years ORDER BY start_date DESC");

$currentYear   = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$selectedYearId = (int)($_GET['year_id'] ?? ($currentYear['id'] ?? 0));

$report = $selectedYearId ? $secCtrl->getEnrollmentReport($selectedYearId) : [];

if (isset($_GET['export']) && $selectedYearId) {
    $headers = ['Class', 'Grade Level', 'Section', 'Total Students', 'Male Students', 'Female Students', 'Percentage'];
    $rows = [];
    $totalStudents = array_sum(array_column($report, 'total_students'));
    foreach ($report as $row) {
        $percentage = $totalStudents > 0 ? round(($row['total_students'] / $totalStudents) * 100, 2) : 0;
        $rows[] = [
            $row['class_name'],
            $row['grade_level'],
            $row['section'],
            $row['total_students'],
            $row['male_count'],
            $row['female_count'],
            $percentage . '%',
        ];
    }

    if ($_GET['export'] === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="enrollment_report_' . date('Ymd') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    if ($_GET['export'] === 'excel') {
        sendExcel('enrollment_report_' . date('Ymd') . '.xls', 'Enrollment Report', $headers, $rows);
    }

    if ($_GET['export'] === 'pdf') {
        $content = buildPdf('Enrollment Report', $headers, $rows);
        sendPdf('enrollment_report_' . date('Ymd') . '.pdf', $content);
    }
}

$selectedYear = $db->fetchOne("SELECT * FROM academic_years WHERE id=?", [$selectedYearId]);

$pageTitle  = 'Enrollment Report';
$activePage = '/secretary/enrollment_report.php';
$role = 'secretary';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Enrollment Report</h2>
        <p class="text-sm text-slate-500 mt-0.5"><?= $selectedYear ? e($selectedYear['name']) : 'Select a year' ?></p>
    </div>
    <div class="flex gap-2">
        <form method="GET" action="" class="flex gap-2">
            <select name="year_id" onchange="this.form.submit()"
                    class="px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                <?php foreach ($years as $y): ?>
                <option value="<?= $y['id'] ?>" <?= $y['id'] == $selectedYearId ? 'selected' : '' ?>>
                    <?= e($y['name']) ?><?= $y['is_current'] ? ' (Current)' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 bg-slate-700 hover:bg-slate-800 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
            <i class="fa-solid fa-print"></i>Print
        </button>
        <a href="?year_id=<?= $selectedYearId ?>&export=csv"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
            <i class="fa-solid fa-file-csv"></i>Export CSV
        </a>
        <a href="?year_id=<?= $selectedYearId ?>&export=excel"
           class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
            <i class="fa-solid fa-file-excel"></i>Export Excel
        </a>
        <a href="?year_id=<?= $selectedYearId ?>&export=pdf"
           class="inline-flex items-center gap-2 bg-rose-600 hover:bg-rose-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
            <i class="fa-solid fa-file-pdf"></i>Export PDF
        </a>
    </div>
</div>

<!-- Summary Cards -->
<?php
$totalStudents = array_sum(array_column($report, 'total_students'));
$totalMale     = array_sum(array_column($report, 'male_count'));
$totalFemale   = array_sum(array_column($report, 'female_count'));
?>
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 text-center">
        <div class="text-3xl font-bold text-teal-600"><?= $totalStudents ?></div>
        <div class="text-sm text-slate-500 mt-1">Total Enrolled</div>
    </div>
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 text-center">
        <div class="text-3xl font-bold text-blue-600"><?= $totalMale ?></div>
        <div class="text-sm text-slate-500 mt-1">Male Students</div>
    </div>
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 text-center">
        <div class="text-3xl font-bold text-pink-600"><?= $totalFemale ?></div>
        <div class="text-sm text-slate-500 mt-1">Female Students</div>
    </div>
</div>

<div id="printArea" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Class</th>
                    <th class="px-5 py-3 text-left">Grade Level</th>
                    <th class="px-5 py-3 text-left">Section</th>
                    <th class="px-5 py-3 text-center">Total</th>
                    <th class="px-5 py-3 text-center">Male</th>
                    <th class="px-5 py-3 text-center">Female</th>
                    <th class="px-5 py-3 text-center">%</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($report as $r): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-semibold"><?= e($r['class_name']) ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= e($r['grade_level'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= e($r['section'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-center font-bold text-teal-700"><?= $r['total_students'] ?></td>
                    <td class="px-5 py-3 text-center text-blue-600"><?= $r['male_count'] ?></td>
                    <td class="px-5 py-3 text-center text-pink-600"><?= $r['female_count'] ?></td>
                    <td class="px-5 py-3 text-center">
                        <?php $pct = $totalStudents > 0 ? round(($r['total_students']/$totalStudents)*100) : 0; ?>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-slate-200 rounded-full h-1.5">
                                <div class="bg-teal-500 h-1.5 rounded-full" style="width:<?= $pct ?>%"></div>
                            </div>
                            <span class="text-xs text-slate-500"><?= $pct ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($report)): ?>
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">No data available.</td></tr>
                <?php endif; ?>
                <?php if (!empty($report)): ?>
                <tr class="bg-slate-50 font-semibold">
                    <td class="px-5 py-3" colspan="3">Total</td>
                    <td class="px-5 py-3 text-center text-teal-700"><?= $totalStudents ?></td>
                    <td class="px-5 py-3 text-center text-blue-600"><?= $totalMale ?></td>
                    <td class="px-5 py-3 text-center text-pink-600"><?= $totalFemale ?></td>
                    <td class="px-5 py-3 text-center">100%</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>@media print { aside,header,.no-print{display:none!important} main{margin-left:0!important;padding-top:0!important} }</style>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
