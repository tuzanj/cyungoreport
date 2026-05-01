<?php
// admin/reports.php - Generate student reports in PDF/Excel

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_ADMIN);

require_once ROOT_PATH . '/controllers/AdminController.php';
require_once ROOT_PATH . '/models/StudentModel.php';

$adminCtrl = new AdminController();
$studentModel = new StudentModel();

$action = $_GET['action'] ?? 'select';
$format = $_GET['format'] ?? 'pdf'; // pdf or excel

if ($action === 'generate' && isset($_GET['student_id']) && isset($_GET['academic_year_id'])) {
    try {
        $studentId = (int)$_GET['student_id'];
        $academicYearId = (int)$_GET['academic_year_id'];
        
        // Verify student exists
        $student = $studentModel->findById($studentId);
        if (!$student) {
            http_response_code(404);
            die('Student not found');
        }
        
        $adminCtrl->generateStudentReportPDF($studentId, $academicYearId, $format);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        die('Error generating report: ' . $e->getMessage());
    }
}

// Get all academic years
$db = Database::getInstance();
$academicYears = $db->fetchAll(
    "SELECT * FROM academic_years ORDER BY start_date DESC"
);

$selectedYear = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 
                ($db->fetchOne("SELECT id FROM academic_years WHERE is_current = 1")['id'] ?? null);

$students = [];
if ($selectedYear) {
    $students = $db->fetchAll(
        "SELECT DISTINCT s.id, s.student_id, s.first_name, s.last_name, c.name as class_name
         FROM students s
         LEFT JOIN enrollments e ON e.student_id = s.id AND e.academic_year_id = ?
         LEFT JOIN classes c ON c.id = e.class_id
         ORDER BY s.last_name, s.first_name",
        [$selectedYear]
    );
}

$pageTitle  = 'Generate Student Reports';
$activePage = '/admin/reports.php';
$role = 'admin';
include ROOT_PATH . '/views/components/layout.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Generate Student Reports</h2>
        <p class="text-sm text-slate-500 mt-0.5">Export learner assessment reports in PDF or Excel format</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <label class="block text-sm font-semibold mb-2">Academic Year</label>
            <select id="academicYearSelect" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" onchange="updateStudents()">
                <option value="">-- Select Academic Year --</option>
                <?php foreach ($academicYears as $year): ?>
                    <option value="<?= $year['id'] ?>" <?= $selectedYear == $year['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($year['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-2">Export Format</label>
            <select id="formatSelect" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="pdf">PDF Document</option>
                <option value="excel">Excel Spreadsheet</option>
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-semibold mb-2">Select Student</label>
        <select id="studentSelect" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">-- Select Student --</option>
            <?php foreach ($students as $student): ?>
                <option value="<?= $student['id'] ?>">
                    <?= htmlspecialchars($student['student_id'] . ' - ' . $student['first_name'] . ' ' . $student['last_name'] . ' (' . ($student['class_name'] ?? 'N/A') . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mt-6 flex gap-3">
        <button onclick="generateReport()" class="flex-1 bg-indigo-600 text-white px-4 py-2.5 rounded-lg hover:bg-indigo-700 font-semibold transition-colors">
            <i class="fa-solid fa-file-download"></i> Generate Report
        </button>
        <a href="/admin/dashboard.php" class="px-6 py-2.5 border border-slate-300 text-slate-600 rounded-lg hover:bg-slate-50 font-semibold transition-colors">
            Back to Dashboard
        </a>
    </div>
</div>

<script>
function updateStudents() {
    const yearId = document.getElementById('academicYearSelect').value;
    if (!yearId) {
        location.href = '/admin/reports.php';
        return;
    }
    location.href = '/admin/reports.php?academic_year_id=' + yearId;
}

function generateReport() {
    const studentId = document.getElementById('studentSelect').value;
    const yearId = document.getElementById('academicYearSelect').value;
    const format = document.getElementById('formatSelect').value;

    if (!studentId || !yearId) {
        alert('Please select both a student and academic year');
        return;
    }

    // Trigger download
    window.location.href = '/admin/reports.php?action=generate&student_id=' + studentId + '&academic_year_id=' + yearId + '&format=' + format;
}

// Allow Enter key to generate report
document.getElementById('studentSelect').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') generateReport();
});
</script>

<?php include ROOT_PATH . '/views/components/footer.php'; ?>
