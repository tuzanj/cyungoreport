<?php
// views/admin/audit.php
$pageTitle  = 'Audit Logs';
$activePage = '/admin/audit.php';
$role = 'admin';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-slate-800">Audit Logs</h2>
    <p class="text-sm text-slate-500 mt-0.5">All system activity — last 200 records</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-semibold text-slate-800">Activity Log</h3>
        <span class="text-xs text-slate-400"><?= count($logs ?? []) ?> records</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">User</th>
                    <th class="px-5 py-3 text-left">Action</th>
                    <th class="px-5 py-3 text-left">Table</th>
                    <th class="px-5 py-3 text-left">Record ID</th>
                    <th class="px-5 py-3 text-left">IP</th>
                    <th class="px-5 py-3 text-left">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($logs ?? [] as $log): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-medium"><?= e($log['username'] ?? 'System') ?></td>
                    <td class="px-5 py-3">
                        <?php
                        $actionColors = [
                            'login_success'  => 'bg-green-100 text-green-700',
                            'login_failed'   => 'bg-red-100 text-red-700',
                            'account_locked' => 'bg-orange-100 text-orange-700',
                            'logout'         => 'bg-slate-100 text-slate-600',
                        ];
                        $color = $actionColors[$log['action']] ?? 'bg-indigo-50 text-indigo-700';
                        ?>
                        <span class="badge <?= $color ?>"><?= e($log['action']) ?></span>
                    </td>
                    <td class="px-5 py-3 text-slate-400"><?= e($log['table_name'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-slate-400"><?= $log['record_id'] ?? '—' ?></td>
                    <td class="px-5 py-3 font-mono text-xs text-slate-400"><?= e($log['ip_address'] ?? '') ?></td>
                    <td class="px-5 py-3 text-slate-400 whitespace-nowrap"><?= formatDate($log['created_at'], 'd M Y, H:i') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">No log entries.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
