<?php
// secretary/dashboard.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_SECRETARY);

$db = Database::getInstance();
$currentYear   = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$currentYearId = $currentYear ? (int)$currentYear['id'] : 0;

$stats = [
    'total_students'  => $db->fetchOne("SELECT COUNT(*) as c FROM students WHERE status='active'")['c'] ?? 0,
    'enrolled_today'  => $db->fetchOne("SELECT COUNT(*) as c FROM enrollments WHERE enrollment_date=CURDATE()")['c'] ?? 0,
    'total_classes'   => $db->fetchOne("SELECT COUNT(*) as c FROM classes WHERE academic_year_id=?", [$currentYearId])['c'] ?? 0,
    'pending_parents' => $db->fetchOne(
        "SELECT COUNT(*) as c FROM students s
         WHERE NOT EXISTS (SELECT 1 FROM parent_student ps WHERE ps.student_id=s.id)"
    )['c'] ?? 0,
];

$recentStudents = $db->fetchAll(
    "SELECT s.*, e.enrollment_date, cl.name as class_name
     FROM students s
     LEFT JOIN enrollments e ON e.student_id=s.id AND e.academic_year_id=? AND e.status='active'
     LEFT JOIN classes cl ON cl.id=e.class_id
     ORDER BY s.created_at DESC LIMIT 10",
    [$currentYearId]
);

include ROOT_PATH . '/views/secretary/dashboard.php';
