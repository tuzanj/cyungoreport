<?php
// discipline/faults.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
if (currentRole() !== ROLE_ADMIN) {
    requireRole(ROLE_DISCIPLINE_MASTER);
}

require_once ROOT_PATH . '/models/DisciplineModel.php';
require_once ROOT_PATH . '/models/AuditModel.php';

$disciplineModel = new DisciplineModel();
$auditModel = new AuditModel();
$db = Database::getInstance();

$action = $_GET['action'] ?? '';
$faultId = (int)($_GET['id'] ?? 0);
$fault = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.');
        redirect('/discipline/faults.php');
    }

    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'create') {
        $name = trim($_POST['name'] ?? '');
        $points = (int)($_POST['points_deduction'] ?? 0);
        
        if ($name) {
            $id = $disciplineModel->createFault($name, $points);
            $auditModel->log('fault_created', 'faults', $id, null, ['name' => $name, 'points' => $points]);
            setFlash('success', 'Fault created successfully.');
        } else {
            setFlash('danger', 'Fault name is required.');
        }
        redirect('/discipline/faults.php');
    }

    if ($formAction === 'update') {
        $id = (int)$_POST['fault_id'];
        $name = trim($_POST['name'] ?? '');
        $points = (int)($_POST['points_deduction'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name) {
            $disciplineModel->updateFault($id, $name, $points, $isActive);
            $auditModel->log('fault_updated', 'faults', $id, null, ['name' => $name, 'points' => $points, 'active' => $isActive]);
            setFlash('success', 'Fault updated successfully.');
        } else {
            setFlash('danger', 'Fault name is required.');
        }
        redirect('/discipline/faults.php');
    }

    if ($formAction === 'delete') {
        $id = (int)$_POST['fault_id'];
        $disciplineModel->delete($id); // Note: delete is inherited from BaseModel
        $auditModel->log('fault_deleted', 'faults', $id);
        setFlash('success', 'Fault deleted.');
        redirect('/discipline/faults.php');
    }
}

if ($action === 'edit' && $faultId) {
    $fault = $db->fetchOne("SELECT * FROM faults WHERE id = ?", [$faultId]);
}

$faults = $disciplineModel->getFaults(false); // Get all, including inactive

// View
$pageTitle = 'Manage Faults';
$activePage = '/discipline/faults.php';
$role = currentRole();
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Faults & Deductions</h2>
        <p class="text-sm text-slate-500">Configure disciplinary rules and point deductions</p>
    </div>
    <a href="?action=new" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
        <i class="fa-solid fa-plus"></i> New Fault
    </a>
</div>

<?php if ($action === 'new' || $action === 'edit'): ?>
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6 max-w-2xl">
    <h3 class="font-semibold text-slate-800 mb-4"><?= $action === 'edit' ? 'Edit Fault' : 'Add New Fault' ?></h3>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="form_action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">
        <?php if ($action === 'edit'): ?>
        <input type="hidden" name="fault_id" value="<?= $fault['id'] ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Fault Name *</label>
                <input type="text" name="name" required value="<?= e($fault['name'] ?? '') ?>"
                       class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       placeholder="e.g. Using phone in school">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Points Deduction *</label>
                <input type="number" name="points_deduction" required min="0" value="<?= e($fault['points_deduction'] ?? 0) ?>"
                       class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-[10px] text-slate-400 mt-1">Number of points to subtract from 40.</p>
            </div>
            <div class="flex items-end pb-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" <?= (!isset($fault) || ($fault['is_active'] ?? 1)) ? 'checked' : '' ?>
                           class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                    <span class="text-sm font-medium text-slate-700">Active / Enable</span>
                </label>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors">
                <?= $action === 'edit' ? 'Update Fault' : 'Create Fault' ?>
            </button>
            <a href="/discipline/faults.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4 text-left">Fault Name</th>
                    <th class="px-6 py-4 text-center">Points Deduction</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($faults as $f): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4 font-medium text-slate-700"><?= e($f['name']) ?></td>
                    <td class="px-6 py-4 text-center">
                        <span class="font-bold <?= $f['points_deduction'] > 0 ? 'text-red-600' : 'text-slate-400' ?>">
                            -<?= $f['points_deduction'] ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="badge <?= $f['is_active'] ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' ?>">
                            <?= $f['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <a href="?action=edit&id=<?= $f['id'] ?>" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <button onclick="confirmDelete(<?= $f['id'] ?>, '<?= addslashes(e($f['name'])) ?>')"
                                    class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($faults)): ?>
                <tr><td colspan="4" class="px-6 py-10 text-center text-slate-400 italic">No faults defined yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete Modal -->
<dialog id="deleteModal" class="p-6 rounded-xl shadow-xl backdrop:bg-black/50">
    <div class="max-w-sm">
        <h3 class="text-lg font-bold mb-2">Delete Fault?</h3>
        <p class="text-sm text-slate-600 mb-6">Are you sure you want to delete <span id="deleteFaultName" class="font-semibold text-slate-800"></span>? This cannot be undone.</p>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="fault_id" id="deleteFaultId">
            <div class="flex gap-2 justify-end">
                <button type="button" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50" onclick="document.getElementById('deleteModal').close()">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Delete</button>
            </div>
        </form>
    </div>
</dialog>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteFaultId').value = id;
    document.getElementById('deleteFaultName').textContent = name;
    document.getElementById('deleteModal').showModal();
}
</script>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
