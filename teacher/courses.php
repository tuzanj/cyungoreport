<?php
// teacher/courses.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_TEACHER);

require_once ROOT_PATH . '/models/TeacherModel.php';

$teacherModel = new TeacherModel();
$db           = Database::getInstance();
$teacher      = $teacherModel->findByUserId(currentUserId());
if (!$teacher) redirect('/index.php');

$currentYear = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$yearId      = $currentYear ? (int)$currentYear['id'] : 0;
$courses     = $teacherModel->getAssignedCourses((int)$teacher['id'], $yearId);

$pageTitle  = 'My Courses';
$activePage = '/teacher/courses.php';
$role = 'teacher';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-slate-800">My Courses</h2>
    <p class="text-sm text-slate-500 mt-0.5"><?= $currentYear ? e($currentYear['name']) : '' ?> · <?= count($courses) ?> courses assigned</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($courses as $c): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden card-hover">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-5 text-white">
            <div class="text-xs font-mono opacity-70 mb-1"><?= e($c['code']) ?></div>
            <div class="text-lg font-bold"><?= e($c['course_name']) ?></div>
            <div class="text-blue-200 text-sm mt-1"><?= e($c['class_name']) ?> · <?= e($c['grade_level'] ?? '') ?></div>
        </div>
        <div class="p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <i class="fa-solid fa-users text-blue-400"></i>
                    <span><?= $c['student_count'] ?> students</span>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="<?= BASE_URL ?>/teacher/marks.php?cc=<?= $c['class_course_id'] ?>"
                   class="flex-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 py-2 rounded-lg text-xs font-semibold text-center transition-colors">
                    <i class="fa-solid fa-pen-to-square mr-1"></i>Marks
                </a>
                <a href="<?= BASE_URL ?>/teacher/attendance.php?cc=<?= $c['class_course_id'] ?>"
                   class="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-700 py-2 rounded-lg text-xs font-semibold text-center transition-colors">
                    <i class="fa-solid fa-calendar-check mr-1"></i>Attendance
                </a>
                <a href="<?= BASE_URL ?>/teacher/reports.php?cc=<?= $c['class_course_id'] ?>"
                   class="flex-1 bg-teal-50 hover:bg-teal-100 text-teal-700 py-2 rounded-lg text-xs font-semibold text-center transition-colors">
                    <i class="fa-solid fa-chart-bar mr-1"></i>Report
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($courses)): ?>
    <div class="col-span-3 bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm border border-slate-100">
        <i class="fa-solid fa-book-open text-4xl text-slate-200 mb-3"></i>
        <p>No courses assigned for this academic year.</p>
    </div>
    <?php endif; ?>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
