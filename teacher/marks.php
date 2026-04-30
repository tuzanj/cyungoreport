<?php
// teacher/marks.php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

startSecureSession();
requireRole(ROLE_TEACHER);

require_once ROOT_PATH . '/controllers/TeacherController.php';
require_once ROOT_PATH . '/models/TeacherModel.php';
require_once ROOT_PATH . '/models/CourseModel.php';

$teacherModel = new TeacherModel();
$teacherCtrl  = new TeacherController();
$courseModel  = new CourseModel();
$db           = Database::getInstance();

$teacher     = $teacherModel->findByUserId(currentUserId());
if (!$teacher) redirect('/index.php');

$currentYear = $db->fetchOne("SELECT * FROM academic_years WHERE is_current=1 LIMIT 1");
$yearId      = $currentYear ? (int)$currentYear['id'] : 0;

$courses       = $teacherModel->getAssignedCourses((int)$teacher['id'], $yearId);
$selectedCcId  = (int)($_GET['cc'] ?? 0);
$students      = [];
$criteria      = null;
$courseName    = '';
$canPublish    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.'); redirect('/teacher/marks.php');
    }
    $formAction   = $_POST['form_action'] ?? '';
    $classCourseId = (int)($_POST['class_course_id'] ?? 0);

    if ($formAction === 'save_marks') {
        $marksData = $_POST['marks'] ?? [];
        $result = $teacherCtrl->saveMarks($classCourseId, $marksData);
        setFlash($result['success'] ? 'success' : 'danger', $result['message']);
        redirect('/teacher/marks.php?cc='.$classCourseId);
    }

    if ($formAction === 'publish') {
        $result = $teacherCtrl->publishResults($classCourseId);
        setFlash($result['success'] ? 'success' : 'danger', $result['message']);
        redirect('/teacher/marks.php?cc='.$classCourseId);
    }

    if ($formAction === 'delete_mark') {
        $result = $teacherCtrl->deleteMark((int)$_POST['mark_id']);
        setFlash($result['success'] ? 'success' : 'danger', $result['message'] ?? $result['error']);
        redirect('/teacher/marks.php?cc='.$classCourseId);
    }

    if ($formAction === 'submit_supplementary') {
        $score = (float)($_POST['supplementary_score'] ?? 0);
        $result = $teacherCtrl->submitSupplementary((int)$_POST['mark_id'], $score);
        setFlash($result['success'] ? 'success' : 'danger', $result['message']);
        redirect('/teacher/marks.php?cc='.$classCourseId);
    }
}

if ($selectedCcId) {
    $students  = $teacherCtrl->getClassroomMarks($selectedCcId);
    $criteria  = $courseModel->getGradingCriteria(
        $db->fetchOne("SELECT course_id FROM class_courses WHERE id=?", [$selectedCcId])['course_id'] ?? 0,
        $yearId
    );
    $cc = $db->fetchOne("SELECT c.name as course_name FROM class_courses cc JOIN courses c ON c.id=cc.course_id WHERE cc.id=?", [$selectedCcId]);
    $courseName = $cc['course_name'] ?? '';
    $canPublish = !empty(array_filter($students, fn($s) => $s['mark_status'] === 'draft'));
}

include ROOT_PATH . '/views/teacher/marks.php';
