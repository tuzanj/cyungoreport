<?php
// admin/claims.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_ADMIN);

require_once ROOT_PATH . '/controllers/AdminController.php';

$adminCtrl = new AdminController();
$db        = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.'); redirect('/admin/claims.php');
    }
    $result = $adminCtrl->resolveGradeClaim(
        (int)$_POST['claim_id'],
        trim($_POST['response'] ?? ''),
        $_POST['status'] ?? 'resolved'
    );
    setFlash($result['success'] ? 'success' : 'danger', $result['message'] ?? '');
    redirect('/admin/claims.php');
}

$claims = $db->fetchAll(
    "SELECT gc.*, s.first_name, s.last_name, s.student_id as student_code,
            c.name as course_name, m.calculated_grade, m.letter_grade
     FROM grade_claims gc
     JOIN students s ON s.id=gc.student_id
     JOIN marks m ON m.id=gc.mark_id
     JOIN class_courses cc ON cc.id=m.class_course_id
     JOIN courses c ON c.id=cc.course_id
     ORDER BY gc.created_at DESC"
);

$pageTitle  = 'Grade Claims';
$activePage = '/admin/claims.php';
$role = 'admin';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-slate-800">Grade Claims</h2>
    <p class="text-sm text-slate-500 mt-0.5">Review and resolve student grade appeal requests</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Student</th>
                    <th class="px-5 py-3 text-left">Course</th>
                    <th class="px-5 py-3 text-center">Grade</th>
                    <th class="px-5 py-3 text-left">Reason</th>
                    <th class="px-5 py-3 text-center">Status</th>
                    <th class="px-5 py-3 text-left">Submitted</th>
                    <th class="px-5 py-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($claims as $cl): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3">
                        <div class="font-medium"><?= e($cl['first_name'].' '.$cl['last_name']) ?></div>
                        <div class="text-xs text-slate-400 font-mono"><?= e($cl['student_code']) ?></div>
                    </td>
                    <td class="px-5 py-3 font-medium"><?= e($cl['course_name']) ?></td>
                    <td class="px-5 py-3 text-center">
                        <span class="font-bold"><?= number_format((float)$cl['calculated_grade'],1) ?></span>
                        <span class="badge bg-red-100 text-red-700 ml-1"><?= e($cl['letter_grade'] ?? '') ?></span>
                    </td>
                    <td class="px-5 py-3 max-w-xs">
                        <p class="text-slate-600 text-xs line-clamp-2"><?= e($cl['reason']) ?></p>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <?php
                        $statusColors = ['pending'=>'bg-yellow-100 text-yellow-700','under_review'=>'bg-blue-100 text-blue-700','resolved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700'];
                        $sc = $statusColors[$cl['status']] ?? 'bg-slate-100 text-slate-600';
                        ?>
                        <span class="badge <?= $sc ?>"><?= ucfirst(str_replace('_',' ',$cl['status'])) ?></span>
                    </td>
                    <td class="px-5 py-3 text-slate-400 whitespace-nowrap"><?= formatDate($cl['created_at'], 'd M Y') ?></td>
                    <td class="px-5 py-3">
                        <?php if (in_array($cl['status'], ['pending','under_review'])): ?>
                        <button onclick="openResolveModal(<?= $cl['id'] ?>, '<?= e(addslashes($cl['first_name'].' '.$cl['last_name'])) ?>')"
                                class="text-xs bg-indigo-50 text-indigo-700 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-colors font-medium">
                            Resolve
                        </button>
                        <?php else: ?>
                        <span class="text-xs text-slate-400"><?= e($cl['response'] ?? 'No response') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($claims)): ?>
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">No grade claims.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Resolve Modal -->
<div id="resolveModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl mx-4">
        <h3 class="text-lg font-semibold text-slate-800 mb-1">Resolve Claim</h3>
        <p class="text-sm text-slate-500 mb-4">Student: <strong id="claimStudentName"></strong></p>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="claim_id" id="resolveClaimId">
            <div class="mb-3">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Decision</label>
                <select name="status" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="resolved">Resolved (Accept)</option>
                    <option value="rejected">Rejected</option>
                    <option value="under_review">Mark Under Review</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Response / Notes</label>
                <textarea name="response" rows="3"
                          class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                          placeholder="Provide feedback to the student…"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('resolveModal').classList.add('hidden')"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 py-2.5 rounded-lg text-sm font-semibold">Cancel</button>
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg text-sm font-semibold">Submit</button>
            </div>
        </form>
    </div>
</div>
<script>
function openResolveModal(id, name) {
    document.getElementById('resolveClaimId').value = id;
    document.getElementById('claimStudentName').textContent = name;
    document.getElementById('resolveModal').classList.remove('hidden');
}
</script>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
