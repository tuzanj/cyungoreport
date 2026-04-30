<?php
// config/report.php

function escapePdfText(string $text): string {
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function buildPdf(string $title, array $headers, array $rows): string {
    $lines = [];
    $lines[] = strtoupper($title);
    $lines[] = str_repeat('-', 90);
    $lines[] = implode(' | ', $headers);
    $lines[] = str_repeat('-', 90);

    foreach ($rows as $row) {
        $cells = array_map(fn($value) => trim((string)$value), $row);
        $lines[] = implode(' | ', $cells);
    }

    $content = "BT /F1 12 Tf 50 770 Td (" . escapePdfText(array_shift($lines)) . ") Tj ";
    $content .= "0 -20 Td (" . escapePdfText(array_shift($lines)) . ") Tj ";
    foreach ($lines as $line) {
        $content .= "0 -16 Td (" . escapePdfText($line) . ") Tj ";
    }
    $content .= "ET";
    return $content;
}

function sendPdf(string $filename, string $content): void {
    $stream = $content;
    $length = strlen($stream);

    $objects = [];
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
    $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    $objects[] = "5 0 obj\n<< /Length {$length} >>\nstream\n{$stream}\nendstream\nendobj\n";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $object) {
        $offsets[] = strlen($pdf);
        $pdf .= $object;
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= sprintf("%010d 65535 f \n", 0);
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $pdf;
    exit;
}

function sendExcel(string $filename, string $title, array $headers, array $rows): void {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    echo "<table border=1><tr>";
    foreach ($headers as $header) {
        echo '<th>' . htmlspecialchars($header) . '</th>';
    }
    echo "</tr>";
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
    exit;
}
