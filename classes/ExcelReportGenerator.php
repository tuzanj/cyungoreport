<?php
// classes/ExcelReportGenerator.php

require_once ROOT_PATH . '/classes/ReportGenerator.php';

/**
 * Excel Report Generator
 * Generates learner assessment reports in Excel format
 * Uses native PHP with simple CSV-to-XLS or PhpSpreadsheet if available
 */
class ExcelReportGenerator extends ReportGenerator {

    public function generate(): void {
        try {
            // Try to use PhpSpreadsheet if available
            if (class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
                $this->generateWithPhpSpreadsheet();
            } else {
                // Fallback: Generate as CSV (Excel-compatible)
                $this->generateAsCSV();
            }
        } catch (Exception $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Failed to generate Excel: ' . $e->getMessage()]));
        }
    }

    /**
     * Generate Excel using PhpSpreadsheet
     */
    private function generateWithPhpSpreadsheet(): void {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $spreadsheet->getProperties()
            ->setTitle('Learner Assessment Report')
            ->setAuthor('School Management System');

        $student = $this->reportData['student'];
        $enrollment = $this->reportData['enrollment'];
        $marks = $this->reportData['marks'];
        $behavior = $this->reportData['behavior'];
        $school = $this->reportData['school'];

        $row = 1;

        // Header
        $sheet->setCellValue('A' . $row, 'REPUBLIC OF RWANDA');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;

        $sheet->setCellValue('A' . $row, 'MINISTRY OF EDUCATION - GISAGARA DISTRICT');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, $school['name']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $row++;

        $sheet->setCellValue('A' . $row, 'LEARNER\'S ASSESSMENT REPORT');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row += 2;

        // Student Information
        $sheet->setCellValue('A' . $row, 'Student Name:');
        $sheet->setCellValue('B' . $row, $student['first_name'] . ' ' . $student['last_name']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 'Student ID:');
        $sheet->setCellValue('B' . $row, $student['student_id']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 'Class:');
        $sheet->setCellValue('B' . $row, $enrollment['class_name']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 'Academic Year:');
        $sheet->setCellValue('B' . $row, $enrollment['academic_year']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 'Date of Birth:');
        $sheet->setCellValue('B' . $row, $student['date_of_birth']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 'Gender:');
        $sheet->setCellValue('B' . $row, ucfirst($student['gender']));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row += 2;

        // Marks Table Header
        $sheet->setCellValue('A' . $row, 'Code');
        $sheet->setCellValue('B' . $row, 'Course Name');
        $sheet->setCellValue('C' . $row, '1st Term');
        $sheet->setCellValue('D' . $row, '2nd Term');
        $sheet->setCellValue('E' . $row, '3rd Term');
        $sheet->setCellValue('F' . $row, 'ANNUAL %');
        $sheet->setCellValue('G' . $row, 'Grade');
        $sheet->setCellValue('H' . $row, 'Behavior');
        $sheet->setCellValue('I' . $row, 'Status');
        $sheet->setCellValue('J' . $row, 'Remarks');

        // Style header row
        $headerStyle = $sheet->getStyle('A' . $row . ':J' . $row);
        $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF366092');
        $headerStyle->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $row++;

        // Add marks data
        $marksFormatted = $this->formatMarks($marks);
        foreach ($marksFormatted as $mark) {
            $sheet->setCellValue('A' . $row, $mark['code']);
            $sheet->setCellValue('B' . $row, $mark['name']);
            $sheet->setCellValue('C' . $row, $mark['assignments']);
            $sheet->setCellValue('D' . $row, $mark['quizzes']);
            $sheet->setCellValue('E' . $row, $mark['midterm']);
            $sheet->setCellValue('F' . $row, $mark['percentage']);
            $sheet->setCellValue('G' . $row, $mark['grade']);
            $sheet->setCellValue('H' . $row, $mark['behavior']);
            $sheet->setCellValue('I' . $row, $mark['status']);
            $sheet->setCellValue('J' . $row, $mark['remarks']);

            // Style data rows
            if ($mark['status'] === 'PASS') {
                $sheet->getStyle('I' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFCCFFCC');
            } else {
                $sheet->getStyle('I' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFCCCC');
            }

            $row++;
        }

        $row += 2;

        // Behavior Section
        $sheet->setCellValue('A' . $row, 'BEHAVIOR ASSESSMENT');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row += 2;

        $sheet->setCellValue('A' . $row, 'Overall Conduct Grade:');
        $sheet->setCellValue('B' . $row, $behavior['behavior_grade'] ?? 'N/A');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 'Teacher Remarks:');
        $sheet->setCellValue('B' . $row, $behavior['remarks'] ?? 'No remarks');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row += 2;

        // Footer
        $sheet->setCellValue('A' . $row, 'Report generated on: ' . date('d-m-Y H:i:s'));
        $sheet->getStyle('A' . $row)->getFont()->setSize(10)->setItalic(true);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(10);
        $sheet->getColumnDimension('J')->setWidth(25);

        // Generate and send
        $filename = $this->getFileName('xlsx');
        $this->setDownloadHeaders('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $filename);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    /**
     * Fallback: Generate as CSV
     */
    private function generateAsCSV(): void {
        $student = $this->reportData['student'];
        $enrollment = $this->reportData['enrollment'];
        $marks = $this->reportData['marks'];
        $behavior = $this->reportData['behavior'];
        $school = $this->reportData['school'];

        $filename = $this->getFileName('csv');
        $this->setDownloadHeaders('text/csv; charset=utf-8', $filename);

        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8 in Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header
        fputcsv($output, ['REPUBLIC OF RWANDA']);
        fputcsv($output, ['MINISTRY OF EDUCATION - GISAGARA DISTRICT']);
        fputcsv($output, [$school['name']]);
        fputcsv($output, ['LEARNER\'S ASSESSMENT REPORT']);
        fputcsv($output, []);

        // Student Info
        fputcsv($output, ['Student Name:', $student['first_name'] . ' ' . $student['last_name']]);
        fputcsv($output, ['Student ID:', $student['student_id']]);
        fputcsv($output, ['Class:', $enrollment['class_name']]);
        fputcsv($output, ['Academic Year:', $enrollment['academic_year']]);
        fputcsv($output, ['Date of Birth:', $student['date_of_birth']]);
        fputcsv($output, ['Gender:', ucfirst($student['gender'])]);
        fputcsv($output, []);

        // Marks Header
        fputcsv($output, [
            'Code', 'Course Name', '1st Term', '2nd Term', '3rd Term', 
            'ANNUAL %', 'Grade', 'Behavior', 'Status', 'Remarks'
        ]);

        // Marks Data
        $marksFormatted = $this->formatMarks($marks);
        foreach ($marksFormatted as $mark) {
            fputcsv($output, [
                $mark['code'], $mark['name'], $mark['assignments'], $mark['quizzes'],
                $mark['midterm'], $mark['percentage'], $mark['grade'],
                $mark['behavior'], $mark['status'], $mark['remarks']
            ]);
        }

        fputcsv($output, []);
        fputcsv($output, ['BEHAVIOR ASSESSMENT']);
        fputcsv($output, ['Overall Conduct Grade:', $behavior['behavior_grade'] ?? 'N/A']);
        fputcsv($output, ['Teacher Remarks:', $behavior['remarks'] ?? 'No remarks']);
        fputcsv($output, []);
        fputcsv($output, ['Report generated on: ' . date('d-m-Y H:i:s')]);

        fclose($output);
    }
}
