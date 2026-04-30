<?php
// student/courses.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_STUDENT);

require_once ROOT_PATH . '/models/StudentModel.php';

$studentModel = new StudentModel();
$db           = Database::getInstance();
$student      = $studentModel->findByUserId(currentUserId());
if (!$student) redirect('/index.php');

$currentYear = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$yearId      = $currentYear ? (int)$currentYear['id'] : 0;
$courses     = $studentModel->getEnrolledCourses((int)$student['id'], $yearId);

$pageTitle  = 'My Courses';
$activePage = '/student/courses.php';
$role = 'student';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-slate-800">Enrolled Courses</h2>
    <p class="text-sm text-slate-500 mt-0.5"><?= $currentYear ? e($currentYear['name']) : '' ?> · <?= count($courses) ?> courses</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($courses as $c): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden card-hover">
        <div class="bg-gradient-to-r from-violet-600 to-indigo-600 p-5 text-white">
            <div class="text-xs font-mono opacity-70 mb-1"><?= e($c['code']) ?></div>
            <div class="text-lg font-bold"><?= e($c['course_name']) ?></div>
            <div class="text-violet-200 text-sm mt-1"><?= e($c['class_name']) ?></div>
        </div>
        <div class="p-4">
            <div class="flex items-center gap-2 text-sm text-slate-600 mb-2">
                <i class="fa-solid fa-chalkboard-user text-violet-400"></i>
                <span><?= e($c['teacher_first'].' '.$c['teacher_last']) ?></span>
            </div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <i class="fa-solid fa-star text-violet-400"></i>
                <span><?= $c['credits'] ?> Credit<?= $c['credits'] != 1 ? 's' : '' ?></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($courses)): ?>
    <div class="col-span-3 bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm border border-slate-100">
        <i class="fa-solid fa-book text-4xl text-slate-200 mb-3"></i>
        <p>Not enrolled in any courses.</p>
    </div>
    <?php endif; ?>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
