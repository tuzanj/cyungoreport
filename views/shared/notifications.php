<?php
// views/shared/notifications.php
// Variables: $notifications (array), $role (string)
$pageTitle  = 'Notifications';
$activePage = '/' . $role . '/notifications.php';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Notifications</h2>
        <p class="text-sm text-slate-500 mt-0.5"><?= count(array_filter($notifications ?? [], fn($n) => !$n['is_read'])) ?> unread</p>
    </div>
    <?php if (!empty($notifications)): ?>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="form_action" value="mark_all_read">
        <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
            <i class="fa-solid fa-check-double mr-1"></i>Mark all read
        </button>
    </form>
    <?php endif; ?>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <?php if (empty($notifications)): ?>
    <div class="px-5 py-16 text-center text-slate-400">
        <i class="fa-solid fa-bell-slash text-4xl text-slate-200 mb-3"></i>
        <p>You have no notifications.</p>
    </div>
    <?php else: ?>
    <div class="divide-y divide-slate-50">
        <?php
        $iconMap = [
            'success' => ['icon'=>'fa-circle-check','bg'=>'bg-green-100','text'=>'text-green-600'],
            'warning' => ['icon'=>'fa-triangle-exclamation','bg'=>'bg-yellow-100','text'=>'text-yellow-600'],
            'danger'  => ['icon'=>'fa-circle-xmark','bg'=>'bg-red-100','text'=>'text-red-600'],
            'info'    => ['icon'=>'fa-circle-info','bg'=>'bg-blue-100','text'=>'text-blue-600'],
        ];
        foreach ($notifications as $n):
            $style = $iconMap[$n['type']] ?? $iconMap['info'];
        ?>
        <div class="flex gap-4 px-5 py-4 hover:bg-slate-50 <?= $n['is_read'] ? '' : 'bg-indigo-50/30' ?>">
            <div class="w-9 h-9 <?= $style['bg'] ?> rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5">
                <i class="fa-solid <?= $style['icon'] ?> <?= $style['text'] ?> text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold text-sm text-slate-800"><?= e($n['title']) ?></p>
                        <p class="text-sm text-slate-500 mt-0.5"><?= e($n['message']) ?></p>
                    </div>
                    <?php if (!$n['is_read']): ?>
                    <form method="POST" action="" class="flex-shrink-0">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="form_action" value="mark_read">
                        <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                        <button type="submit" title="Mark as read"
                                class="w-2 h-2 bg-indigo-500 rounded-full hover:bg-indigo-700 transition-colors mt-2"></button>
                    </form>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    <i class="fa-regular fa-clock mr-1"></i><?= formatDate($n['created_at'], 'd M Y, H:i') ?>
                </p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
