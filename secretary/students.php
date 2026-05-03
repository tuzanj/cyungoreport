<?php
// secretary/students.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_SECRETARY);

require_once ROOT_PATH . '/models/StudentModel.php';
require_once ROOT_PATH . '/classes/ExcelStudentImporter.php';
require_once ROOT_PATH . '/classes/StudentBulkManager.php';

$studentModel = new StudentModel();
$action       = $_GET['action'] ?? 'list';

if ($action === 'download_template') {
    StudentBulkManager::downloadTemplate();
}

if ($action === 'download_class') {
    StudentBulkManager::downloadClassList((int)($_GET['class_id'] ?? 0));
}

if ($action === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.');
        redirect('/secretary/students.php?action=import');
    }
    $result = StudentBulkManager::handleImportUpload();
    setFlash($result['success'] ? 'success' : 'danger', $result['success'] ? "Successfully imported {$result['imported']} students. {$result['failed']} failed." . (!empty($result['errors']) ? " Errors: " . implode("; ", array_slice($result['errors'], 0, 3)) : '') : $result['error']);
    redirect('/secretary/students.php?action=import');
}

$students     = $studentModel->getAllWithDetails();
$classes      = StudentBulkManager::getClasses();
$search       = trim($_GET['q'] ?? '');

if ($search) {
    $students = array_filter($students, fn($s) =>
        stripos($s['student_id'], $search) !== false ||
        stripos($s['first_name'].' '.$s['last_name'], $search) !== false ||
        stripos($s['email'], $search) !== false
    );
}

$pageTitle  = 'All Students';
$activePage = '/secretary/students.php';
$role = 'secretary';
include ROOT_PATH . '/views/components/layout.php';
?>

<?php if ($action === 'import'): ?>
<div class="mb-6">
    <h2 class="text-xl font-bold text-slate-800">Bulk Import Students</h2>
    <p class="text-sm text-slate-500 mt-0.5">Download the template, fill it, then upload many students at once.</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 max-w-3xl">
    <div class="bg-blue-50 text-blue-700 rounded-xl p-4 text-sm mb-5">
        Required columns: first_name, last_name, gender, date_of_birth, phone, address, emergency_contact, class_id
    </div>
    <div class="mb-5">
        <a href="<?= BASE_URL ?>/secretary/students.php?action=download_template"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold">
            <i class="fa-solid fa-download"></i> Download CSV Template
        </a>
    </div>
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Choose CSV File *</label>
            <input type="file" name="csv_file" accept=".csv" required
                   class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold">
                <i class="fa-solid fa-upload"></i> Import Students
            </button>
            <a href="<?= BASE_URL ?>/secretary/students.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold">Back</a>
        </div>
    </form>
</div>
<?php include ROOT_PATH . '/views/components/footer.php'; exit; ?>
<?php endif; ?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">All Students</h2>
        <p class="text-sm text-slate-500 mt-0.5"><?= count($students) ?> students registered</p>
    </div>
    <div class="flex gap-2">
        <form method="GET" action="" class="flex gap-2">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search students…"
                   class="px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 w-52">
            <button type="submit" class="bg-teal-600 text-white px-4 py-2.5 rounded-lg text-sm font-semibold">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>
        <a href="<?= BASE_URL ?>/secretary/register_student.php"
           class="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
            <i class="fa-solid fa-user-plus"></i> Register New
        </a>
        <a href="<?= BASE_URL ?>/secretary/students.php?action=import"
           class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
            <i class="fa-solid fa-file-upload"></i> Bulk Import
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-5">
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
        <input type="hidden" name="action" value="download_class">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Download students by class</label>
            <select name="class_id" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm">
                <option value="0">All classes</option>
                <?php foreach ($classes as $class): ?>
                <option value="<?= (int)$class['id'] ?>"><?= e($class['name'] . ' - ' . ($class['year_name'] ?? '')) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold">
            <i class="fa-solid fa-file-csv"></i> Download CSV
        </button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Student ID</th>
                    <th class="px-5 py-3 text-left">Name</th>
                    <th class="px-5 py-3 text-left">Email</th>
                    <th class="px-5 py-3 text-left">Class</th>
                    <th class="px-5 py-3 text-center">Status</th>
                    <th class="px-5 py-3 text-left">Enrolled</th>
                    <th class="px-5 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($students as $s): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-mono font-semibold text-teal-700"><?= e($s['student_id']) ?></td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center text-teal-700 font-bold text-xs">
                                <?= strtoupper(substr($s['first_name'],0,1).substr($s['last_name'],0,1)) ?>
                            </div>
                            <span class="font-medium"><?= e($s['first_name'].' '.$s['last_name']) ?></span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-slate-500"><?= e($s['email']) ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= e($s['class_name'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-center">
                        <?php $statusColor = ['active'=>'bg-green-100 text-green-700','suspended'=>'bg-orange-100 text-orange-700','graduated'=>'bg-blue-100 text-blue-700','withdrawn'=>'bg-red-100 text-red-700']; ?>
                        <span class="badge <?= $statusColor[$s['status']] ?? 'bg-slate-100 text-slate-600' ?>"><?= ucfirst($s['status']) ?></span>
                    </td>
                    <td class="px-5 py-3 text-slate-400"><?= $s['enrollment_date'] ? formatDate($s['enrollment_date']) : '—' ?></td>
                    <td class="px-5 py-3">
                        <a href="<?= BASE_URL ?>/secretary/link_parent.php?student_id=<?= $s['id'] ?>"
                           class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Link Parent">
                            <i class="fa-solid fa-link"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($students)): ?>
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">No students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
