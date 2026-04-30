<?php
// views/secretary/dashboard.php
$pageTitle  = 'Dashboard';
$activePage = '/secretary/dashboard.php';
$role = 'secretary';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
    <?php
    $cards = [
        ['label'=>'Total Students','value'=>$stats['total_students'],'icon'=>'fa-user-graduate','color'=>'bg-teal-500'],
        ['label'=>'Enrolled Today', 'value'=>$stats['enrolled_today'],'icon'=>'fa-user-check',  'color'=>'bg-indigo-500'],
        ['label'=>'Total Classes',  'value'=>$stats['total_classes'], 'icon'=>'fa-building',     'color'=>'bg-blue-500'],
        ['label'=>'Pending Links',  'value'=>$stats['pending_parents'],'icon'=>'fa-link',        'color'=>'bg-violet-500'],
    ];
    foreach ($cards as $c):
    ?>
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 card-hover">
        <div class="w-11 h-11 <?= $c['color'] ?> rounded-xl flex items-center justify-center mb-4">
            <i class="fa-solid <?= $c['icon'] ?> text-white text-lg"></i>
        </div>
        <div class="text-3xl font-bold text-slate-800"><?= number_format($c['value']) ?></div>
        <div class="text-sm text-slate-500 mt-1"><?= $c['label'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <!-- Quick Actions -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Quick Actions</h3>
        <div class="space-y-2">
            <?php
            $actions = [
                ['href'=>'/secretary/register_student.php','icon'=>'fa-user-plus','label'=>'Register New Student','color'=>'text-teal-600 bg-teal-50 hover:bg-teal-100'],
                ['href'=>'/secretary/link_parent.php',     'icon'=>'fa-link',     'label'=>'Link Parent/Guardian', 'color'=>'text-indigo-600 bg-indigo-50 hover:bg-indigo-100'],
                ['href'=>'/secretary/students.php',        'icon'=>'fa-users',    'label'=>'View All Students',    'color'=>'text-blue-600 bg-blue-50 hover:bg-blue-100'],
                ['href'=>'/secretary/enrollment_report.php','icon'=>'fa-file-invoice','label'=>'Enrollment Report','color'=>'text-violet-600 bg-violet-50 hover:bg-violet-100'],
            ];
            foreach ($actions as $a):
            ?>
            <a href="<?= BASE_URL . $a['href'] ?>"
               class="flex items-center gap-3 p-3 rounded-xl <?= $a['color'] ?> transition-colors">
                <i class="fa-solid <?= $a['icon'] ?> w-5 text-center"></i>
                <span class="text-sm font-medium text-slate-700"><?= $a['label'] ?></span>
                <i class="fa-solid fa-chevron-right ml-auto text-xs opacity-40"></i>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Registrations -->
    <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Recent Registrations</h3>
            <a href="<?= BASE_URL ?>/secretary/students.php" class="text-xs text-teal-600 hover:underline">View all</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3 text-left">Student ID</th>
                        <th class="px-5 py-3 text-left">Name</th>
                        <th class="px-5 py-3 text-left">Class</th>
                        <th class="px-5 py-3 text-left">Enrolled</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($recentStudents ?? [] as $s): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-mono text-teal-700 font-semibold"><?= e($s['student_id']) ?></td>
                        <td class="px-5 py-3 font-medium"><?= e($s['first_name'].' '.$s['last_name']) ?></td>
                        <td class="px-5 py-3 text-slate-500"><?= e($s['class_name'] ?? '—') ?></td>
                        <td class="px-5 py-3 text-slate-400"><?= formatDate($s['enrollment_date'] ?? $s['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentStudents)): ?>
                    <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400">No recent registrations.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
