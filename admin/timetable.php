<?php
// admin/timetable.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_ADMIN);

require_once ROOT_PATH . '/controllers/AdminController.php';
require_once ROOT_PATH . '/models/ClassModel.php';
require_once ROOT_PATH . '/models/CourseModel.php';
require_once ROOT_PATH . '/models/TeacherModel.php';

$adminCtrl    = new AdminController();
$classModel   = new ClassModel();
$courseModel  = new CourseModel();
$teacherModel = new TeacherModel();
$db           = Database::getInstance();

$currentYear   = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$currentYearId = $currentYear ? (int)$currentYear['id'] : 0;

$selectedClassId = (int)($_GET['class_id'] ?? 0);
$classes   = $classModel->getForYear($currentYearId);
$courses   = $courseModel->findAll('name ASC');
$teachers  = $teacherModel->getAllWithDept();
$timetable = [];
$classCourses = [];

if ($selectedClassId) {
    $timetable    = $classModel->getFullTimetable($selectedClassId, $currentYearId);
    $classCourses = $db->fetchAll(
        "SELECT cc.id, c.name as course_name, t.first_name, t.last_name
         FROM class_courses cc
         JOIN courses c ON c.id = cc.course_id
         JOIN teachers t ON t.id = cc.teacher_id
         WHERE cc.class_id=? AND cc.academic_year_id=?",
        [$selectedClassId, $currentYearId]
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.');
        redirect('/admin/timetable.php');
    }
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'assign_course') {
        $result = $adminCtrl->assignCourseToTeacher(
            (int)$_POST['class_id'],
            (int)$_POST['course_id'],
            (int)$_POST['teacher_id'],
            $currentYearId
        );
        setFlash($result['success'] ? 'success' : 'danger', $result['message']);
        redirect('/admin/timetable.php?class_id='.(int)$_POST['class_id']);
    }

    if ($formAction === 'add_schedule') {
        $result = $adminCtrl->addSchedule((int)$_POST['class_course_id'], [
            'day'        => $_POST['day_of_week'] ?? '',
            'start_time' => $_POST['start_time'] ?? '',
            'end_time'   => $_POST['end_time'] ?? '',
            'room'       => trim($_POST['room'] ?? ''),
        ]);
        setFlash($result['success'] ? 'success' : 'danger', $result['message']);
        redirect('/admin/timetable.php?class_id='.$selectedClassId);
    }

    if ($formAction === 'delete_schedule') {
        $schedId = (int)($_POST['schedule_id'] ?? 0);
        $db->execute("DELETE FROM schedules WHERE id=?", [$schedId]);
        setFlash('success', 'Schedule entry deleted.');
        redirect('/admin/timetable.php?class_id='.$selectedClassId);
    }
}

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$timetableByDay = [];
foreach ($timetable as $row) {
    $timetableByDay[$row['day_of_week']][] = $row;
}

$pageTitle  = 'Timetable';
$activePage = '/admin/timetable.php';
$role = 'admin';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Timetable / Schedule</h2>
        <p class="text-sm text-slate-500 mt-0.5">Assign courses and set weekly schedules per class</p>
    </div>
    <!-- Class selector -->
    <form method="GET" action="" class="flex gap-2">
        <select name="class_id" onchange="this.form.submit()"
                class="px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 min-w-[180px]">
            <option value="">Select a class…</option>
            <?php foreach ($classes as $cl): ?>
            <option value="<?= $cl['id'] ?>" <?= $selectedClassId == $cl['id'] ? 'selected' : '' ?>>
                <?= e($cl['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($selectedClassId): ?>
<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-5">
    <!-- Assign Course -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Assign Course to Teacher</h3>
        <form method="POST" action="?class_id=<?= $selectedClassId ?>">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="form_action" value="assign_course">
            <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Course</label>
                    <select name="course_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Teacher</label>
                    <select name="teacher_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= e($t['first_name'].' '.$t['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg text-sm font-semibold transition-colors">
                    <i class="fa-solid fa-link mr-1"></i>Assign
                </button>
            </div>
        </form>
    </div>

    <!-- Add Schedule Slot -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Add Schedule Slot</h3>
        <form method="POST" action="?class_id=<?= $selectedClassId ?>">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="form_action" value="add_schedule">
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Course Assignment</label>
                    <select name="class_course_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <?php foreach ($classCourses as $cc): ?>
                        <option value="<?= $cc['id'] ?>"><?= e($cc['course_name'].' ('.$cc['first_name'].' '.$cc['last_name'].')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Day</label>
                    <select name="day_of_week" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <?php foreach ($days as $d): ?>
                        <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Start</label>
                        <input type="time" name="start_time" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">End</label>
                        <input type="time" name="end_time" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Room</label>
                    <input type="text" name="room" placeholder="e.g. Room 201"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white py-2.5 rounded-lg text-sm font-semibold transition-colors">
                    <i class="fa-solid fa-plus mr-1"></i>Add Slot
                </button>
            </div>
        </form>
    </div>

    <!-- Assigned Courses List -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Assigned Courses</h3>
        <div class="space-y-2">
            <?php foreach ($classCourses as $cc): ?>
            <div class="flex items-center justify-between bg-slate-50 rounded-lg px-3 py-2.5">
                <div>
                    <div class="text-sm font-medium"><?= e($cc['course_name']) ?></div>
                    <div class="text-xs text-slate-400"><?= e($cc['first_name'].' '.$cc['last_name']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($classCourses)): ?>
            <p class="text-sm text-slate-400 text-center py-4">No courses assigned yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Timetable Grid -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-slate-800">Weekly Timetable</h3>
    </div>
    <?php if (empty($timetable)): ?>
    <div class="px-5 py-10 text-center text-slate-400 text-sm">No schedule slots added yet.</div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-0 divide-x divide-slate-100">
        <?php foreach ($days as $day): ?>
        <?php if (empty($timetableByDay[$day])) continue; ?>
        <div>
            <div class="bg-indigo-600 text-white text-xs font-semibold px-3 py-2 text-center"><?= $day ?></div>
            <div class="divide-y divide-slate-50">
                <?php foreach ($timetableByDay[$day] as $slot): ?>
                <div class="px-3 py-3 hover:bg-slate-50">
                    <div class="text-xs font-semibold text-indigo-600"><?= substr($slot['start_time'],0,5).' – '.substr($slot['end_time'],0,5) ?></div>
                    <div class="text-sm font-medium mt-0.5"><?= e($slot['course_name']) ?></div>
                    <div class="text-xs text-slate-400"><?= e($slot['teacher_first'].' '.$slot['teacher_last']) ?></div>
                    <?php if ($slot['room']): ?>
                    <div class="text-xs text-slate-400"><i class="fa-solid fa-door-open mr-1"></i><?= e($slot['room']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm border border-slate-100">
    <i class="fa-solid fa-calendar-days text-4xl text-slate-200 mb-3"></i>
    <p>Select a class to view or manage its timetable.</p>
</div>
<?php endif; ?>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
