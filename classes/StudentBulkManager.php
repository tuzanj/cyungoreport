<?php

class StudentBulkManager {
    public static function handleImportUpload(string $fieldName = 'csv_file'): array {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Please choose a valid file to upload.'];
        }

        $file = $_FILES[$fieldName];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, ['csv'], true)) {
            return ['success' => false, 'error' => 'Invalid file format. Only CSV files are supported.'];
        }

        $uploadDir = ROOT_PATH . '/uploads/imports/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uploadPath = $uploadDir . 'students_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => false, 'error' => 'Failed to upload file.'];
        }

        try {
            $importer = new ExcelStudentImporter();
            return $importer->importFromCSV($uploadPath);
        } finally {
            if (file_exists($uploadPath)) {
                unlink($uploadPath);
            }
        }
    }

    public static function downloadTemplate(): never {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="student_import_template.csv"');
        echo ExcelStudentImporter::getTemplateCSV();
        exit;
    }

    public static function getClasses(): array {
        return Database::getInstance()->fetchAll(
            "SELECT c.id, c.name, c.grade_level, ay.name as year_name
             FROM classes c
             LEFT JOIN academic_years ay ON ay.id = c.academic_year_id
             ORDER BY ay.is_current DESC, c.name"
        );
    }

    public static function getStudentsByClass(?int $classId = null): array {
        $sql = "SELECT s.student_id, s.first_name, s.last_name, s.gender, s.date_of_birth, s.phone,
                       s.address, s.emergency_contact, s.status, s.enrollment_date,
                       u.username, u.email, c.name as class_name, ay.name as academic_year
                FROM students s
                JOIN users u ON u.id = s.user_id
                LEFT JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
                LEFT JOIN classes c ON c.id = e.class_id
                LEFT JOIN academic_years ay ON ay.id = e.academic_year_id";
        $params = [];
        if ($classId !== null && $classId > 0) {
            $sql .= " WHERE c.id = ?";
            $params[] = $classId;
        }
        $sql .= " ORDER BY c.name, s.last_name, s.first_name";
        return Database::getInstance()->fetchAll($sql, $params);
    }

    public static function downloadClassList(?int $classId = null): never {
        $students = self::getStudentsByClass($classId);
        $filename = $classId ? "class_{$classId}_students.csv" : 'all_students.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Student ID', 'First Name', 'Last Name', 'Gender', 'Date of Birth', 'Phone', 'Email', 'Username', 'Class', 'Academic Year', 'Status', 'Enrollment Date', 'Address', 'Emergency Contact']);
        foreach ($students as $s) {
            fputcsv($out, [
                $s['student_id'], $s['first_name'], $s['last_name'], $s['gender'], $s['date_of_birth'],
                $s['phone'], $s['email'], $s['username'], $s['class_name'], $s['academic_year'],
                $s['status'], $s['enrollment_date'], $s['address'], $s['emergency_contact']
            ]);
        }
        fclose($out);
        exit;
    }
}
