<?php
// admin/analytics.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_ADMIN);

$db = Database::getInstance();
$currentYear   = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$currentYearId = $currentYear ? (int)$currentYear['id'] : 0;

$analytics = [
    'total_students' => $db->fetchOne("SELECT COUNT(*) as c FROM students WHERE status='active'")['c'] ?? 0,

    'pass_rate' => (function() use ($db, $currentYearId) {
        $row = $db->fetchOne(
            "SELECT COUNT(*) as total, SUM(is_pass=1) as pass_count FROM marks m
             JOIN class_courses cc ON cc.id=m.class_course_id
             WHERE cc.academic_year_id=? AND m.status='published'", [$currentYearId]);
        return $row && $row['total'] > 0 ? round(($row['pass_count'] / $row['total']) * 100) : 0;
    })(),

    'fail_rate' => (function() use ($db, $currentYearId) {
        $row = $db->fetchOne(
            "SELECT COUNT(*) as total, SUM(is_pass=0) as fail_count FROM marks m
             JOIN class_courses cc ON cc.id=m.class_course_id
             WHERE cc.academic_year_id=? AND m.status='published'", [$currentYearId]);
        return $row && $row['total'] > 0 ? round(($row['fail_count'] / $row['total']) * 100) : 0;
    })(),

    'avg_gpa' => (function() use ($db, $currentYearId) {
        $marks = $db->fetchAll(
            "SELECT m.calculated_grade, c.credits FROM marks m
             JOIN class_courses cc ON cc.id=m.class_course_id
             JOIN courses c ON c.id=cc.course_id
             WHERE cc.academic_year_id=? AND m.status='published' AND m.calculated_grade IS NOT NULL",
            [$currentYearId]);
        $pts = 0; $cr = 0;
        foreach ($marks as $m) {
            $pts += getGpaPoint(getLetterGrade((float)$m['calculated_grade'])) * $m['credits'];
            $cr  += $m['credits'];
        }
        return $cr > 0 ? round($pts / $cr, 2) : 0;
    })(),

    'grade_dist' => $db->fetchAll(
        "SELECT letter_grade, COUNT(*) as cnt FROM marks m
         JOIN class_courses cc ON cc.id=m.class_course_id
         WHERE cc.academic_year_id=? AND m.status='published' AND m.letter_grade IS NOT NULL
         GROUP BY letter_grade ORDER BY cnt DESC",
        [$currentYearId]
    ),

    'passfail_by_course' => $db->fetchAll(
        "SELECT c.name as course_name,
                SUM(m.is_pass=1) as pass_count,
                SUM(m.is_pass=0) as fail_count
         FROM marks m
         JOIN class_courses cc ON cc.id=m.class_course_id
         JOIN courses c ON c.id=cc.course_id
         WHERE cc.academic_year_id=? AND m.status='published'
         GROUP BY c.id ORDER BY c.name LIMIT 10",
        [$currentYearId]
    ),

    'top_students' => $db->fetchAll(
        "SELECT s.student_id, s.first_name, s.last_name,
                AVG(m.calculated_grade) as avg_grade,
                SUM(m.is_pass=1) as pass_count,
                SUM(m.is_pass=0) as fail_count,
                cl.name as class_name
         FROM marks m
         JOIN students s ON s.id=m.student_id
         JOIN class_courses cc ON cc.id=m.class_course_id
         LEFT JOIN enrollments e ON e.student_id=s.id AND e.academic_year_id=cc.academic_year_id AND e.status='active'
         LEFT JOIN classes cl ON cl.id=e.class_id
         WHERE cc.academic_year_id=? AND m.status='published'
         GROUP BY s.id
         ORDER BY avg_grade DESC LIMIT 10",
        [$currentYearId]
    ),
];

include ROOT_PATH . '/views/admin/analytics.php';
