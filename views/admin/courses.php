<?php
// views/admin/courses.php
// Variables: $courses, $departments, $years, $action, $course (for edit)
$pageTitle  = 'Courses';
$activePage = '/admin/courses.php';
$role = 'admin';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Course Management</h2>
        <p class="text-sm text-slate-500 mt-0.5">Create and manage curriculum courses</p>
    </div>
    <a href="?action=new" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
        <i class="fa-solid fa-plus"></i> New Course
    </a>
</div>

<?php if (in_array($action ?? '', ['new','edit'])): ?>
<!-- Course Form -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
    <h3 class="font-semibold text-slate-800 mb-4"><?= ($action === 'edit') ? 'Edit Course' : 'Add New Course' ?></h3>
    <form method="POST" action="<?= BASE_URL ?>/admin/courses.php">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="form_action" value="<?= ($action === 'edit') ? 'update' : 'create' ?>">
        <?php if ($action === 'edit'): ?>
        <input type="hidden" name="course_id" value="<?= e($course['id']) ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Course Code *</label>
                <input type="text" name="code" required value="<?= e($course['code'] ?? '') ?>"
                       class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       placeholder="e.g. MATH101">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Course Name *</label>
                <input type="text" name="name" required value="<?= e($course['name'] ?? '') ?>"
                       class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       placeholder="e.g. Mathematics">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Type *</label>
                <select name="type" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <?php foreach (['core','general','elective'] as $t): ?>
                    <option value="<?= $t ?>" <?= (($course['type'] ?? 'core') === $t) ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Credits</label>
                <input type="number" name="credits" min="1" max="10" value="<?= e($course['credits'] ?? 3) ?>"
                       class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Department</label>
                <select name="department_id" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">— None —</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= (($course['department_id'] ?? '') == $d['id']) ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Description</label>
                <input type="text" name="description" value="<?= e($course['description'] ?? '') ?>"
                       class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       placeholder="Optional">
            </div>
        </div>

        <div class="flex gap-3 mt-5">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">
                <i class="fa-solid fa-floppy-disk mr-1"></i><?= ($action === 'edit') ? 'Update Course' : 'Create Course' ?>
            </button>
            <a href="<?= BASE_URL ?>/admin/courses.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">Cancel</a>
        </div>
    </form>
</div>

<!-- Grading Criteria Section (only on edit) -->
<?php if ($action === 'edit' && !empty($course)): ?>
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
    <h3 class="font-semibold text-slate-800 mb-1">Grading Criteria</h3>
    <p class="text-xs text-slate-400 mb-4">Weights must sum to 100%</p>
    <form method="POST" action="<?= BASE_URL ?>/admin/courses.php">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="form_action" value="set_grading">
        <input type="hidden" name="course_id" value="<?= e($course['id']) ?>">
        <input type="hidden" name="year_id" value="<?= e($currentYearId ?? '') ?>">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
            <?php
            $gc = $gradingCriteria ?? null;
            $fields = [
                ['name'=>'assignments_weight','label'=>'Assignments %','default'=>$gc['assignments_weight']??20],
                ['name'=>'quizzes_weight',    'label'=>'Quizzes %',    'default'=>$gc['quizzes_weight']??10],
                ['name'=>'midterm_weight',    'label'=>'Midterm %',    'default'=>$gc['midterm_weight']??30],
                ['name'=>'final_weight',      'label'=>'Final %',      'default'=>$gc['final_weight']??40],
                ['name'=>'passing_score',     'label'=>'Pass Score',   'default'=>$gc['passing_score']??50],
            ];
            foreach ($fields as $f):
            ?>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1"><?= $f['label'] ?></label>
                <input type="number" name="<?= $f['name'] ?>" step="0.01" min="0" max="100"
                       value="<?= $f['default'] ?>"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="mt-4 bg-teal-600 hover:bg-teal-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">
            <i class="fa-solid fa-sliders mr-1"></i>Save Criteria
        </button>
    </form>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Courses Table -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-slate-800">All Courses <span class="text-slate-400 font-normal text-sm">(<?= count($courses) ?>)</span></h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Code</th>
                    <th class="px-5 py-3 text-left">Name</th>
                    <th class="px-5 py-3 text-left">Type</th>
                    <th class="px-5 py-3 text-left">Credits</th>
                    <th class="px-5 py-3 text-left">Department</th>
                    <th class="px-5 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($courses as $c): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-5 py-3 font-mono font-semibold text-indigo-700"><?= e($c['code']) ?></td>
                    <td class="px-5 py-3 font-medium"><?= e($c['name']) ?></td>
                    <td class="px-5 py-3">
                        <?php $typeBadge = ['core'=>'bg-blue-100 text-blue-700','general'=>'bg-green-100 text-green-700','elective'=>'bg-purple-100 text-purple-700']; ?>
                        <span class="badge <?= $typeBadge[$c['type']] ?? 'bg-slate-100 text-slate-600' ?>"><?= ucfirst($c['type']) ?></span>
                    </td>
                    <td class="px-5 py-3 text-slate-600"><?= $c['credits'] ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= e($c['department_name'] ?? '—') ?></td>
                    <td class="px-5 py-3">
                        <div class="flex gap-2">
                            <a href="?action=edit&id=<?= $c['id'] ?>" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <button onclick="confirmDelete(<?= $c['id'] ?>, '<?= e($c['name']) ?>')"
                                    class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($courses)): ?>
                <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">No courses found. <a href="?action=new" class="text-indigo-600 hover:underline">Create one</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl mx-4">
        <div class="text-center mb-4">
            <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fa-solid fa-triangle-exclamation text-red-500 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-800">Delete Course?</h3>
            <p class="text-sm text-slate-500 mt-1">This will permanently delete <strong id="deleteCourseName"></strong>.</p>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/admin/courses.php" id="deleteForm">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="course_id" id="deleteCourseId">
            <div class="flex gap-3">
                <button type="button" onclick="closeModal()" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 py-2.5 rounded-lg text-sm font-semibold transition-colors">Cancel</button>
                <button type="submit" class="flex-1 bg-red-500 hover:bg-red-600 text-white py-2.5 rounded-lg text-sm font-semibold transition-colors">Delete</button>
            </div>
        </form>
    </div>
</div>
<script>
function confirmDelete(id, name) {
    document.getElementById('deleteCourseId').value = id;
    document.getElementById('deleteCourseName').textContent = name;
    document.getElementById('deleteModal').classList.remove('hidden');
}
function closeModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
</script>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
