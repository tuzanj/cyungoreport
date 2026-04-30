<?php
// teacher/claims.php

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

$claims = $db->fetchAll(
    "SELECT gc.*, s.first_name, s.last_name, s.student_id as student_code,
            c.name as course_name, m.calculated_grade, m.letter_grade
     FROM grade_claims gc
     JOIN students s ON s.id=gc.student_id
     JOIN marks m ON m.id=gc.mark_id
     JOIN class_courses cc ON cc.id=m.class_course_id
     JOIN courses c ON c.id=cc.course_id
     WHERE cc.teacher_id=?
     ORDER BY gc.created_at DESC",
    [$teacher['id']]
);

$pageTitle  = 'Grade Claims';
$activePage = '/teacher/claims.php';
$role = 'teacher';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-slate-800">Grade Claims</h2>
    <p class="text-sm text-slate-500 mt-0.5">Student grade appeal requests for your courses</p>
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
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($claims as $cl): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3">
                        <div class="font-medium"><?= e($cl['first_name'].' '.$cl['last_name']) ?></div>
                        <div class="text-xs font-mono text-slate-400"><?= e($cl['student_code']) ?></div>
                    </td>
                    <td class="px-5 py-3 font-medium"><?= e($cl['course_name']) ?></td>
                    <td class="px-5 py-3 text-center font-bold">
                        <?= number_format((float)$cl['calculated_grade'],1) ?>
                        <span class="badge bg-red-100 text-red-700 ml-1"><?= e($cl['letter_grade'] ?? '') ?></span>
                    </td>
                    <td class="px-5 py-3 max-w-xs">
                        <p class="text-slate-600 text-xs line-clamp-2"><?= e($cl['reason']) ?></p>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <?php
                        $statusColors = ['pending'=>'bg-yellow-100 text-yellow-700','under_review'=>'bg-blue-100 text-blue-700','resolved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700'];
                        ?>
                        <span class="badge <?= $statusColors[$cl['status']] ?? 'bg-slate-100' ?>">
                            <?= ucfirst(str_replace('_',' ',$cl['status'])) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-slate-400 whitespace-nowrap"><?= formatDate($cl['created_at'], 'd M Y') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($claims)): ?>
                <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">No grade claims.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
