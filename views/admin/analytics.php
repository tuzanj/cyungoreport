<?php
// views/admin/analytics.php
$pageTitle  = 'Analytics';
$activePage = '/admin/analytics.php';
$role = 'admin';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-slate-800">Analytics & Reports</h2>
    <p class="text-sm text-slate-500 mt-0.5">Overview of academic performance and enrollment data</p>
</div>

<!-- Top Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php
    $cards = [
        ['label'=>'Total Students','value'=>$analytics['total_students'],'icon'=>'fa-user-graduate','color'=>'bg-indigo-500'],
        ['label'=>'Pass Rate',     'value'=>($analytics['pass_rate']??0).'%','icon'=>'fa-chart-line','color'=>'bg-green-500'],
        ['label'=>'Fail Rate',     'value'=>($analytics['fail_rate']??0).'%','icon'=>'fa-chart-bar', 'color'=>'bg-red-500'],
        ['label'=>'Avg GPA',       'value'=>number_format($analytics['avg_gpa']??0,2),'icon'=>'fa-star','color'=>'bg-violet-500'],
    ];
    foreach ($cards as $c):
    ?>
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 card-hover">
        <div class="w-11 h-11 <?= $c['color'] ?> rounded-xl flex items-center justify-center mb-4">
            <i class="fa-solid <?= $c['icon'] ?> text-white"></i>
        </div>
        <div class="text-2xl font-bold text-slate-800"><?= $c['value'] ?></div>
        <div class="text-sm text-slate-500 mt-0.5"><?= $c['label'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-6">
    <!-- Grade Distribution -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Grade Distribution</h3>
        <canvas id="gradeChart" height="250"></canvas>
    </div>

    <!-- Pass/Fail by Course -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Pass/Fail by Course</h3>
        <canvas id="passfailChart" height="250"></canvas>
    </div>
</div>

<!-- Top Performers -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-slate-800">Top Performing Students</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">#</th>
                    <th class="px-5 py-3 text-left">Student</th>
                    <th class="px-5 py-3 text-left">Class</th>
                    <th class="px-5 py-3 text-center">Average</th>
                    <th class="px-5 py-3 text-center">Pass</th>
                    <th class="px-5 py-3 text-center">Fail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($analytics['top_students'] ?? [] as $i => $s): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-bold <?= $i < 3 ? 'text-amber-500' : 'text-slate-400' ?>"><?= $i + 1 ?></td>
                    <td class="px-5 py-3">
                        <div class="font-medium"><?= e($s['first_name'].' '.$s['last_name']) ?></div>
                        <div class="text-xs text-slate-400 font-mono"><?= e($s['student_id']) ?></div>
                    </td>
                    <td class="px-5 py-3 text-slate-500"><?= e($s['class_name'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-center font-bold text-green-600"><?= number_format((float)$s['avg_grade'],1) ?></td>
                    <td class="px-5 py-3 text-center text-green-600"><?= $s['pass_count'] ?></td>
                    <td class="px-5 py-3 text-center text-red-500"><?= $s['fail_count'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($analytics['top_students'])): ?>
                <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">No data available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Grade distribution chart
const gradeCtx = document.getElementById('gradeChart').getContext('2d');
new Chart(gradeCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($analytics['grade_dist'] ?? [], 'letter_grade')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($analytics['grade_dist'] ?? [], 'cnt')) ?>,
            backgroundColor: ['#4F46E5','#0EA5E9','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#64748B','#14B8A6'],
            borderWidth: 2, borderColor: '#fff'
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'right' } } }
});

// Pass/fail bar chart
const pfCtx = document.getElementById('passfailChart').getContext('2d');
new Chart(pfCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($analytics['passfail_by_course'] ?? [], 'course_name')) ?>,
        datasets: [
            { label: 'Pass', data: <?= json_encode(array_column($analytics['passfail_by_course'] ?? [], 'pass_count')) ?>, backgroundColor: 'rgba(16,185,129,0.8)', borderRadius: 4 },
            { label: 'Fail', data: <?= json_encode(array_column($analytics['passfail_by_course'] ?? [], 'fail_count')) ?>, backgroundColor: 'rgba(239,68,68,0.8)',  borderRadius: 4 },
        ]
    },
    options: {
        responsive: true,
        scales: { x: { stacked: false, grid:{display:false} }, y: { beginAtZero: true } }
    }
});
</script>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
