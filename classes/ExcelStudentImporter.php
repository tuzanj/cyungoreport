<?php
// classes/ExcelStudentImporter.php

/**
 * Excel Student Importer
 * Handles bulk student registration from Excel/CSV files
 */
class ExcelStudentImporter {
    private Database $db;
    private array $students = [];
    private array $errors = [];
    private int $successCount = 0;
    private int $errorCount = 0;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Import students from CSV file
     * Expected columns: first_name, last_name, gender, date_of_birth, phone, address, emergency_contact, class_id
     */
    public function importFromCSV(string $filePath): array {
        try {
            if (!file_exists($filePath)) {
                return ['success' => false, 'error' => 'File not found.'];
            }

            $handle = fopen($filePath, 'r');
            if (!$handle) {
                return ['success' => false, 'error' => 'Cannot open file.'];
            }

            // Read header
            $header = fgetcsv($handle);
            if (!$header) {
                fclose($handle);
                return ['success' => false, 'error' => 'Invalid file format.'];
            }

            $columnMap = $this->mapColumns($header);

            // Read data rows
            $rowNum = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (empty(array_filter($row))) continue; // Skip empty rows

                $studentData = $this->parseRow($row, $columnMap, $rowNum);
                if (isset($studentData['error'])) {
                    $this->errors[] = $studentData['error'];
                    $this->errorCount++;
                } else {
                    $this->students[] = $studentData;
                }
            }

            fclose($handle);

            // Create user and student accounts
            foreach ($this->students as $student) {
                if ($this->createStudent($student)) {
                    $this->successCount++;
                } else {
                    $this->errorCount++;
                }
            }

            return [
                'success' => true,
                'imported' => $this->successCount,
                'failed' => $this->errorCount,
                'errors' => $this->errors
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Map CSV header columns to expected fields
     */
    private function mapColumns(array $header): array {
        $mapping = [];
        $expectedColumns = [
            'first_name', 'last_name', 'gender', 'date_of_birth',
            'phone', 'address', 'emergency_contact', 'class_id'
        ];

        foreach ($header as $index => $column) {
            $normalized = strtolower(trim(str_replace(' ', '_', $column)));
            if (in_array($normalized, $expectedColumns)) {
                $mapping[$normalized] = $index;
            }
        }

        return $mapping;
    }

    /**
     * Parse a single CSV row
     */
    private function parseRow(array $row, array $columnMap, int $rowNum): array {
        $data = ['row_number' => $rowNum];

        foreach ($columnMap as $field => $index) {
            $value = trim($row[$index] ?? '');
            
            if ($field === 'first_name' || $field === 'last_name') {
                if (empty($value)) {
                    return ['error' => "Row {$rowNum}: {$field} is required."];
                }
            }
            
            if ($field === 'date_of_birth' && !empty($value)) {
                // Validate date format
                $dateObj = DateTime::createFromFormat('Y-m-d', $value);
                if (!$dateObj) {
                    $dateObj = DateTime::createFromFormat('m/d/Y', $value);
                    if (!$dateObj) {
                        return ['error' => "Row {$rowNum}: Invalid date format (use YYYY-MM-DD)."];
                    }
                    $value = $dateObj->format('Y-m-d');
                }
            }
            
            if ($field === 'gender' && !empty($value)) {
                $value = strtolower($value);
                if (!in_array($value, ['male', 'female', 'other'])) {
                    return ['error' => "Row {$rowNum}: Invalid gender (use male/female/other)."];
                }
            }
            
            if ($field === 'class_id' && !empty($value)) {
                // Verify class exists
                $class = $this->db->fetchOne("SELECT id FROM classes WHERE id = ?", [$value]);
                if (!$class) {
                    return ['error' => "Row {$rowNum}: Class ID {$value} does not exist."];
                }
            }

            $data[$field] = $value;
        }

        return $data;
    }

    /**
     * Create student account with user
     */
    private function createStudent(array $studentData): bool {
        try {
            // Generate student ID
            $studentId = $this->generateStudentId();

            // Generate username
            $username = $this->generateUsername($studentData['first_name'], $studentData['last_name']);

            // Create user account
            $hashedPassword = password_hash('Password123!', PASSWORD_BCRYPT);
            $userId = $this->db->insert(
                "INSERT INTO users (username, email, password_hash, role, is_active)
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $username,
                    str_replace(' ', '', strtolower($studentData['first_name'] . '.' . $studentData['last_name'])) . '@student.school',
                    $hashedPassword,
                    'student',
                    1
                ]
            );

            if (!$userId) {
                $this->errors[] = "Row {$studentData['row_number']}: Failed to create user account.";
                return false;
            }

            // Create student record
            $studentDbId = $this->db->insert(
                "INSERT INTO students (user_id, student_id, first_name, last_name, gender, date_of_birth, phone, address, emergency_contact, enrollment_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $userId, $studentId,
                    $studentData['first_name'], $studentData['last_name'],
                    $studentData['gender'] ?? null,
                    $studentData['date_of_birth'] ?? null,
                    $studentData['phone'] ?? null,
                    $studentData['address'] ?? null,
                    $studentData['emergency_contact'] ?? null,
                    date('Y-m-d')
                ]
            );

            // Enroll student if class_id provided
            if (!empty($studentData['class_id'])) {
                $currentYear = $this->db->fetchOne(
                    "SELECT id FROM academic_years WHERE is_current = 1"
                );

                if ($currentYear) {
                    $this->db->insert(
                        "INSERT INTO enrollments (student_id, class_id, academic_year_id, enrollment_date)
                         VALUES (?, ?, ?, ?)",
                        [
                            $studentDbId,
                            $studentData['class_id'],
                            $currentYear['id'],
                            date('Y-m-d')
                        ]
                    );
                }
            }

            return true;
        } catch (Exception $e) {
            $this->errors[] = "Row {$studentData['row_number']}: {$e->getMessage()}";
            return false;
        }
    }

    /**
     * Generate unique student ID
     */
    private function generateStudentId(): string {
        $year = date('Y');
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM students WHERE student_id LIKE ?",
            ["{$year}%"]
        );
        $count = $result['count'] ?? 0;
        return $year . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique username
     */
    private function generateUsername(string $firstName, string $lastName): string {
        $baseUsername = strtolower(substr($firstName, 0, 1) . $lastName);
        $username = $baseUsername;
        $counter = 1;

        while ($this->db->fetchOne("SELECT id FROM users WHERE username = ?", [$username])) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Get import template (columns and sample data)
     */
    public static function getTemplateCSV(): string {
        $output = "first_name,last_name,gender,date_of_birth,phone,address,emergency_contact,class_id\n";
        $output .= "John,Doe,male,2006-05-15,0788123456,123 Main St,Jane Doe (Mother),1\n";
        $output .= "Jane,Smith,female,2006-08-20,0789654321,456 Oak Ave,John Smith (Father),1\n";
        return $output;
    }
}
