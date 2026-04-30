<?php
// views/teacher/marks.php
$pageTitle  = 'Enter Marks';
$activePage = '/teacher/marks.php';
$role = 'teacher';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Marks Entry</h2>
        <p class="text-sm text-slate-500 mt-0.5">Enter and manage student marks</p>
    </div>
    <!-- Course Selector -->
    <form method="GET" action="" class="flex gap-2 items-center">
        <select name="cc" onchange="this.form.submit()"
                class="px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-[200px]">
            <option value="">Select a course...</option>
            <?php foreach ($courses ?? [] as $c): ?>
            <option value="<?= $c['class_course_id'] ?>" <?= ($selectedCcId == $c['class_course_id']) ? 'selected' : '' ?>>
                <?= e($c['course_name'].' ('.$c['class_name'].')') ?>
            </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($selectedCcId && !empty($students)): ?>
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <div>
            <h3 class="font-semibold text-slate-800"><?= e($courseName ?? '') ?></h3>
            <p class="text-xs text-slate-400 mt-0.5">
                Weights — Assignments: <?= $criteria['assignments_weight'] ?? 20 ?>% |
                Quizzes: <?= $criteria['quizzes_weight'] ?? 10 ?>% |
                Midterm: <?= $criteria['midterm_weight'] ?? 30 ?>% |
                Final: <?= $criteria['final_weight'] ?? 40 ?>% |
                Pass: <?= $criteria['passing_score'] ?? 50 ?>
            </p>
        </div>
        <?php if (!empty($canPublish)): ?>
        <form method="POST" action="<?= BASE_URL ?>/teacher/marks.php">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="form_action" value="publish">
            <input type="hidden" name="class_course_id" value="<?= $selectedCcId ?>">
            <button type="submit" onclick="return confirm('Publish all draft results? This cannot be undone.')"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                <i class="fa-solid fa-paper-plane"></i> Publish Results
            </button>
        </form>
        <?php endif; ?>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/teacher/marks.php" id="marksForm">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="form_action" value="save_marks">
        <input type="hidden" name="class_course_id" value="<?= $selectedCcId ?>">

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left">Student</th>
                        <th class="px-4 py-3 text-center">Assignments<br><span class="text-xs font-normal text-slate-400">/100</span></th>
                        <th class="px-4 py-3 text-center">Quizzes<br><span class="text-xs font-normal text-slate-400">/100</span></th>
                        <th class="px-4 py-3 text-center">Midterm<br><span class="text-xs font-normal text-slate-400">/100</span></th>
                        <th class="px-4 py-3 text-center">Final<br><span class="text-xs font-normal text-slate-400">/100</span></th>
                        <th class="px-4 py-3 text-center">Grade</th>
                        <th class="px-4 py-3 text-center">Result</th>
                        <th class="px-4 py-3 text-center">Remarks</th>
                        <th class="px-4 py-3 text-center">Supplementary</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($students as $s): ?>
                    <?php $isPublished = ($s['mark_status'] === 'published'); ?>
                    <tr class="hover:bg-slate-50 <?= $isPublished ? 'bg-green-50/30' : '' ?>">
                        <td class="px-4 py-3">
                            <div class="font-medium"><?= e($s['first_name'].' '.$s['last_name']) ?></div>
                            <div class="text-xs text-slate-400"><?= e($s['student_id']) ?></div>
                        </td>
                        <?php
                        $fields = ['assignments_score','quizzes_score','midterm_score','final_score'];
                        foreach ($fields as $f):
                        ?>
                        <td class="px-2 py-2 text-center">
                            <?php if ($isPublished): ?>
                            <span class="text-slate-600"><?= $s[$f] !== null ? number_format((float)$s[$f],1) : '—' ?></span>
                            <?php else: ?>
                            <input type="number" name="marks[<?= $s['id'] ?>][<?= $f ?>]"
                                   value="<?= $s[$f] !== null ? $s[$f] : '' ?>"
                                   min="0" max="100" step="0.5"
                                   class="w-16 px-2 py-1 border border-slate-200 rounded-lg text-center text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                   placeholder="—">
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="px-4 py-3 text-center">
                            <?php if ($s['calculated_grade'] !== null): ?>
                            <div class="font-bold text-lg <?= ($s['is_pass']) ? 'text-green-600' : 'text-red-600' ?>">
                                <?= number_format((float)$s['calculated_grade'],1) ?>
                            </div>
                            <div class="text-xs font-semibold <?= ($s['is_pass']) ? 'text-green-500' : 'text-red-500' ?>">
                                <?= e($s['letter_grade'] ?? '') ?>
                            </div>
                            <?php else: ?>
                            <span class="text-slate-300">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($s['is_pass'] !== null): ?>
                            <span class="badge <?= $s['is_pass'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= $s['is_pass'] ? 'PASS' : 'FAIL' ?>
                            </span>
                            <?php else: ?>
                            <span class="text-slate-300">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-2 py-2">
                            <?php if ($isPublished): ?>
                            <span class="text-xs text-slate-400"><?= e($s['remarks'] ?? '') ?></span>
                            <?php else: ?>
                            <input type="text" name="marks[<?= $s['id'] ?>][remarks]"
                                   value="<?= e($s['remarks'] ?? '') ?>"
                                   class="w-full px-2 py-1 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-blue-400"
                                   placeholder="Optional">
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($s['is_supplementary']): ?>
                                <span class="text-xs text-slate-500">Score: <?= $s['supplementary_score'] !== null ? number_format((float)$s['supplementary_score'],1) : '—' ?></span>
                            <?php elseif ($s['status'] === 'published' && $s['is_pass'] === 0): ?>
                                <form method="POST" action="<?= BASE_URL ?>/teacher/marks.php?cc=<?= $selectedCcId ?>" class="flex items-center gap-2 justify-center">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="form_action" value="submit_supplementary">
                                    <input type="hidden" name="mark_id" value="<?= $s['mark_id'] ?>">
                                    <input type="number" name="supplementary_score" min="0" max="100" step="0.5"
                                           class="w-20 px-2 py-1 border border-slate-200 rounded-lg text-center text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                           placeholder="Score" required>
                                    <button type="submit" class="px-2 py-1 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs">Submit</button>
                                </form>
                            <?php else: ?>
                                <span class="text-slate-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($isPublished): ?>
                            <span class="badge bg-green-100 text-green-700"><i class="fa-solid fa-check mr-1"></i>Published</span>
                            <?php else: ?>
                            <div class="flex gap-1 justify-center">
                                <?php if ($s['mark_id']): ?>
                                <form method="POST" action="<?= BASE_URL ?>/teacher/marks.php">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="form_action" value="delete_mark">
                                    <input type="hidden" name="mark_id" value="<?= $s['mark_id'] ?>">
                                    <button type="submit" onclick="return confirm('Delete this mark?')"
                                            class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-slate-100 bg-slate-50 flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                <i class="fa-solid fa-floppy-disk"></i> Save All Marks
            </button>
        </div>
    </form>
</div>

<?php elseif ($selectedCcId): ?>
<div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm border border-slate-100">
    <i class="fa-solid fa-users text-4xl mb-3 text-slate-200"></i>
    <p>No students enrolled in this course.</p>
</div>
<?php else: ?>
<div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm border border-slate-100">
    <i class="fa-solid fa-book-open text-4xl mb-3 text-slate-200"></i>
    <p>Select a course above to view and enter marks.</p>
</div>
<?php endif; ?>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
