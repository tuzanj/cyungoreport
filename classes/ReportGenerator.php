<?php
// classes/ReportGenerator.php

/**
 * Base Report Generator class
 * Handles common report generation logic
 */
abstract class ReportGenerator {
    protected array $reportData;
    protected string $reportType;
    protected string $fileName;

    public function __construct(array $reportData, string $fileName = 'report') {
        $this->reportData = $reportData;
        $this->fileName = $fileName;
    }

    /**
     * Generate and download the report
     */
    abstract public function generate(): void;

    /**
     * Get report file name with timestamp
     */
    protected function getFileName(string $extension): string {
        $timestamp = date('Y-m-d_His');
        return $this->fileName . '_' . $timestamp . '.' . $extension;
    }

    /**
     * Set appropriate headers for download
     */
    protected function setDownloadHeaders(string $mimeType, string $filename): void {
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    /**
     * Format marks data for display
     */
    protected function formatMarks(array $marks): array {
        $formatted = [];
        foreach ($marks as $mark) {
            $formatted[] = [
                'code' => $mark['code'] ?? '',
                'name' => $mark['name'] ?? '',
                'assignments' => $mark['assignments_score'] ?? 'N/A',
                'quizzes' => $mark['quizzes_score'] ?? 'N/A',
                'midterm' => $mark['midterm_score'] ?? 'N/A',
                'final' => $mark['final_score'] ?? 'N/A',
                'percentage' => $mark['calculated_grade'] ?? 'N/A',
                'grade' => $mark['letter_grade'] ?? 'N/A',
                'status' => $mark['is_pass'] ? 'PASS' : 'FAIL',
                'behavior' => $mark['behavior_grade'] ?? 'N/A',
                'remarks' => $mark['remarks'] ?? ''
            ];
        }
        return $formatted;
    }

    /**
     * Get letter grade from percentage
     */
    protected function getLetterGrade(float $percentage): string {
        if ($percentage >= 90) return 'A';
        if ($percentage >= 80) return 'B';
        if ($percentage >= 70) return 'C';
        if ($percentage >= 60) return 'D';
        if ($percentage >= 50) return 'E';
        return 'F';
    }
}
