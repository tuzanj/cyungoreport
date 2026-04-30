<?php
// models/MarkModel.php

require_once ROOT_PATH . '/models/BaseModel.php';

class MarkModel extends BaseModel {
    protected string $table = 'marks';

    public function upsert(int $studentId, int $classCourseId, array $scores): int {
        $existing = $this->db->fetchOne(
            "SELECT id FROM marks WHERE student_id = ? AND class_course_id = ?",
            [$studentId, $classCourseId]
        );

        // Get grading criteria for calculation
        $criteria = $this->db->fetchOne(
            "SELECT gc.* FROM grading_criteria gc
             JOIN class_courses cc ON cc.course_id = gc.course_id
             WHERE cc.id = ? AND gc.academic_year_id = cc.academic_year_id",
            [$classCourseId]
        );

        $calculated = null;
        $letter = null;
        $isPass = null;

        if ($criteria) {
            $assignments = (float)($scores['assignments_score'] ?? 0);
            $quizzes    = (float)($scores['quizzes_score'] ?? 0);
            $midterm    = (float)($scores['midterm_score'] ?? 0);
            $final      = (float)($scores['final_score'] ?? 0);

            $calculated = ($assignments * $criteria['assignments_weight'] / 100)
                        + ($quizzes * $criteria['quizzes_weight'] / 100)
                        + ($midterm * $criteria['midterm_weight'] / 100)
                        + ($final * $criteria['final_weight'] / 100);

            $letter = getLetterGrade($calculated);
            $isPass = ($calculated >= (float)$criteria['passing_score']) ? 1 : 0;
        }

        if ($existing) {
            $this->db->execute(
                "UPDATE marks SET assignments_score=?, quizzes_score=?, midterm_score=?, final_score=?,
                 calculated_grade=?, letter_grade=?, is_pass=?, remarks=?, updated_at=NOW()
                 WHERE student_id=? AND class_course_id=?",
                [
                    $scores['assignments_score'] ?? null, $scores['quizzes_score'] ?? null,
                    $scores['midterm_score'] ?? null, $scores['final_score'] ?? null,
                    $calculated, $letter, $isPass, $scores['remarks'] ?? null,
                    $studentId, $classCourseId
                ]
            );
            return (int)$existing['id'];
        } else {
            return $this->db->insert(
                "INSERT INTO marks (student_id, class_course_id, assignments_score, quizzes_score, midterm_score, final_score,
                 calculated_grade, letter_grade, is_pass, remarks)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $studentId, $classCourseId,
                    $scores['assignments_score'] ?? null, $scores['quizzes_score'] ?? null,
                    $scores['midterm_score'] ?? null, $scores['final_score'] ?? null,
                    $calculated, $letter, $isPass, $scores['remarks'] ?? null
                ]
            );
        }
    }

    public function publishAll(int $classCourseId): int {
        return $this->db->execute(
            "UPDATE marks SET status = 'published', published_at = NOW()
             WHERE class_course_id = ? AND status = 'draft'",
            [$classCourseId]
        );
    }

    public function deleteIfUnpublished(int $markId): bool {
        $mark = $this->findById($markId);
        if ($mark && $mark['status'] === 'draft') {
            $this->delete($markId);
            return true;
        }
        return false;
    }

    public function getStudentMarks(int $studentId, int $academicYearId): array {
        return $this->db->fetchAll(
            "SELECT m.*, c.name as course_name, c.code, c.credits,
                    cl.name as class_name,
                    t.first_name as teacher_first, t.last_name as teacher_last
             FROM marks m
             JOIN class_courses cc ON cc.id = m.class_course_id
             JOIN courses c ON c.id = cc.course_id
             JOIN classes cl ON cl.id = cc.class_id
             JOIN teachers t ON t.id = cc.teacher_id
             WHERE m.student_id = ? AND cc.academic_year_id = ?
             ORDER BY c.name",
            [$studentId, $academicYearId]
        );
    }

    public function submitSupplementary(int $markId, float $score, string $letterGrade): void {
        $this->db->execute(
            "UPDATE marks SET supplementary_score=?, is_supplementary=1, calculated_grade=?, letter_grade=?,
             is_pass = IF(? >= (SELECT gc.passing_score FROM grading_criteria gc
                                JOIN class_courses cc ON cc.course_id = gc.course_id
                                WHERE cc.id = (SELECT class_course_id FROM marks WHERE id=? )
                                LIMIT 1), 1, 0)
             WHERE id=?",
            [$score, $score, $letterGrade, $score, $markId, $markId]
        );
    }

    public function getClassReport(int $classCourseId): array {
        return $this->db->fetchAll(
            "SELECT s.student_id, s.first_name, s.last_name,
                    m.assignments_score, m.quizzes_score, m.midterm_score, m.final_score,
                    m.calculated_grade, m.letter_grade, m.is_pass, m.status
             FROM marks m
             JOIN students s ON s.id = m.student_id
             WHERE m.class_course_id = ?
             ORDER BY m.calculated_grade DESC",
            [$classCourseId]
        );
    }
}
