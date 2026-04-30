<?php
// views/student/schedule.php
$pageTitle  = 'My Schedule';
$activePage = '/student/schedule.php';
$role = 'student';
include ROOT_PATH . '/views/components/layout.php';

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$scheduleByDay = [];
foreach ($schedule ?? [] as $s) {
    $scheduleByDay[$s['day_of_week']][] = $s;
}
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-slate-800">Weekly Schedule</h2>
    <p class="text-sm text-slate-500 mt-0.5">Your timetable for the current academic year</p>
</div>

<?php if (empty($schedule)): ?>
<div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm border border-slate-100">
    <i class="fa-solid fa-calendar-alt text-4xl text-slate-200 mb-3"></i>
    <p>No schedule available. Contact your secretary.</p>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <?php foreach ($days as $day): ?>
    <?php if (empty($scheduleByDay[$day])) continue; ?>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="bg-indigo-600 px-5 py-3">
            <h3 class="font-semibold text-white"><?= $day ?></h3>
        </div>
        <div class="divide-y divide-slate-50">
            <?php foreach ($scheduleByDay[$day] as $s): ?>
            <div class="px-5 py-4 flex items-start gap-3 hover:bg-slate-50">
                <div class="text-center min-w-[60px]">
                    <div class="text-xs font-semibold text-indigo-600"><?= substr($s['start_time'],0,5) ?></div>
                    <div class="text-xs text-slate-400"><?= substr($s['end_time'],0,5) ?></div>
                </div>
                <div class="flex-1">
                    <div class="font-medium text-sm"><?= e($s['course_name']) ?></div>
                    <div class="text-xs text-slate-400 mt-0.5"><?= e($s['code']) ?></div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        <i class="fa-solid fa-chalkboard-user mr-1"></i><?= e($s['teacher_first'].' '.$s['teacher_last']) ?>
                        <?php if ($s['room']): ?>
                        · <i class="fa-solid fa-door-open mr-1"></i><?= e($s['room']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
