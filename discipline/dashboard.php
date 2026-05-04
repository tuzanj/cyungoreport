<?php
// discipline/dashboard.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
if (currentRole() !== ROLE_ADMIN) {
    requireRole(ROLE_DISCIPLINE_MASTER);
}

require_once ROOT_PATH . '/models/DisciplineModel.php';

$disciplineModel = new DisciplineModel();
$db = Database::getInstance();

$currentYear = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$yearId = $currentYear ? (int)$currentYear['id'] : 0;

$stats = [
    'total_incidents' => $db->fetchOne("SELECT COUNT(*) as c FROM student_discipline WHERE academic_year_id = ?", [$yearId])['c'] ?? 0,
    'today_incidents' => $db->fetchOne("SELECT COUNT(*) as c FROM student_discipline WHERE incident_date = CURDATE()")['c'] ?? 0,
    'top_faults' => $db->fetchAll("SELECT f.name, COUNT(sd.id) as cnt FROM student_discipline sd JOIN faults f ON f.id = sd.fault_id GROUP BY f.id ORDER BY cnt DESC LIMIT 5")
];

$recentIncidents = $db->fetchAll(
    "SELECT sd.*, f.name as fault_name, s.first_name, s.last_name, s.student_id as student_reg_id, cl.name as class_name
     FROM student_discipline sd
     JOIN faults f ON f.id = sd.fault_id
     JOIN students s ON s.id = sd.student_id
     LEFT JOIN enrollments e ON e.student_id = s.id AND e.academic_year_id = ?
     LEFT JOIN classes cl ON cl.id = e.class_id
     ORDER BY sd.incident_date DESC LIMIT 10",
    [$yearId]
);

// View
$pageTitle = 'Discipline Dashboard';
$activePage = '/discipline/dashboard.php';
$role = currentRole();
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-slate-800">Discipline Overview</h2>
    <p class="text-sm text-slate-500">Monitor and manage student disciplinary records</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center text-red-600">
                <i class="fa-solid fa-gavel text-xl"></i>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Incidents</div>
                <div class="text-2xl font-bold text-slate-800"><?= $stats['total_incidents'] ?></div>
            </div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center text-orange-600">
                <i class="fa-solid fa-calendar-day text-xl"></i>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Recorded Today</div>
                <div class="text-2xl font-bold text-slate-800"><?= $stats['today_incidents'] ?></div>
            </div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600">
                <i class="fa-solid fa-users text-xl"></i>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Students</div>
                <div class="text-2xl font-bold text-slate-800"><?= $db->fetchOne("SELECT COUNT(*) as c FROM students WHERE status='active'")['c'] ?? 0 ?></div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Incidents -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800">Recent Disciplinary Incidents</h3>
                <a href="/discipline/students.php" class="text-xs font-bold text-indigo-600 hover:underline">VIEW ALL</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3 text-left">Student</th>
                            <th class="px-5 py-3 text-left">Fault</th>
                            <th class="px-5 py-3 text-center">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach ($recentIncidents as $inc): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-3">
                                <div class="font-medium"><?= e($inc['first_name'].' '.$inc['last_name']) ?></div>
                                <div class="text-[10px] text-slate-400"><?= e($inc['class_name'] ?? 'No Class') ?></div>
                            </td>
                            <td class="px-5 py-3 text-slate-600"><?= e($inc['fault_name']) ?></td>
                            <td class="px-5 py-3 text-center text-slate-500"><?= date('M d, Y', strtotime($inc['incident_date'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentIncidents)): ?>
                        <tr><td colspan="3" class="px-5 py-8 text-center text-slate-400">No incidents recorded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Common Faults -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="font-semibold text-slate-800">Frequent Issues</h3>
            </div>
            <div class="p-5 space-y-4">
                <?php foreach ($stats['top_faults'] as $fault): ?>
                <div>
                    <div class="flex justify-between text-xs mb-1.5">
                        <span class="font-medium text-slate-700"><?= e($fault['name']) ?></span>
                        <span class="text-slate-400 font-bold"><?= $fault['cnt'] ?> times</span>
                    </div>
                    <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-red-500 h-full rounded-full" style="width: <?= min(100, ($fault['cnt'] / max(1, $stats['total_incidents'])) * 100) ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($stats['top_faults'])): ?>
                <div class="text-center py-4 text-slate-400 text-sm italic">No data available</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
