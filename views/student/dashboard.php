<?php
// views/student/dashboard.php
$pageTitle  = 'My Dashboard';
$activePage = '/student/dashboard.php';
$role = 'student';
include ROOT_PATH . '/views/components/layout.php';

$student = $data['student'] ?? [];
$marks   = $data['marks'] ?? [];
$courses = $data['courses'] ?? [];
$gpa     = $data['gpa'] ?? 0.0;
$year    = $data['year'] ?? null;
?>

<!-- Welcome Banner -->
<div class="bg-gradient-to-r from-violet-600 to-indigo-600 rounded-2xl p-6 mb-6 text-white">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-violet-200 text-sm">Welcome back,</p>
            <h2 class="text-2xl font-bold mt-0.5"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h2>
            <p class="text-violet-200 text-sm mt-1">
                <span class="font-mono"><?= e($student['student_id']) ?></span>
                <?php if ($year): ?> · <?= e($year['name']) ?><?php endif; ?>
            </p>
        </div>
        <div class="hidden sm:block text-right">
            <div class="text-4xl font-bold"><?= number_format($gpa, 2) ?></div>
            <div class="text-violet-200 text-sm">Current GPA</div>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <?php
    $passCount = count(array_filter($marks, fn($m) => $m['is_pass'] == 1));
    $failCount = count(array_filter($marks, fn($m) => $m['is_pass'] == 0));
    $cards = [
        ['label'=>'Enrolled Courses','value'=>count($courses), 'icon'=>'fa-book',      'color'=>'bg-indigo-500'],
        ['label'=>'GPA',             'value'=>number_format($gpa,2), 'icon'=>'fa-star','color'=>'bg-violet-500'],
        ['label'=>'Passed',          'value'=>$passCount,     'icon'=>'fa-circle-check','color'=>'bg-green-500'],
        ['label'=>'Failed',          'value'=>$failCount,     'icon'=>'fa-circle-xmark','color'=>'bg-red-500'],
    ];
    foreach ($cards as $c):
    ?>
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 card-hover">
        <div class="w-9 h-9 <?= $c['color'] ?> rounded-lg flex items-center justify-center mb-3">
            <i class="fa-solid <?= $c['icon'] ?> text-white text-sm"></i>
        </div>
        <div class="text-2xl font-bold text-slate-800"><?= $c['value'] ?></div>
        <div class="text-xs text-slate-500 mt-0.5"><?= $c['label'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
    <!-- My Marks -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">My Results</h3>
            <a href="<?= BASE_URL ?>/student/marks.php" class="text-xs text-violet-600 hover:underline">View all</a>
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
                        <td class="px-4 py-3">
                            <div class="font-medium"><?= e($m['course_name']) ?></div>
                            <div class="text-xs text-slate-400"><?= e($m['code']) ?></div>
                        </td>
                        <td class="px-4 py-3 text-center font-bold <?= $m['is_pass'] ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $m['calculated_grade'] !== null ? number_format((float)$m['calculated_grade'],1) : '—' ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge <?= $m['is_pass'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= e($m['letter_grade'] ?? '—') ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge <?= $m['is_pass'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= $m['is_pass'] ? 'PASS' : 'FAIL' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty(array_filter($marks, fn($m) => $m['status']==='published'))): ?>
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">Results not yet published.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Enrolled Courses & Quick Links -->
    <div class="space-y-5">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="font-semibold text-slate-800">Enrolled Courses</h3>
            </div>
            <div class="divide-y divide-slate-50">
                <?php foreach ($courses as $c): ?>
                <div class="px-5 py-3 flex items-center justify-between hover:bg-slate-50">
                    <div>
                        <div class="font-medium text-sm"><?= e($c['course_name']) ?></div>
                        <div class="text-xs text-slate-400"><?= e($c['code']) ?> · <?= e($c['teacher_first'].' '.$c['teacher_last']) ?></div>
                    </div>
                    <span class="text-xs text-slate-400"><?= $c['credits'] ?> cr</span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($courses)): ?>
                <div class="px-5 py-8 text-center text-slate-400 text-sm">Not enrolled in any course.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <h3 class="font-semibold text-slate-800 mb-3">Quick Links</h3>
            <div class="grid grid-cols-2 gap-2">
                <?php
                $links = [
                    ['href'=>'/student/schedule.php',  'icon'=>'fa-calendar-alt',   'label'=>'Schedule',    'color'=>'text-indigo-600'],
                    ['href'=>'/student/transcript.php', 'icon'=>'fa-scroll',          'label'=>'Transcript',  'color'=>'text-violet-600'],
                    ['href'=>'/student/marks.php',      'icon'=>'fa-star-half-stroke','label'=>'All Marks',   'color'=>'text-blue-600'],
                    ['href'=>'/student/profile.php',    'icon'=>'fa-user-pen',        'label'=>'My Profile',  'color'=>'text-teal-600'],
                ];
                foreach ($links as $l):
                ?>
                <a href="<?= BASE_URL . $l['href'] ?>"
                   class="flex items-center gap-2 p-3 bg-slate-50 hover:bg-indigo-50 rounded-xl transition-colors">
                    <i class="fa-solid <?= $l['icon'] ?> <?= $l['color'] ?>"></i>
                    <span class="text-sm text-slate-700"><?= $l['label'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
