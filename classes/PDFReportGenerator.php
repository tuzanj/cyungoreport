<?php
// classes/PDFReportGenerator.php

require_once ROOT_PATH . '/classes/ReportGenerator.php';

use Dompdf\Dompdf;

/**
 * PDF Report Generator
 * Generates learner assessment reports in PDF format
 * Using DOMPDF library (requires installation via composer)
 */
class PDFReportGenerator extends ReportGenerator {
    private string $htmlContent = '';

    public function generate(): void {
        try {
            $this->htmlContent = $this->buildReportHTML();
            
            // Check if DOMPDF is available via composer
            if (class_exists('\\Dompdf\\Dompdf')) {
                $this->generateWithDOMPDF();
            } else {
                // Fallback: Generate as HTML for browser to print as PDF
                $this->generateAsHTML();
            }
        } catch (Exception $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Failed to generate PDF: ' . $e->getMessage()]));
        }
    }

    /**
     * Generate PDF using DOMPDF
     */
    private function generateWithDOMPDF(): void {
        $dompdf = new Dompdf();
        $dompdf->loadHtml($this->htmlContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = $this->getFileName('pdf');
        $this->setDownloadHeaders('application/pdf', $filename);
        echo $dompdf->output();
    }

    /**
     * Fallback: Generate as HTML
     */
    private function generateAsHTML(): void {
        $this->setDownloadHeaders('text/html', $this->getFileName('html'));
        echo $this->htmlContent;
    }

    /**
     * Build the complete HTML report
     */
    private function buildReportHTML(): string {
        $student = $this->reportData['student'];
        $enrollment = $this->reportData['enrollment'];
        $marks = $this->reportData['marks'];
        $behavior = $this->reportData['behavior'];
        $school = $this->reportData['school'];

        $marksFormatted = $this->formatMarks($marks);

        // Start HTML document
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner Assessment Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            border: 2px solid #000;
        }
        
        .header {
            text-align: center;
            padding: 20px;
            border-bottom: 2px solid #000;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 12px;
            margin: 2px 0;
        }
        
        .school-logo {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .school-logo img {
            max-height: 80px;
        }
        
        .student-info {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .info-section {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            width: 25%;
            padding: 8px;
            border: 1px solid #999;
            font-weight: bold;
            background-color: #f0f0f0;
        }
        
        .info-value {
            display: table-cell;
            width: 25%;
            padding: 8px;
            border: 1px solid #999;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        thead {
            background-color: #f0f0f0;
        }
        
        th, td {
            padding: 10px;
            border: 1px solid #999;
            text-align: center;
        }
        
        th {
            font-weight: bold;
            background-color: #e0e0e0;
        }
        
        .course-name {
            text-align: left;
        }
        
        .pass {
            background-color: #d4edda;
        }
        
        .fail {
            background-color: #f8d7da;
        }
        
        .footer {
            text-align: center;
            padding: 15px;
            font-size: 11px;
            border-top: 2px solid #000;
            margin-top: 20px;
        }
        
        .signature-section {
            margin-top: 30px;
            display: table;
            width: 100%;
        }
        
        .signature {
            display: table-cell;
            text-align: center;
            padding: 20px;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 30px;
            font-size: 12px;
        }
        
        @media print {
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <p><strong>REPUBLIC OF RWANDA</strong></p>
            <p><strong>MINISTRY OF EDUCATION</strong></p>
            <p>GISAGARA DISTRICT</p>
            <h1>{$school['name']}</h1>
            <p>Phone: {$school['phone']}</p>
            <h2 style="margin-top: 10px;">LEARNER'S ASSESSMENT REPORT</h2>
        </div>

        <div class="student-info">
            <div class="info-section">
                <span class="info-label">Student Name:</span>
                <span class="info-value">{$student['first_name']} {$student['last_name']}</span>
                <span class="info-label">Student ID:</span>
                <span class="info-value">{$student['student_id']}</span>
            </div>
            <div class="info-section">
                <span class="info-label">Class:</span>
                <span class="info-value">{$enrollment['class_name']}</span>
                <span class="info-label">Academic Year:</span>
                <span class="info-value">{$enrollment['academic_year']}</span>
            </div>
            <div class="info-section">
                <span class="info-label">Date of Birth:</span>
                <span class="info-value">{$student['date_of_birth']}</span>
                <span class="info-label">Gender:</span>
                <span class="info-value">{$student['gender']}</span>
            </div>
        </div>

        <h3 style="margin: 20px 0 10px 0;">COURSES AND MARKS</h3>
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th class="course-name">Course Name</th>
                    <th>1st Term</th>
                    <th>2nd Term</th>
                    <th>3rd Term</th>
                    <th>ANNUAL</th>
                    <th>Grade</th>
                    <th>Behavior</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
HTML;

        foreach ($marksFormatted as $mark) {
            $statusClass = $mark['status'] === 'PASS' ? 'pass' : 'fail';
            $html .= <<<HTML
                <tr>
                    <td>{$mark['code']}</td>
                    <td class="course-name">{$mark['name']}</td>
                    <td>{$mark['assignments']}</td>
                    <td>{$mark['quizzes']}</td>
                    <td>{$mark['midterm']}</td>
                    <td><strong>{$mark['percentage']}</strong></td>
                    <td>{$mark['grade']}</td>
                    <td>{$mark['behavior']}</td>
                    <td class="{$statusClass}">{$mark['status']}</td>
                </tr>
HTML;
        }

        // Add behavior summary
        $behaviorGrade = $behavior['behavior_grade'] ?? 'N/A';
        $behaviorRemarks = $behavior['remarks'] ?? 'No remarks';

        $html .= <<<HTML
            </tbody>
        </table>

        <h3 style="margin: 20px 0 10px 0;">BEHAVIOR ASSESSMENT</h3>
        <table>
            <tr>
                <td style="width: 20%; font-weight: bold;">Overall Conduct Grade:</td>
                <td style="width: 30%; text-align: left; font-weight: bold; font-size: 16px;">{$behaviorGrade}</td>
                <td style="width: 20%; font-weight: bold;">Teacher Remarks:</td>
                <td style="width: 30%; text-align: left;">{$behaviorRemarks}</td>
            </tr>
        </table>

        <div class="signature-section">
            <div class="signature">
                <div class="signature-line">Class Teacher</div>
            </div>
            <div class="signature">
                <div class="signature-line">School Manager</div>
            </div>
        </div>

        <div class="footer">
            <p>Report generated on: {$this->getFormattedDate()}</p>
            <p>This is an official school document. For inquiries, contact the school office.</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Get formatted date
     */
    private function getFormattedDate(): string {
        return date('d-m-Y H:i:s');
    }
}
