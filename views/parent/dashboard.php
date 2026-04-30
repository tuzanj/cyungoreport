<?php
// views/parent/dashboard.php
$pageTitle  = 'Parent Dashboard';
$activePage = '/parent/dashboard.php';
$role = 'parent';
include ROOT_PATH . '/views/components/layout.php';

$parent   = $data['parent'] ?? [];
$children = $data['children'] ?? [];
$year     = $data['year'] ?? null;
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-slate-800">Welcome, <?= e(($parent['first_name'] ?? '') . ' ' . ($parent['last_name'] ?? '')) ?></h2>
    <p class="text-sm text-slate-500 mt-0.5">
        <?= count($children) ?> child<?= count($children) !== 1 ? 'ren' : '' ?> linked to your account
        <?php if ($year): ?> · <?= e($year['name']) ?><?php endif; ?>
    </p>
</div>

<?php if (empty($children)): ?>
<div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm border border-slate-100">
    <i class="fa-solid fa-child-reaching text-4xl text-slate-200 mb-3"></i>
    <p>No children linked. Contact the school secretary.</p>
</div>
<?php endif; ?>

<?php foreach ($children as $child): ?>
<?php $info = $child['info']; $marks = $child['marks']; $gpa = $child['gpa']; $att = $child['attendance']; ?>
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-5">
    <!-- Child Header -->
    <div class="bg-gradient-to-r from-green-600 to-teal-600 px-6 py-5 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center text-xl font-bold">
                    <?= strtoupper(substr($info['first_name'],0,1).substr($info['last_name'],0,1)) ?>
                </div>
                <div>
                    <div class="text-xl font-bold"><?= e($info['first_name'].' '.$info['last_name']) ?></div>
                    <div class="text-green-200 text-sm">
                        <span class="font-mono"><?= e($info['student_id']) ?></span>
                        <?php if ($info['class_name']): ?> · <?= e($info['class_name']) ?><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="text-right hidden sm:block">
                <div class="text-3xl font-bold"><?= number_format($gpa, 2) ?></div>
                <div class="text-green-200 text-sm">GPA</div>
            </div>
        </div>
    </div>

    <div class="p-5">
        <!-- Attendance Mini Stats -->
        <div class="grid grid-cols-4 gap-3 mb-5">
            <?php
            $attCards = [
                ['label'=>'Total Classes','value'=>$att['total'] ?? 0,  'color'=>'bg-slate-50 text-slate-700'],
                ['label'=>'Present',       'value'=>$att['present'] ?? 0,'color'=>'bg-green-50 text-green-700'],
                ['label'=>'Absent',        'value'=>$att['absent'] ?? 0, 'color'=>'bg-red-50 text-red-700'],
                ['label'=>'Attendance %',  'value'=> ($att['total'] ?? 0) > 0 ? round((($att['present'] ?? 0) / $att['total']) * 100).'%' : '—','color'=>'bg-blue-50 text-blue-700'],
            ];
            foreach ($attCards as $ac):
            ?>
            <div class="<?= $ac['color'] ?> rounded-xl p-3 text-center">
                <div class="text-xl font-bold"><?= $ac['value'] ?></div>
                <div class="text-xs mt-0.5 opacity-70"><?= $ac['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Marks Table -->
        <?php if (!empty($marks)): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-2 text-left">Course</th>
                        <th class="px-4 py-2 text-center">Grade</th>
                        <th class="px-4 py-2 text-center">Letter</th>
                        <th class="px-4 py-2 text-center">Result</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($marks as $m): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2 font-medium"><?= e($m['course_name']) ?></td>
                        <td class="px-4 py-2 text-center font-bold <?= $m['is_pass'] ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $m['calculated_grade'] !== null ? number_format((float)$m['calculated_grade'],1) : '—' ?>
                        </td>
                        <td class="px-4 py-2 text-center">
                            <span class="badge <?= $m['is_pass'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= e($m['letter_grade'] ?? '—') ?>
                            </span>
                        </td>
                        <td class="px-4 py-2 text-center">
                            <span class="badge <?= $m['is_pass'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= $m['is_pass'] ? 'PASS' : 'FAIL' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-sm text-slate-400 text-center py-4">No published results yet.</p>
        <?php endif; ?>

        <!-- Message Teacher Link -->
        <div class="mt-4 flex justify-end">
            <a href="<?= BASE_URL ?>/parent/messages.php?student=<?= $info['id'] ?>"
               class="inline-flex items-center gap-2 text-sm text-green-700 bg-green-50 hover:bg-green-100 px-4 py-2 rounded-lg transition-colors font-medium">
                <i class="fa-solid fa-envelope"></i>Message Teacher
            </a>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
