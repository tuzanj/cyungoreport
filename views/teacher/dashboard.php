<?php
// views/teacher/dashboard.php
$pageTitle  = 'Teacher Dashboard';
$activePage = '/teacher/dashboard.php';
$role = 'teacher';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 card-hover">
        <div class="w-11 h-11 bg-blue-500 rounded-xl flex items-center justify-center mb-4">
            <i class="fa-solid fa-book-open text-white text-lg"></i>
        </div>
        <div class="text-3xl font-bold text-slate-800"><?= count($data['courses'] ?? []) ?></div>
        <div class="text-sm text-slate-500 mt-1">Assigned Courses</div>
    </div>
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 card-hover">
        <div class="w-11 h-11 bg-indigo-500 rounded-xl flex items-center justify-center mb-4">
            <i class="fa-solid fa-users text-white text-lg"></i>
        </div>
        <div class="text-3xl font-bold text-slate-800"><?= $data['total_students'] ?? 0 ?></div>
        <div class="text-sm text-slate-500 mt-1">Total Students</div>
    </div>
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 card-hover">
        <div class="w-11 h-11 bg-yellow-500 rounded-xl flex items-center justify-center mb-4">
            <i class="fa-solid fa-flag text-white text-lg"></i>
        </div>
        <div class="text-3xl font-bold text-slate-800"><?= $data['total_claims'] ?? 0 ?></div>
        <div class="text-sm text-slate-500 mt-1">Pending Claims</div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
    <!-- My Courses -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">My Courses</h3>
            <a href="<?= BASE_URL ?>/teacher/courses.php" class="text-xs text-blue-600 hover:underline">View all</a>
        </div>
        <div class="divide-y divide-slate-50">
            <?php foreach ($data['courses'] ?? [] as $c): ?>
            <div class="px-5 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-book text-blue-600 text-sm"></i>
                    </div>
                    <div>
                        <div class="font-medium text-sm"><?= e($c['course_name']) ?></div>
                        <div class="text-xs text-slate-400"><?= e($c['class_name']) ?> · <?= e($c['code']) ?></div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="badge bg-blue-50 text-blue-700"><?= $c['student_count'] ?> students</span>
                    <a href="<?= BASE_URL ?>/teacher/marks.php?cc=<?= $c['class_course_id'] ?>"
                       class="text-xs text-indigo-600 hover:underline">Marks</a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($data['courses'])): ?>
            <div class="px-5 py-10 text-center text-slate-400 text-sm">No courses assigned.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pending Grade Claims -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Pending Grade Claims</h3>
            <a href="<?= BASE_URL ?>/teacher/claims.php" class="text-xs text-yellow-600 hover:underline">View all</a>
        </div>
        <div class="divide-y divide-slate-50">
            <?php foreach (array_slice($data['pending_claims'] ?? [], 0, 5) as $claim): ?>
            <div class="px-5 py-4 hover:bg-slate-50">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="font-medium text-sm"><?= e($claim['first_name'].' '.$claim['last_name']) ?></div>
                        <div class="text-xs text-slate-400"><?= e($claim['course_name']) ?></div>
                        <div class="text-xs text-slate-500 mt-1 line-clamp-2"><?= e($claim['reason']) ?></div>
                    </div>
                    <a href="<?= BASE_URL ?>/teacher/claims.php?id=<?= $claim['id'] ?>"
                       class="ml-3 text-xs bg-yellow-50 text-yellow-700 px-2.5 py-1 rounded-lg hover:bg-yellow-100 transition-colors whitespace-nowrap">
                        Review
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($data['pending_claims'])): ?>
            <div class="px-5 py-10 text-center text-slate-400 text-sm">No pending claims.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
