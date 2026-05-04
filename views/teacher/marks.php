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
        <p class="text-sm text-slate-500 mt-0.5">Manage assessments and student marks</p>
    </div>
    <!-- Course Selector -->
    <form method="GET" action="" class="flex gap-2 items-center">
        <select name="cc" onchange="this.form.submit()"
                class="px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-[250px]">
            <option value="">Select a module...</option>
            <?php if (isset($courses)): foreach ($courses as $c): ?>
            <option value="<?= $c['class_course_id'] ?>" <?= (isset($selectedCcId) && $selectedCcId == $c['class_course_id']) ? 'selected' : '' ?>>
                <?= e(($c['course_name'] ?? '').' ('.($c['class_name'] ?? '').')') ?>
            </option>
            <?php endforeach; endif; ?>
        </select>
    </form>
</div>

<?php if (isset($selectedCcId) && $selectedCcId): ?>
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Sidebar: Assessments List -->
    <div class="lg:col-span-1 space-y-4">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-semibold text-slate-800 text-sm">Assessments</h3>
                <button onclick="openAssessmentModal()" class="text-indigo-600 hover:text-indigo-700 text-xs font-bold">
                    <i class="fa-solid fa-plus"></i> NEW
                </button>
            </div>
            <div class="divide-y divide-slate-50 max-h-[500px] overflow-y-auto">
                <?php if (isset($assessments)): foreach ($assessments as $asmt): ?>
                <a href="?cc=<?= $selectedCcId ?>&assessment_id=<?= $asmt['id'] ?>" 
                   class="block px-4 py-3 hover:bg-slate-50 transition-colors <?= (isset($assessmentId) && $assessmentId == $asmt['id']) ? 'bg-indigo-50 border-l-4 border-indigo-500' : '' ?>">
                    <div class="flex justify-between items-start mb-1">
                        <span class="text-xs font-bold uppercase text-slate-400"><?= e($asmt['assessment_type'] ?? '') ?> #<?= $asmt['assessment_number'] ?? 0 ?></span>
                        <span class="text-[10px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-500"><?= e($asmt['date_of_assessment'] ?? '') ?></span>
                    </div>
                    <div class="text-sm font-medium text-slate-700"><?= e(($asmt['assessment_name'] ?? '') ?: 'Assessment '.($asmt['assessment_number'] ?? 0)) ?></div>
                    <div class="text-[11px] text-slate-400 mt-1">Max Marks: <?= $asmt['max_marks'] ?? 0 ?></div>
                </a>
                <?php endforeach; endif; ?>
                <?php if (empty($assessments)): ?>
                <div class="px-4 py-8 text-center text-slate-400 text-sm">
                    No assessments created yet.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-indigo-600 rounded-2xl p-4 text-white shadow-lg shadow-indigo-200">
            <div class="text-xs opacity-80 uppercase font-bold mb-1">Module Weight</div>
            <div class="text-2xl font-bold"><?= $moduleWeight ?? 0 ?></div>
            <div class="text-[10px] opacity-70 mt-2">Sum of formative assessments should match this weight.</div>
        </div>
    </div>

    <!-- Main Content: Mark Entry or Summary -->
    <div class="lg:col-span-3 space-y-6">
        <?php if (isset($assessmentId) && $assessmentId && isset($currentAssessment) && $currentAssessment): ?>
        <!-- Assessment Mark Entry -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-white">
                <div>
                    <h3 class="font-semibold text-slate-800">
                        Enter Marks: <?= e(($currentAssessment['assessment_name'] ?? '') ?: ucfirst($currentAssessment['assessment_type'] ?? '').' #'.($currentAssessment['assessment_number'] ?? 0)) ?>
                    </h3>
                    <p class="text-xs text-slate-500">Date: <?= e($currentAssessment['date_of_assessment'] ?? '') ?> | Max Marks: <?= e($currentAssessment['max_marks'] ?? 0) ?></p>
                </div>
                <a href="?cc=<?= $selectedCcId ?>" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            </div>

            <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/teacher/marks.php">
                <input type="hidden" name="csrf_token" value="<?= function_exists('generateCsrfToken') ? generateCsrfToken() : '' ?>">
                <input type="hidden" name="form_action" value="save_assessment_marks">
                <input type="hidden" name="class_course_id" value="<?= $selectedCcId ?>">
                <input type="hidden" name="assessment_id" value="<?= $assessmentId ?>">

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3 text-left">Student</th>
                                <th class="px-6 py-3 text-center">Score / <?= $currentAssessment['max_marks'] ?? 0 ?></th>
                                <th class="px-6 py-3 text-center">Percentage</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if (isset($students)): foreach ($students as $s): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-3">
                                    <div class="font-medium"><?= e(($s['first_name'] ?? '').' '.($s['last_name'] ?? '')) ?></div>
                                    <div class="text-xs text-slate-400"><?= e($s['student_id'] ?? '') ?></div>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <input type="number" name="marks[<?= $s['id'] ?? 0 ?>]" 
                                           value="<?= isset($assessmentMarks[$s['id']]) ? $assessmentMarks[$s['id']] : '' ?>"
                                           min="0" max="<?= $currentAssessment['max_marks'] ?? 0 ?>" step="0.5"
                                           class="w-24 px-3 py-2 border border-slate-200 rounded-lg text-center text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                           placeholder="—">
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <?php if (isset($assessmentMarks[$s['id']]) && isset($currentAssessment['max_marks']) && $currentAssessment['max_marks'] > 0): ?>
                                    <span class="text-slate-500 font-medium">
                                        <?= number_format(($assessmentMarks[$s['id']] / $currentAssessment['max_marks']) * 100, 1) ?>%
                                    </span>
                                    <?php else: ?>
                                    <span class="text-slate-300">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Save Marks
                    </button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <!-- Module Summary Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800">Module Marks Summary</h3>
                <div class="flex gap-2">
                    <?php if (isset($canPublish) && $canPublish): ?>
                    <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/teacher/marks.php">
                        <input type="hidden" name="csrf_token" value="<?= function_exists('generateCsrfToken') ? generateCsrfToken() : '' ?>">
                        <input type="hidden" name="form_action" value="publish">
                        <input type="hidden" name="class_course_id" value="<?= $selectedCcId ?>">
                        <button type="submit" onclick="return confirm('Publish all results for this module?')"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                            Publish All
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-4 text-left">Student</th>
                            <th class="px-4 py-4 text-center">Formative Assessment<br><span class="text-[10px] font-normal text-slate-400">Sum of FA</span></th>
                            <th class="px-4 py-4 text-center">Integrated Assessment<br><span class="text-[10px] font-normal text-slate-400">Sum of IA</span></th>
                            <th class="px-4 py-4 text-center">Comprehensive Assessment<br><span class="text-[10px] font-normal text-slate-400">CA Mark</span></th>
                            <th class="px-4 py-4 text-center font-bold text-slate-700">Average Marks<br><span class="text-[10px] font-normal text-slate-400">(FA+IA+CA)/N</span></th>
                            <th class="px-4 py-4 text-center">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if (isset($students)): foreach ($students as $s): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-medium"><?= e(($s['first_name'] ?? '').' '.($s['last_name'] ?? '')) ?></div>
                                <div class="text-xs text-slate-400"><?= e($s['student_id'] ?? '') ?></div>
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600"><?= (isset($s['formative_score']) && $s['formative_score'] !== null) ? number_format((float)$s['formative_score'], 1) : '<span class="text-slate-300">N/A</span>' ?></td>
                            <td class="px-4 py-3 text-center text-slate-600"><?= (isset($s['integrated_score']) && $s['integrated_score'] !== null) ? number_format((float)$s['integrated_score'], 1) : '<span class="text-slate-300">N/A</span>' ?></td>
                            <td class="px-4 py-3 text-center text-slate-600"><?= (isset($s['comprehensive_score']) && $s['comprehensive_score'] !== null) ? number_format((float)$s['comprehensive_score'], 1) : '<span class="text-slate-300">N/A</span>' ?></td>
                            <td class="px-4 py-3 text-center">
                                <?php if (isset($s['calculated_grade']) && $s['calculated_grade'] !== null): ?>
                                <div class="font-bold text-base <?= (isset($s['is_pass']) && $s['is_pass']) ? 'text-indigo-600' : 'text-red-600' ?>">
                                    <?= number_format((float)$s['calculated_grade'], 1) ?>
                                </div>
                                <div class="text-[10px] font-bold uppercase <?= (isset($s['is_pass']) && $s['is_pass']) ? 'text-indigo-400' : 'text-red-400' ?>">
                                    Grade: <?= e($s['letter_grade'] ?? '') ?>
                                </div>
                                <?php else: ?>
                                <span class="text-slate-300">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if (isset($s['is_pass']) && $s['is_pass'] !== null): ?>
                                <span class="badge <?= $s['is_pass'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                    <?= $s['is_pass'] ? 'COMPETENT' : 'NOT YET' ?>
                                </span>
                                <?php else: ?>
                                <span class="text-slate-300">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Assessment Modal -->
<div id="assessmentModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl mx-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-slate-800">Create New Assessment</h3>
            <button onclick="closeAssessmentModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/teacher/marks.php">
            <input type="hidden" name="csrf_token" value="<?= function_exists('generateCsrfToken') ? generateCsrfToken() : '' ?>">
            <input type="hidden" name="form_action" value="create_assessment">
            <input type="hidden" name="class_course_id" value="<?= $selectedCcId ?? 0 ?>">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Module</label>
                    <input type="text" readonly value="<?= e($courseName ?? '') ?>" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-500">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Type *</label>
                        <select name="assessment_type" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="formative">Formative</option>
                            <option value="integrated">Integrated</option>
                            <option value="comprehensive">Comprehensive</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Assessment # *</label>
                        <input type="number" name="assessment_number" required min="1" value="1"
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Assessment Name</label>
                    <input type="text" name="assessment_name" placeholder="e.g. Mid-term Quiz"
                           class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Date *</label>
                        <input type="date" name="date_of_assessment" required value="<?= date('Y-m-d') ?>"
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Max Marks *</label>
                        <input type="number" name="max_marks" required min="1" step="0.5" placeholder="e.g. 10"
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="closeAssessmentModal()" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 py-2.5 rounded-lg text-sm font-semibold transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg text-sm font-semibold transition-colors">Create</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openAssessmentModal() {
    document.getElementById('assessmentModal').classList.remove('hidden');
}
function closeAssessmentModal() {
    document.getElementById('assessmentModal').classList.add('hidden');
}
</script>

<?php elseif (isset($selectedCcId) && $selectedCcId): ?>
<div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm border border-slate-100">
    <i class="fa-solid fa-users text-4xl mb-3 text-slate-200"></i>
    <p>No students enrolled in this course.</p>
</div>
<?php else: ?>
<div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm border border-slate-100">
    <i class="fa-solid fa-book-open text-4xl mb-3 text-slate-200"></i>
    <p>Select a module above to manage assessments and marks.</p>
</div>
<?php endif; ?>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
