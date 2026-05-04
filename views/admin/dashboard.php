<?php
// views/admin/dashboard.php
// Variables: $stats (array), $pageTitle, $activePage, $role

$pageTitle  = 'Dashboard';
$activePage = '/admin/dashboard.php';
$role = 'admin';

include ROOT_PATH . '/views/components/layout.php';
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
    <?php
    $cards = [
        ['label'=>'Total Students', 'value'=>$stats['total_students'], 'icon'=>'fa-user-graduate', 'color'=>'bg-indigo-500', 'light'=>'bg-indigo-50 text-indigo-600'],
        ['label'=>'Teachers',       'value'=>$stats['total_teachers'], 'icon'=>'fa-chalkboard-user','color'=>'bg-blue-500',   'light'=>'bg-blue-50 text-blue-600'],
        ['label'=>'Assessments',    'value'=>$stats['total_assessments'], 'icon'=>'fa-file-pen',   'color'=>'bg-teal-500',   'light'=>'bg-teal-50 text-teal-600'],
        ['label'=>'Discipline',     'value'=>$stats['total_incidents'],   'icon'=>'fa-gavel',      'color'=>'bg-red-500',    'light'=>'bg-red-50 text-red-600'],
    ];
    foreach ($cards as $c):
    ?>
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 card-hover">
        <div class="flex items-center justify-between mb-4">
            <div class="w-11 h-11 <?= $c['color'] ?> rounded-xl flex items-center justify-center">
                <i class="fa-solid <?= $c['icon'] ?> text-white text-lg"></i>
            </div>
            <span class="text-xs font-medium <?= $c['light'] ?> px-2.5 py-1 rounded-full">Active</span>
        </div>
        <div class="text-3xl font-bold text-slate-800"><?= number_format($c['value']) ?></div>
        <div class="text-sm text-slate-500 mt-1"><?= $c['label'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-6">
    <!-- Enrollment Chart -->
    <div class="xl:col-span-2 bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-800">Enrollment by Class</h3>
            <span class="text-xs text-slate-400">Current Academic Year</span>
        </div>
        <canvas id="enrollmentChart" height="220"></canvas>
    </div>

    <!-- Pending Actions -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
        <h3 class="font-semibold text-slate-800 mb-4">Pending Actions</h3>
        <div class="space-y-3">
            <a href="<?= BASE_URL ?>/admin/claims.php"
               class="flex items-center justify-between p-3 bg-yellow-50 rounded-xl hover:bg-yellow-100 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center">
                        <i class="fa-solid fa-flag text-white text-xs"></i>
                    </div>
                    <span class="text-sm font-medium text-slate-700">Grade Claims</span>
                </div>
                <span class="badge bg-yellow-100 text-yellow-800"><?= $stats['pending_claims'] ?></span>
            </a>
        </div>

        <!-- Quick Links -->
        <h3 class="font-semibold text-slate-800 mt-5 mb-3">Staff Functions</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <?php
            $quickLinks = [
                ['href'=>'/teacher/marks.php',             'label'=>'Record Marks',  'icon'=>'fa-pen-to-square', 'color'=>'indigo'],
                ['href'=>'/discipline/dashboard.php',      'label'=>'Discipline',    'icon'=>'fa-gavel', 'color'=>'red'],
                ['href'=>'/admin/courses.php?action=new',  'label'=>'New Course',    'icon'=>'fa-plus', 'color'=>'teal'],
                ['href'=>'/admin/timetable.php',           'label'=>'Timetable',     'icon'=>'fa-calendar', 'color'=>'violet'],
            ];
            foreach ($quickLinks as $ql):
            ?>
            <a href="<?= BASE_URL . $ql['href'] ?>"
               class="flex flex-col items-center gap-1 p-3 bg-slate-50 hover:bg-<?= $ql['color'] ?>-50 rounded-xl text-center transition-colors">
                <i class="fa-solid <?= $ql['icon'] ?> text-<?= $ql['color'] ?>-600 text-sm"></i>
                <span class="text-xs text-slate-600 font-medium"><?= $ql['label'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Recent Audit Logs -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-semibold text-slate-800">Recent Activity</h3>
        <a href="<?= BASE_URL ?>/admin/audit.php" class="text-xs text-indigo-600 hover:underline">View all</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">User</th>
                    <th class="px-5 py-3 text-left">Action</th>
                    <th class="px-5 py-3 text-left">Table</th>
                    <th class="px-5 py-3 text-left">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($stats['recent_logs'] as $log): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-5 py-3 font-medium"><?= e($log['username'] ?? 'System') ?></td>
                    <td class="px-5 py-3">
                        <span class="badge bg-indigo-50 text-indigo-700"><?= e($log['action']) ?></span>
                    </td>
                    <td class="px-5 py-3 text-slate-400"><?= e($log['table_name'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-slate-400"><?= formatDate($log['created_at'], 'd M, H:i') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($stats['recent_logs'])): ?>
                <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400">No activity yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const ctx = document.getElementById('enrollmentChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($stats['enrollment_by_class'], 'name')) ?>,
        datasets: [{
            label: 'Students',
            data: <?= json_encode(array_column($stats['enrollment_by_class'], 'cnt')) ?>,
            backgroundColor: 'rgba(79, 70, 229, 0.85)',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
        }
    }
});
</script>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
