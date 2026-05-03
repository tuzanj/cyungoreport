<?php
// Handles student registration from admin panel
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/controllers/AdminController.php';
require_once ROOT_PATH . '/classes/StudentBulkManager.php';

$admin = new AdminController();

$action = $_GET['action'] ?? 'list';

if ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = $admin->createStudent([
            'username' => $_POST['username'] ?? '',
            'email' => $_POST['email'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'gender' => $_POST['gender'] ?? null,
            'date_of_birth' => $_POST['date_of_birth'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'address' => $_POST['address'] ?? null,
            'emergency_contact' => $_POST['emergency_contact'] ?? null,
            'class_id' => $_POST['class_id'] ?? null,
        ]);

        if ($result['success']) {
            setFlash('success', $result['message']);
            redirect('/admin/students.php');
        } else {
            setFlash('danger', $result['error']);
        }
    }

    $db = Database::getInstance();
    $classes = $db->fetchAll(
        "SELECT c.*, ay.name as year_name FROM classes c
         JOIN academic_years ay ON ay.id = c.academic_year_id
         WHERE ay.is_current = 1 ORDER BY c.name"
    );

    $page_title = 'Register Student';
    include ROOT_PATH . '/views/components/layout.php';
    ?>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <h2><i class="fas fa-user-plus"></i> Register New Student</h2>
                <hr>

                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-control" id="gender" name="gender">
                                <option value="">-- Select --</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="emergency_contact" class="form-label">Emergency Contact</label>
                            <input type="text" class="form-control" id="emergency_contact" name="emergency_contact">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="class_id" class="form-label">Assign to Class</label>
                            <select class="form-control" id="class_id" name="class_id">
                                <option value="">-- Select Class --</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>">
                                        <?php echo htmlspecialchars($class['name'] . ' - ' . $class['year_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Register Student</button>
                        <a href="/admin/students.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
} elseif ($action === 'import') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $result = StudentBulkManager::handleImportUpload();
        setFlash($result['success'] ? 'success' : 'danger', $result['success'] ? "Successfully imported {$result['imported']} students. {$result['failed']} failed." . (!empty($result['errors']) ? " Errors: " . implode("; ", array_slice($result['errors'], 0, 3)) : '') : $result['error']);
    }

    $page_title = 'Bulk Import Students';
    include ROOT_PATH . '/views/components/layout.php';
    ?>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <h2><i class="fas fa-file-upload"></i> Bulk Import Students</h2>
                <hr>

                <div class="alert alert-info" role="alert">
                    <strong>Instructions:</strong><br>
                    Use the template below to prepare your CSV file. Columns required: first_name, last_name, gender, date_of_birth, phone, address, emergency_contact, class_id
                </div>

                <div class="mb-4">
                    <h5>Download Template</h5>
                    <a href="?action=download_template" class="btn btn-sm btn-info">
                        <i class="fas fa-download"></i> Download CSV Template
                    </a>
                </div>

                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Choose CSV File *</label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                        <small class="text-muted">Supported format: CSV</small>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Import Students</button>
                        <a href="/admin/students.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
} elseif ($action === 'download_template') {
    StudentBulkManager::downloadTemplate();
} elseif ($action === 'download_class') {
    StudentBulkManager::downloadClassList((int)($_GET['class_id'] ?? 0));
} else {
    // List view - show all students
    $db = Database::getInstance();
    $classes = StudentBulkManager::getClasses();
    $students = $db->fetchAll(
        "SELECT s.*, u.username, u.email, u.is_active, c.name as class_name
         FROM students s
         JOIN users u ON u.id = s.user_id
         LEFT JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
         LEFT JOIN classes c ON c.id = e.class_id
         ORDER BY s.last_name, s.first_name"
    );

    $page_title = 'Manage Students';
    include ROOT_PATH . '/views/components/layout.php';
    ?>
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users"></i> Student Management</h2>
            <div class="btn-group">
                <a href="?action=register" class="btn btn-primary"><i class="fas fa-user-plus"></i> Register Student</a>
                <a href="?action=import" class="btn btn-success"><i class="fas fa-file-upload"></i> Bulk Import</a>
                <a href="?action=download_class" class="btn btn-info"><i class="fas fa-download"></i> Download All</a>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <input type="hidden" name="action" value="download_class">
                    <div class="col-md-6">
                        <label class="form-label">Download students by class</label>
                        <select name="class_id" class="form-control">
                            <option value="0">All classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= (int)$class['id'] ?>"><?= htmlspecialchars($class['name'] . ' - ' . ($class['year_name'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-info w-100"><i class="fas fa-file-csv"></i> Download CSV</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Class</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($student['student_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['class_name'] ?? '-'); ?></td>
                                    <td>
                                        <?php
                                        $status_badge = $student['is_active'] ? 'success' : 'danger';
                                        $status_text = $student['is_active'] ? 'Active' : 'Inactive';
                                        echo "<span class='badge bg-{$status_badge}'>{$status_text}</span>";
                                        ?>
                                    </td>
                                    <td>
                                        <a href="/student/profile.php?id=<?php echo $student['id']; ?>" 
                                           class="btn btn-sm btn-info" title="View Profile">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php
}
