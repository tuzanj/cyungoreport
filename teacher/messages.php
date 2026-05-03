<?php
// teacher/messages.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_TEACHER);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.');
        redirect('/teacher/messages.php');
    }

    if (($_POST['form_action'] ?? '') === 'mark_read') {
        $db->execute(
            "UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?",
            [(int)($_POST['message_id'] ?? 0), currentUserId()]
        );
        redirect('/teacher/messages.php');
    }
}

$messages = $db->fetchAll(
    "SELECT m.*, u.username as sender_username,
            COALESCE(p.first_name, s.first_name, u.username) as sender_first_name,
            COALESCE(p.last_name, s.last_name, '') as sender_last_name
     FROM messages m
     JOIN users u ON u.id = m.sender_id
     LEFT JOIN parents p ON p.user_id = u.id
     LEFT JOIN students s ON s.user_id = u.id
     WHERE m.receiver_id = ?
     ORDER BY m.created_at DESC",
    [currentUserId()]
);

$pageTitle = 'Messages';
$activePage = '/teacher/messages.php';
$role = 'teacher';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-slate-800">Messages</h2>
    <p class="text-sm text-slate-500 mt-0.5">Messages sent to you by parents</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="divide-y divide-slate-50">
        <?php foreach ($messages as $msg): ?>
        <div class="px-5 py-4 hover:bg-slate-50 <?= !$msg['is_read'] ? 'bg-blue-50/40' : '' ?>">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-sm text-slate-800"><?= e(trim($msg['sender_first_name'] . ' ' . $msg['sender_last_name']) ?: $msg['sender_username']) ?></span>
                        <?php if (!$msg['is_read']): ?>
                        <span class="badge bg-blue-100 text-blue-700 text-xs">New</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-sm text-slate-700 font-medium mt-1"><?= e($msg['subject'] ?? '(No subject)') ?></div>
                    <div class="text-sm text-slate-500 mt-2 whitespace-pre-line"><?= e($msg['body']) ?></div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-slate-400 whitespace-nowrap mb-2"><?= formatDate($msg['created_at'], 'd M, H:i') ?></div>
                    <?php if (!$msg['is_read']): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="form_action" value="mark_read">
                        <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                        <button type="submit" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg">Mark read</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($messages)): ?>
        <div class="px-5 py-10 text-center text-slate-400 text-sm">No messages yet.</div>
        <?php endif; ?>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
