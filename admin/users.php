<?php
// admin/users.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_ADMIN);

require_once ROOT_PATH . '/controllers/AdminController.php';
require_once ROOT_PATH . '/models/UserModel.php';

$adminCtrl = new AdminController();
$userModel = new UserModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.'); redirect('/admin/users.php');
    }
    $formAction = $_POST['form_action'] ?? '';
    if ($formAction === 'toggle') {
        $result = $adminCtrl->toggleUserStatus((int)$_POST['user_id']);
        setFlash('success', 'User status updated.');
    }
    if ($formAction === 'reset_password') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $user = $userModel->findById($userId);
        if ($user) {
            $token = $userModel->createPasswordReset($userId);
            $resetUrl = BASE_URL . "/index.php?action=reset_password&token={$token}";
            setFlash('success', "Reset link for {$user['username']}: <a href='{$resetUrl}' class='underline' target='_blank'>Click here</a> (valid 1 hour)");
        }
    }
    redirect('/admin/users.php');
}

$users    = $userModel->getAllWithRoles();
$search   = trim($_GET['q'] ?? '');
if ($search) {
    $users = array_filter($users, fn($u) =>
        stripos($u['username'], $search) !== false ||
        stripos($u['email'], $search) !== false ||
        stripos($u['first_name'].' '.$u['last_name'], $search) !== false
    );
}

$pageTitle  = 'User Management';
$activePage = '/admin/users.php';
$role = 'admin';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">User Management</h2>
        <p class="text-sm text-slate-500 mt-0.5">Manage all system accounts</p>
    </div>
    <form method="GET" action="" class="flex gap-2">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search users…"
               class="px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-52">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2.5 rounded-lg text-sm font-semibold">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Name</th>
                    <th class="px-5 py-3 text-left">Username</th>
                    <th class="px-5 py-3 text-left">Email</th>
                    <th class="px-5 py-3 text-left">Role</th>
                    <th class="px-5 py-3 text-center">Status</th>
                    <th class="px-5 py-3 text-left">Created</th>
                    <th class="px-5 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($users as $u): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-700 font-bold text-xs">
                                <?= strtoupper(substr($u['first_name']??$u['username'],0,1).substr($u['last_name']??'',0,1)) ?>
                            </div>
                            <span class="font-medium"><?= e(trim($u['first_name'].' '.$u['last_name'])) ?: '—' ?></span>
                        </div>
                    </td>
                    <td class="px-5 py-3 font-mono text-slate-600"><?= e($u['username']) ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= e($u['email']) ?></td>
                    <td class="px-5 py-3">
                        <?php
                        $roleColors = ['admin'=>'bg-indigo-100 text-indigo-700','secretary'=>'bg-teal-100 text-teal-700','teacher'=>'bg-blue-100 text-blue-700','student'=>'bg-violet-100 text-violet-700','parent'=>'bg-green-100 text-green-700'];
                        $rc = $roleColors[$u['role']] ?? 'bg-slate-100 text-slate-600';
                        ?>
                        <span class="badge <?= $rc ?>"><?= ucfirst($u['role']) ?></span>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="badge <?= $u['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-slate-400 whitespace-nowrap"><?= formatDate($u['created_at']) ?></td>
                    <td class="px-5 py-3">
                        <div class="flex gap-1">
                            <?php if ($u['id'] != currentUserId()): ?>
                            <form method="POST" action="" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="form_action" value="toggle">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>"
                                        class="p-1.5 <?= $u['is_active'] ? 'text-red-500 hover:bg-red-50' : 'text-green-500 hover:bg-green-50' ?> rounded-lg transition-colors">
                                    <i class="fa-solid <?= $u['is_active'] ? 'fa-ban' : 'fa-circle-check' ?>"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" action="" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="form_action" value="reset_password">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" title="Send reset link"
                                        class="p-1.5 text-indigo-500 hover:bg-indigo-50 rounded-lg transition-colors">
                                    <i class="fa-solid fa-key"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
