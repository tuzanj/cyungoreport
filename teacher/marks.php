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
$assessmentId  = (int)($_GET['assessment_id'] ?? 0);
$students      = [];
$assessments   = [];
$currentAssessment = null;
$assessmentMarks = [];
$criteria      = null;
$courseName    = '';
$canPublish    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid CSRF token.'); redirect('/teacher/marks.php');
    }
    $formAction   = $_POST['form_action'] ?? '';
    $classCourseId = (int)($_POST['class_course_id'] ?? 0);

    if ($formAction === 'create_assessment') {
        $data = [
            'class_course_id' => $classCourseId,
            'assessment_type' => $_POST['assessment_type'],
            'assessment_number' => (int)$_POST['assessment_number'],
            'assessment_name' => $_POST['assessment_name'],
            'date_of_assessment' => $_POST['date_of_assessment'],
            'max_marks' => (float)$_POST['max_marks'],
            'term' => (int)($_POST['term'] ?? 1),
            'created_by' => currentUserId()
        ];
        $result = $teacherCtrl->createAssessment($data);
        setFlash($result['success'] ? 'success' : 'danger', $result['message']);
        redirect('/teacher/marks.php?cc='.$classCourseId.'&assessment_id='.$result['id']);
    }

    if ($formAction === 'save_assessment_marks') {
        $assessmentId = (int)$_POST['assessment_id'];
        $marksData = $_POST['marks'] ?? [];
        $result = $teacherCtrl->saveAssessmentMarks($assessmentId, $marksData);
        setFlash($result['success'] ? 'success' : 'danger', $result['message']);
        redirect('/teacher/marks.php?cc='.$classCourseId.'&assessment_id='.$assessmentId);
    }

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
    $assessments = $teacherCtrl->getAssessments($selectedCcId);
    
    if ($assessmentId) {
        $currentAssessment = $db->fetchOne("SELECT * FROM assessments WHERE id = ?", [$assessmentId]);
        if ($currentAssessment) {
            $marks = $teacherCtrl->getAssessmentMarks($assessmentId);
            foreach ($marks as $m) {
                $assessmentMarks[$m['student_id']] = $m['score'];
            }
        }
    }

    $criteria  = $courseModel->getGradingCriteria(
        $db->fetchOne("SELECT course_id FROM class_courses WHERE id=?", [$selectedCcId])['course_id'] ?? 0,
        $yearId
    );
    $cc = $db->fetchOne("SELECT c.name as course_name, c.module_weight FROM class_courses cc JOIN courses c ON c.id=cc.course_id WHERE cc.id=?", [$selectedCcId]);
    $courseName = $cc['course_name'] ?? '';
    $moduleWeight = $cc['module_weight'] ?? 0;
    $canPublish = !empty(array_filter($students, fn($s) => $s['mark_status'] === 'draft'));
}

include ROOT_PATH . '/views/teacher/marks.php';
