<?php
// views/teacher/attendance.php
$pageTitle  = 'Attendance';
$activePage = '/teacher/attendance.php';
$role = 'teacher';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-slate-800">Record Attendance</h2>
    <p class="text-sm text-slate-500 mt-0.5">Mark student attendance for a course and date</p>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-5">
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Course</label>
            <select name="cc" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Select course...</option>
                <?php foreach ($courses ?? [] as $c): ?>
                <option value="<?= $c['class_course_id'] ?>" <?= ($selectedCcId == $c['class_course_id']) ? 'selected' : '' ?>>
                    <?= e($c['course_name'].' — '.$c['class_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Date</label>
            <input type="date" name="date" value="<?= e($selectedDate ?? date('Y-m-d')) ?>"
                   max="<?= date('Y-m-d') ?>"
                   class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors">
                <i class="fa-solid fa-magnifying-glass mr-1"></i>Load Students
            </button>
        </div>
    </form>
</div>

<?php if ($selectedCcId && !empty($students)): ?>
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-slate-800">
            Attendance — <?= e($courseName ?? '') ?> — <?= formatDate($selectedDate) ?>
        </h3>
    </div>
    <form method="POST" action="<?= BASE_URL ?>/teacher/attendance.php">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="class_course_id" value="<?= $selectedCcId ?>">
        <input type="hidden" name="date" value="<?= e($selectedDate) ?>">

        <!-- Mark All Row -->
        <div class="px-5 py-3 bg-blue-50 border-b border-slate-100 flex items-center gap-4">
            <span class="text-sm font-medium text-slate-600">Mark all as:</span>
            <?php foreach (['present','absent','late','excused'] as $s): ?>
            <button type="button" onclick="markAll('<?= $s ?>')"
                    class="text-xs px-3 py-1 rounded-full font-semibold transition-colors
                    <?= $s==='present' ? 'bg-green-100 text-green-700 hover:bg-green-200' : ($s==='absent' ? 'bg-red-100 text-red-700 hover:bg-red-200' : ($s==='late' ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200')) ?>">
                <?= ucfirst($s) ?>
            </button>
            <?php endforeach; ?>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3 text-left">Student</th>
                        <th class="px-5 py-3 text-center">Present</th>
                        <th class="px-5 py-3 text-center">Absent</th>
                        <th class="px-5 py-3 text-center">Late</th>
                        <th class="px-5 py-3 text-center">Excused</th>
                        <th class="px-5 py-3 text-left">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50" id="attendanceTable">
                    <?php foreach ($students as $s): ?>
                    <?php $existing = $existingMap[$s['id']] ?? null; ?>
                    <tr class="hover:bg-slate-50 attendance-row" data-status="<?= $existing['status'] ?? 'present' ?>">
                        <td class="px-5 py-3">
                            <div class="font-medium"><?= e($s['first_name'].' '.$s['last_name']) ?></div>
                            <div class="text-xs text-slate-400"><?= e($s['student_id']) ?></div>
                        </td>
                        <?php foreach (['present','absent','late','excused'] as $st): ?>
                        <td class="px-5 py-3 text-center">
                            <input type="radio" name="attendance[<?= $s['id'] ?>][status]"
                                   value="<?= $st ?>"
                                   class="status-radio w-4 h-4 text-blue-600 cursor-pointer"
                                   <?= (($existing['status'] ?? 'present') === $st) ? 'checked' : '' ?>>
                        </td>
                        <?php endforeach; ?>
                        <td class="px-5 py-3">
                            <input type="text" name="attendance[<?= $s['id'] ?>][remarks]"
                                   value="<?= e($existing['remarks'] ?? '') ?>"
                                   class="w-full px-2 py-1 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-blue-400"
                                   placeholder="Optional">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-slate-100 bg-slate-50 flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                <i class="fa-solid fa-floppy-disk"></i> Save Attendance
            </button>
        </div>
    </form>
</div>
<?php elseif ($selectedCcId): ?>
<div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm border border-slate-100">
    <p>No students enrolled in this course.</p>
</div>
<?php else: ?>
<div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm border border-slate-100">
    <i class="fa-solid fa-calendar-check text-4xl mb-3 text-slate-200"></i>
    <p>Select a course and date to record attendance.</p>
</div>
<?php endif; ?>

<script>
function markAll(status) {
    document.querySelectorAll('.attendance-row').forEach(row => {
        const radio = row.querySelector(`input[value="${status}"]`);
        if (radio) radio.checked = true;
    });
}
</script>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
