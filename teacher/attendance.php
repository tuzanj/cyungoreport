<?php
// teacher/attendance.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_TEACHER);

require_once ROOT_PATH . '/controllers/TeacherController.php';
require_once ROOT_PATH . '/models/TeacherModel.php';
require_once ROOT_PATH . '/models/AttendanceModel.php';
require_once ROOT_PATH . '/models/EnrollmentModel.php';

$teacherModel  = new TeacherModel();
$teacherCtrl   = new TeacherController();
$attendModel   = new AttendanceModel();
$enrollModel   = new EnrollmentModel();
$db            = Database::getInstance();

$teacher = $teacherModel->findByUserId(currentUserId());
if (!$teacher) redirect('/index.php');

$currentYear = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$yearId      = $currentYear ? (int)$currentYear['id'] : 0;
$courses     = $teacherModel->getAssignedCourses((int)$teacher['id'], $yearId);

$selectedCcId  = (int)($_GET['cc'] ?? 0);
$selectedDate  = $_GET['date'] ?? date('Y-m-d');
$students      = [];
$existingMap   = [];
$courseName    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.'); redirect('/teacher/attendance.php');
    }
    $classCourseId   = (int)($_POST['class_course_id'] ?? 0);
    $date            = $_POST['date'] ?? date('Y-m-d');
    $attendanceData  = $_POST['attendance'] ?? [];
    $result = $teacherCtrl->recordAttendance($classCourseId, $date, $attendanceData);
    setFlash($result['success'] ? 'success' : 'danger', $result['message']);
    redirect('/teacher/attendance.php?cc='.$classCourseId.'&date='.$date);
}

if ($selectedCcId) {
    $students = $enrollModel->getStudentsInClass(
        $db->fetchOne("SELECT class_id FROM class_courses WHERE id=?", [$selectedCcId])['class_id'] ?? 0,
        $yearId
    );
    $existing = $attendModel->getForDate($selectedCcId, $selectedDate);
    foreach ($existing as $e) { $existingMap[$e['student_id']] = $e; }
    $cc = $db->fetchOne("SELECT c.name as course_name FROM class_courses cc JOIN courses c ON c.id=cc.course_id WHERE cc.id=?", [$selectedCcId]);
    $courseName = $cc['course_name'] ?? '';
}

include ROOT_PATH . '/views/teacher/attendance.php';
