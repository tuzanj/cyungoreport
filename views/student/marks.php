<?php
// views/student/marks.php
$pageTitle  = 'My Marks';
$activePage = '/student/marks.php';
$role = 'student';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-xl font-bold text-slate-800">My Marks</h2>
        <p class="text-sm text-slate-500 mt-0.5"><?= e($yearName ?? 'Current Year') ?></p>
    </div>
    <div class="flex items-center gap-3">
        <div class="text-right">
            <div class="text-2xl font-bold text-violet-600"><?= number_format($gpa ?? 0, 2) ?></div>
            <div class="text-xs text-slate-400">GPA</div>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Course</th>
                    <th class="px-5 py-3 text-center">Assign.</th>
                    <th class="px-5 py-3 text-center">Quiz</th>
                    <th class="px-5 py-3 text-center">Midterm</th>
                    <th class="px-5 py-3 text-center">Final</th>
                    <th class="px-5 py-3 text-center">Total</th>
                    <th class="px-5 py-3 text-center">Letter</th>
                    <th class="px-5 py-3 text-center">Result</th>
                    <th class="px-5 py-3 text-center">Supplementary</th>
                    <th class="px-5 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($marks ?? [] as $m): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3">
                        <div class="font-medium"><?= e($m['course_name']) ?></div>
                        <div class="text-xs text-slate-400"><?= e($m['code']) ?> · <?= e($m['teacher_first'].' '.$m['teacher_last']) ?></div>
                    </td>
                    <?php foreach (['assignments_score','quizzes_score','midterm_score','final_score'] as $f): ?>
                    <td class="px-5 py-3 text-center text-slate-600">
                        <?= $m[$f] !== null ? number_format((float)$m[$f],1) : '<span class="text-slate-300">—</span>' ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="px-5 py-3 text-center font-bold <?= ($m['is_pass'] ?? false) ? 'text-green-600' : 'text-red-600' ?>">
                        <?= $m['calculated_grade'] !== null ? number_format((float)$m['calculated_grade'],1) : '<span class="text-slate-300 font-normal">—</span>' ?>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <?php if ($m['letter_grade']): ?>
                        <span class="badge <?= ($m['is_pass'] ?? false) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            <?= e($m['letter_grade']) ?>
                        </span>
                        <?php else: ?><span class="text-slate-300">—</span><?php endif; ?>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <?php if ($m['status'] === 'published'): ?>
                        <span class="badge <?= $m['is_pass'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            <?= $m['is_pass'] ? 'PASS' : 'FAIL' ?>
                        </span>
                        <?php else: ?>
                        <span class="badge bg-yellow-100 text-yellow-700">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <?php if (!empty($m['is_supplementary']) && $m['supplementary_score'] !== null): ?>
                        <span class="badge bg-orange-100 text-orange-700">Score <?= number_format((float)$m['supplementary_score'],1) ?></span>
                        <?php else: ?>
                        <span class="text-slate-400">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <?php if ($m['status'] === 'published' && !$m['is_pass']): ?>
                        <button onclick="openClaimModal(<?= $m['id'] ?>)"
                                class="text-xs bg-orange-50 text-orange-700 hover:bg-orange-100 px-2.5 py-1 rounded-lg transition-colors font-medium">
                            <i class="fa-solid fa-flag mr-1"></i>Claim
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($marks)): ?>
                <tr><td colspan="9" class="px-5 py-10 text-center text-slate-400">No marks available yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Grade Claim Modal -->
<div id="claimModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl mx-4">
        <h3 class="text-lg font-semibold text-slate-800 mb-1">Raise Grade Claim</h3>
        <p class="text-sm text-slate-500 mb-4">Explain why you believe your grade should be reviewed.</p>
        <form method="POST" action="<?= BASE_URL ?>/student/marks.php">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="form_action" value="raise_claim">
            <input type="hidden" name="mark_id" id="claimMarkId">
            <textarea name="reason" required rows="4" placeholder="Describe your reason..."
                      class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 resize-none mb-4"></textarea>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('claimModal').classList.add('hidden')"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 py-2.5 rounded-lg text-sm font-semibold transition-colors">Cancel</button>
                <button type="submit" class="flex-1 bg-violet-600 hover:bg-violet-700 text-white py-2.5 rounded-lg text-sm font-semibold transition-colors">Submit Claim</button>
            </div>
        </form>
    </div>
</div>
<script>
function openClaimModal(markId) {
    document.getElementById('claimMarkId').value = markId;
    document.getElementById('claimModal').classList.remove('hidden');
}
</script>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
