<?php
// views/admin/teachers.php
$pageTitle  = 'Teachers';
$activePage = '/admin/teachers.php';
$role = 'admin';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Teacher Management</h2>
        <p class="text-sm text-slate-500 mt-0.5">Add and manage teaching staff</p>
    </div>
    <a href="?action=new" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
        <i class="fa-solid fa-user-plus"></i> Add Teacher
    </a>
</div>

<?php if (in_array($action ?? '', ['new','edit'])): ?>
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
    <h3 class="font-semibold text-slate-800 mb-4"><?= ($action==='edit') ? 'Edit Teacher' : 'Add New Teacher' ?></h3>
    <form method="POST" action="<?= BASE_URL ?>/admin/teachers.php">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="form_action" value="<?= ($action==='edit') ? 'update' : 'create' ?>">
        <?php if ($action==='edit'): ?>
        <input type="hidden" name="teacher_id" value="<?= e($teacher['id'] ?? '') ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if ($action === 'new'): ?>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Username *</label>
                <input type="text" name="username" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g. john.doe">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Email *</label>
                <input type="email" name="email" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <?php endif; ?>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name *</label>
                <input type="text" name="first_name" required value="<?= e($teacher['first_name'] ?? '') ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name *</label>
                <input type="text" name="last_name" required value="<?= e($teacher['last_name'] ?? '') ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Gender *</label>
                <select name="gender" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <?php foreach (['male','female','other'] as $g): ?>
                    <option value="<?= $g ?>" <?= (($teacher['gender'] ?? '') === $g) ? 'selected' : '' ?>><?= ucfirst($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Date of Birth</label>
                <input type="date" name="date_of_birth" value="<?= e($teacher['date_of_birth'] ?? '') ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone</label>
                <input type="text" name="phone" value="<?= e($teacher['phone'] ?? '') ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Qualification</label>
                <input type="text" name="qualification" value="<?= e($teacher['qualification'] ?? '') ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g. M.Sc Mathematics">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Trade</label>
                <select name="trade_id" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">— None —</option>
                    <?php foreach ($departments ?? [] as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= (($teacher['trade_id'] ?? '') == $d['id']) ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Hire Date</label>
                <input type="date" name="hire_date" value="<?= e($teacher['hire_date'] ?? date('Y-m-d')) ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div class="flex gap-3 mt-5">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">
                <i class="fa-solid fa-floppy-disk mr-1"></i><?= ($action==='edit') ? 'Update Teacher' : 'Create Account' ?>
            </button>
            <a href="<?= BASE_URL ?>/admin/teachers.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Assign Course Form -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
    <h3 class="font-semibold text-slate-800 mb-4">Assign Course to Teacher</h3>
    <form method="POST" action="<?= BASE_URL ?>/admin/teachers.php" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="form_action" value="assign_course">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Class</label>
            <select name="class_id" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <?php foreach ($classes ?? [] as $cl): ?>
                <option value="<?= $cl['id'] ?>"><?= e($cl['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Course</label>
            <select name="course_id" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <?php foreach ($courses ?? [] as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['code'].' - '.$c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Teacher</label>
            <select name="teacher_id" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <?php foreach ($teachers ?? [] as $t): ?>
                <option value="<?= $t['id'] ?>"><?= e($t['first_name'].' '.$t['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors">
                <i class="fa-solid fa-link mr-1"></i>Assign
            </button>
        </div>
    </form>
</div>

<!-- Teachers Table -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-slate-800">All Teachers <span class="text-slate-400 font-normal text-sm">(<?= count($teachers ?? []) ?>)</span></h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Employee ID</th>
                    <th class="px-5 py-3 text-left">Name</th>
                    <th class="px-5 py-3 text-left">Email</th>
                    <th class="px-5 py-3 text-left">Department</th>
                    <th class="px-5 py-3 text-left">Qualification</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th class="px-5 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($teachers ?? [] as $t): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-5 py-3 font-mono font-semibold text-blue-700"><?= e($t['employee_id']) ?></td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-700 font-bold text-xs">
                                <?= strtoupper(substr($t['first_name'],0,1).substr($t['last_name'],0,1)) ?>
                            </div>
                            <span class="font-medium"><?= e($t['first_name'].' '.$t['last_name']) ?></span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-slate-500"><?= e($t['email']) ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= e($t['department_name'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= e($t['qualification'] ?? '—') ?></td>
                    <td class="px-5 py-3">
                        <span class="badge <?= $t['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            <?= $t['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex gap-2">
                            <a href="?action=edit&id=<?= $t['id'] ?>" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="?action=toggle&user_id=<?= $t['user_id'] ?>"
                               class="p-1.5 <?= $t['is_active'] ? 'text-red-500 hover:bg-red-50' : 'text-green-500 hover:bg-green-50' ?> rounded-lg transition-colors"
                               title="<?= $t['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                <i class="fa-solid <?= $t['is_active'] ? 'fa-ban' : 'fa-circle-check' ?>"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($teachers)): ?>
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">No teachers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
