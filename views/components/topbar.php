<?php
// views/components/topbar.php
// Variables: $pageTitle (string)

require_once ROOT_PATH . '/models/NotificationModel.php';
$notifModel = new NotificationModel();
$unread = isLoggedIn() ? $notifModel->countUnread(currentUserId()) : 0;
?>
<!-- Topbar -->
<header class="fixed top-0 left-0 right-0 md:left-64 h-16 bg-white border-b border-slate-200 flex items-center px-3 md:px-6 z-30 shadow-sm">
    <!-- Mobile Menu Toggle -->
    <button id="sidebarToggle" class="md:hidden p-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors mr-2">
        <i class="fa-solid fa-bars text-lg"></i>
    </button>
    <div class="flex-1">
        <h1 class="text-lg font-semibold text-slate-800"><?= e($pageTitle ?? '') ?></h1>
    </div>
    <div class="flex items-center gap-4">
        <!-- Notifications Bell -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="relative p-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                <i class="fa-solid fa-bell text-lg"></i>
                <?php if ($unread > 0): ?>
                <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold"><?= $unread > 9 ? '9+' : $unread ?></span>
                <?php endif; ?>
            </button>
            <div x-show="open" x-cloak @click.away="open = false"
                 class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-slate-200 z-50">
                <div class="px-4 py-3 border-b flex items-center justify-between">
                    <span class="font-semibold text-sm">Notifications</span>
                    <?php if ($unread > 0): ?>
                    <a href="<?= BASE_URL ?>/<?= currentRole() ?>/notifications.php?mark_all=1"
                       class="text-xs text-indigo-600 hover:underline">Mark all read</a>
                    <?php endif; ?>
                </div>
                <?php
                $notifs = $notifModel->getForUser(currentUserId());
                $recent = array_slice($notifs, 0, 5);
                ?>
                <div class="max-h-64 overflow-y-auto divide-y divide-slate-100">
                    <?php if (empty($recent)): ?>
                    <div class="px-4 py-6 text-center text-slate-400 text-sm">No notifications</div>
                    <?php else: ?>
                    <?php foreach ($recent as $n): ?>
                    <a href="<?= BASE_URL ?>/<?= currentRole() ?>/notifications.php?id=<?= $n['id'] ?>"
                       class="flex gap-3 px-4 py-3 hover:bg-slate-50 <?= $n['is_read'] ? '' : 'bg-indigo-50/40' ?>">
                        <?php
                        $iconMap = ['success'=>'fa-circle-check text-green-500','warning'=>'fa-triangle-exclamation text-yellow-500','danger'=>'fa-circle-xmark text-red-500','info'=>'fa-circle-info text-blue-500'];
                        $icon = $iconMap[$n['type']] ?? 'fa-circle-info text-blue-500';
                        ?>
                        <i class="fa-solid <?= $icon ?> mt-0.5 flex-shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate"><?= e($n['title']) ?></p>
                            <p class="text-xs text-slate-400 truncate"><?= e($n['message']) ?></p>
                            <p class="text-xs text-slate-300 mt-0.5"><?= formatDate($n['created_at'], 'd M, H:i') ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="px-4 py-2 border-t text-center">
                    <a href="<?= BASE_URL ?>/<?= currentRole() ?>/notifications.php" class="text-xs text-indigo-600 hover:underline">View all</a>
                </div>
            </div>
        </div>

        <!-- Profile -->
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-700 font-bold text-sm">
                <?= strtoupper(substr(currentUserName(), 0, 2)) ?>
            </div>
            <span class="text-sm font-medium hidden sm:block"><?= e(currentUserName()) ?></span>
        </div>
    </div>
</header>
