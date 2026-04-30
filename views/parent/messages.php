<?php
// views/parent/messages.php
$pageTitle  = 'Messages';
$activePage = '/parent/messages.php';
$role = 'parent';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-slate-800">Messages</h2>
    <p class="text-sm text-slate-500 mt-0.5">Communicate with teachers</p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <!-- Compose -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
        <h3 class="font-semibold text-slate-800 mb-4">New Message</h3>
        <form method="POST" action="<?= BASE_URL ?>/parent/messages.php">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="form_action" value="send_message">
            <div class="mb-3">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">To (Teacher)</label>
                <select name="receiver_id" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Select teacher...</option>
                    <?php foreach ($teachers ?? [] as $t): ?>
                    <option value="<?= $t['user_id'] ?>"><?= e($t['first_name'].' '.$t['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Subject</label>
                <input type="text" name="subject"
                       class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                       placeholder="Message subject">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Message</label>
                <textarea name="body" rows="5" required
                          class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 resize-none"
                          placeholder="Write your message..."></textarea>
            </div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2.5 rounded-lg text-sm font-semibold transition-colors flex items-center justify-center gap-2">
                <i class="fa-solid fa-paper-plane"></i>Send Message
            </button>
        </form>
    </div>

    <!-- Inbox/Sent -->
    <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Conversation History</h3>
        </div>
        <div class="divide-y divide-slate-50">
            <?php foreach ($messages ?? [] as $msg): ?>
            <div class="px-5 py-4 hover:bg-slate-50 <?= !$msg['is_read'] && $msg['receiver_id'] == currentUserId() ? 'bg-green-50/40' : '' ?>">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-sm"><?= e($msg['sender_name']) ?></span>
                            <?php if (!$msg['is_read'] && $msg['receiver_id'] == currentUserId()): ?>
                            <span class="badge bg-green-100 text-green-700 text-xs">New</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-sm text-slate-600 font-medium mt-0.5"><?= e($msg['subject'] ?? '(No subject)') ?></div>
                        <div class="text-sm text-slate-500 mt-1 line-clamp-2"><?= e($msg['body']) ?></div>
                    </div>
                    <div class="text-xs text-slate-400 whitespace-nowrap ml-3"><?= formatDate($msg['created_at'], 'd M, H:i') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($messages)): ?>
            <div class="px-5 py-10 text-center text-slate-400 text-sm">No messages yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
