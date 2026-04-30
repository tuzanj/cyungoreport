<?php
// views/student/transcript.php
$pageTitle  = 'Transcript';
$activePage = '/student/transcript.php';
$role = 'student';
include ROOT_PATH . '/views/components/layout.php';

$student    = $transcriptData['student'] ?? [];
$transcript = $transcriptData['transcript'] ?? [];
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Academic Transcript</h2>
        <p class="text-sm text-slate-500 mt-0.5">Full academic record across all years</p>
    </div>
    <button onclick="window.print()" class="inline-flex items-center gap-2 bg-slate-700 hover:bg-slate-800 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
        <i class="fa-solid fa-print"></i> Print / Save PDF
    </button>
</div>

<div id="printArea" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-violet-700 to-indigo-700 p-6 text-white print:bg-indigo-700">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-xl font-bold"><?= APP_NAME ?></div>
                <div class="text-violet-200 text-sm">Official Academic Transcript</div>
            </div>
            <div class="text-right">
                <div class="font-mono font-bold text-lg"><?= e($student['student_id'] ?? '') ?></div>
                <div class="text-violet-200 text-xs">Student ID</div>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div>
                <div class="text-violet-300 text-xs">Full Name</div>
                <div class="font-semibold"><?= e(($student['first_name'] ?? '').' '.($student['last_name'] ?? '')) ?></div>
            </div>
            <div>
                <div class="text-violet-300 text-xs">Gender</div>
                <div class="font-semibold"><?= ucfirst($student['gender'] ?? '') ?></div>
            </div>
            <div>
                <div class="text-violet-300 text-xs">Date of Birth</div>
                <div class="font-semibold"><?= $student['date_of_birth'] ? formatDate($student['date_of_birth']) : '—' ?></div>
            </div>
            <div>
                <div class="text-violet-300 text-xs">Enrollment Date</div>
                <div class="font-semibold"><?= $student['enrollment_date'] ? formatDate($student['enrollment_date']) : '—' ?></div>
            </div>
        </div>
    </div>

    <!-- Per-year records -->
    <?php if (empty($transcript)): ?>
    <div class="px-6 py-10 text-center text-slate-400">No published results available.</div>
    <?php endif; ?>

    <?php foreach ($transcript as $yr): ?>
    <div class="px-6 py-5 border-b border-slate-100 last:border-b-0">
        <div class="flex items-center justify-between mb-3">
            <h4 class="font-semibold text-slate-800"><?= e($yr['year']['name']) ?></h4>
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-500">GPA:</span>
                <span class="text-lg font-bold <?= $yr['gpa'] >= 2.0 ? 'text-green-600' : 'text-red-600' ?>"><?= number_format($yr['gpa'],2) ?></span>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-3 py-2 text-left">Course</th>
                    <th class="px-3 py-2 text-left">Code</th>
                    <th class="px-3 py-2 text-center">Credits</th>
                    <th class="px-3 py-2 text-center">Grade</th>
                    <th class="px-3 py-2 text-center">Letter</th>
                    <th class="px-3 py-2 text-center">GPA Pts</th>
                    <th class="px-3 py-2 text-center">Result</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($yr['marks'] as $m): ?>
                <tr>
                    <td class="px-3 py-2 font-medium"><?= e($m['course_name']) ?></td>
                    <td class="px-3 py-2 font-mono text-slate-500 text-xs"><?= e($m['code']) ?></td>
                    <td class="px-3 py-2 text-center"><?= $m['credits'] ?></td>
                    <td class="px-3 py-2 text-center font-bold <?= $m['is_pass'] ? 'text-green-600' : 'text-red-600' ?>">
                        <?= $m['calculated_grade'] !== null ? number_format((float)$m['calculated_grade'],1) : '—' ?>
                    </td>
                    <td class="px-3 py-2 text-center">
                        <span class="badge <?= $m['is_pass'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            <?= e($m['letter_grade'] ?? '—') ?>
                        </span>
                    </td>
                    <td class="px-3 py-2 text-center text-slate-600">
                        <?= number_format(getGpaPoint($m['letter_grade'] ?? 'F'), 1) ?>
                    </td>
                    <td class="px-3 py-2 text-center">
                        <span class="badge <?= $m['is_pass'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            <?= $m['is_pass'] ? 'PASS' : 'FAIL' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>

    <div class="px-6 py-4 bg-slate-50 text-xs text-slate-400 text-center">
        Generated on <?= date('d M Y, H:i') ?> · <?= APP_NAME ?> v<?= APP_VERSION ?>
    </div>
</div>

<style>
@media print {
    aside, header, .no-print { display: none !important; }
    main { margin-left: 0 !important; padding-top: 0 !important; }
    #printArea { box-shadow: none; border: none; }
}
</style>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
