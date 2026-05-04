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

        // Get module info for weight and type
        $module = $this->db->fetchOne(
            "SELECT c.* FROM courses c
             JOIN class_courses cc ON cc.course_id = c.id
             WHERE cc.id = ?",
            [$classCourseId]
        );

        $calculated = null;
        $letter = null;
        $isPass = null;

        if ($module) {
            $formative = (float)($scores['formative_score'] ?? 0);
            $integrated = (float)($scores['integrated_score'] ?? 0);
            $comprehensive = (float)($scores['comprehensive_score'] ?? 0);

            // Calculate average marks based on available scores
            $total = 0;
            $count = 0;
            if (isset($scores['formative_score']) && $scores['formative_score'] !== 'N/A') {
                $total += $formative;
                $count++;
            }
            if (isset($scores['integrated_score']) && $scores['integrated_score'] !== 'N/A') {
                $total += $integrated;
                $count++;
            }
            if (isset($scores['comprehensive_score']) && $scores['comprehensive_score'] !== 'N/A') {
                $total += $comprehensive;
                $count++;
            }

            $calculated = ($count > 0) ? ($total / $count) : 0;
            $letter = getLetterGrade($calculated);

            // Passing score depends on module type
            // Passing Line : 50% for mathematics, sciences and complementally modules
            // while 70% is for core modules ( specific and general modules )
            $passingPercentage = 0.70;
            if (in_array($module['type'], ['complementary', 'general'])) { // general modules sometimes 50%?
                // Re-reading image: "50% for mathematics, sciences and complementally modules while 70% is for core modules (specific and general modules)"
                $passingPercentage = 0.50;
            } else if (in_array($module['type'], ['specific', 'general'])) {
                $passingPercentage = 0.70;
            }

            $moduleWeight = (float)$module['module_weight'];
            $isPass = ($calculated >= ($moduleWeight * $passingPercentage)) ? 1 : 0;
        }

        if ($existing) {
            $this->db->execute(
                "UPDATE marks SET formative_score=?, integrated_score=?, comprehensive_score=?,
                 calculated_grade=?, letter_grade=?, is_pass=?, remarks=?, updated_at=NOW()
                 WHERE student_id=? AND class_course_id=?",
                [
                    $scores['formative_score'] ?? null, $scores['integrated_score'] ?? null,
                    $scores['comprehensive_score'] ?? null,
                    $calculated, $letter, $isPass, $scores['remarks'] ?? null,
                    $studentId, $classCourseId
                ]
            );
            return (int)$existing['id'];
        } else {
            return $this->db->insert(
                "INSERT INTO marks (student_id, class_course_id, formative_score, integrated_score, comprehensive_score,
                 calculated_grade, letter_grade, is_pass, remarks)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $studentId, $classCourseId,
                    $scores['formative_score'] ?? null, $scores['integrated_score'] ?? null,
                    $scores['comprehensive_score'] ?? null,
                    $calculated, $letter, $isPass, $scores['remarks'] ?? null
                ]
            );
        }
    }

    public function createAssessment(array $data): int {
        return $this->db->insert(
            "INSERT INTO assessments (class_course_id, assessment_type, assessment_number, assessment_name, date_of_assessment, max_marks, term, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['class_course_id'], $data['assessment_type'], $data['assessment_number'],
                $data['assessment_name'] ?? null, $data['date_of_assessment'], $data['max_marks'],
                $data['term'] ?? 1, $data['created_by']
            ]
        );
    }

    public function saveAssessmentMark(int $assessmentId, int $studentId, float $score): void {
        $existing = $this->db->fetchOne(
            "SELECT id FROM assessment_marks WHERE assessment_id = ? AND student_id = ?",
            [$assessmentId, $studentId]
        );

        if ($existing) {
            $this->db->execute(
                "UPDATE assessment_marks SET score = ?, updated_at = NOW() WHERE id = ?",
                [$score, $existing['id']]
            );
        } else {
            $this->db->insert(
                "INSERT INTO assessment_marks (assessment_id, student_id, score) VALUES (?, ?, ?)",
                [$assessmentId, $studentId, $score]
            );
        }

        // After saving individual assessment mark, update the main marks table
        $this->syncMainMarks($studentId, $assessmentId);
    }

    private function syncMainMarks(int $studentId, int $assessmentId): void {
        // Get assessment info
        $assessment = $this->db->fetchOne(
            "SELECT * FROM assessments WHERE id = ?",
            [$assessmentId]
        );

        if (!$assessment) return;

        $classCourseId = $assessment['class_course_id'];
        $type = $assessment['assessment_type'];

        // Sum up all marks of this type for this student and course
        $totalScore = $this->db->fetchOne(
            "SELECT SUM(am.score) as total
             FROM assessment_marks am
             JOIN assessments a ON a.id = am.assessment_id
             WHERE am.student_id = ? AND a.class_course_id = ? AND a.assessment_type = ?",
            [$studentId, $classCourseId, $type]
        );

        $sum = $totalScore['total'] ?? 0;

        // Update the main marks table
        $field = $type . '_score'; // formative_score, integrated_score, or comprehensive_score
        
        $existing = $this->db->fetchOne(
            "SELECT id FROM marks WHERE student_id = ? AND class_course_id = ?",
            [$studentId, $classCourseId]
        );

        if ($existing) {
            $this->db->execute(
                "UPDATE marks SET $field = ?, updated_at = NOW() WHERE id = ?",
                [$sum, $existing['id']]
            );
        } else {
            $this->db->insert(
                "INSERT INTO marks (student_id, class_course_id, $field) VALUES (?, ?, ?)",
                [$studentId, $classCourseId, $sum]
            );
        }

        // Recalculate average marks and pass status
        $this->recalculateFinalMarks($studentId, $classCourseId);
    }

    public function recalculateFinalMarks(int $studentId, int $classCourseId): void {
        $mark = $this->db->fetchOne(
            "SELECT * FROM marks WHERE student_id = ? AND class_course_id = ?",
            [$studentId, $classCourseId]
        );

        if (!$mark) return;

        $module = $this->db->fetchOne(
            "SELECT c.* FROM courses c
             JOIN class_courses cc ON cc.course_id = c.id
             WHERE cc.id = ?",
            [$classCourseId]
        );

        if (!$module) return;

        $formative = (float)($mark['formative_score'] ?? 0);
        $integrated = (float)($mark['integrated_score'] ?? 0);
        $comprehensive = (float)($mark['comprehensive_score'] ?? 0);

        $total = 0;
        $count = 0;
        if ($mark['formative_score'] !== null) { $total += $formative; $count++; }
        if ($mark['integrated_score'] !== null) { $total += $integrated; $count++; }
        if ($mark['comprehensive_score'] !== null) { $total += $comprehensive; $count++; }

        $calculated = ($count > 0) ? ($total / $count) : 0;
        $letter = getLetterGrade($calculated);

        $passingPercentage = 0.70;
        if (in_array($module['type'], ['complementary', 'general'])) {
            $passingPercentage = 0.50;
        }

        $moduleWeight = (float)$module['module_weight'];
        $isPass = ($calculated >= ($moduleWeight * $passingPercentage)) ? 1 : 0;

        $this->db->execute(
            "UPDATE marks SET calculated_grade = ?, letter_grade = ?, is_pass = ? WHERE id = ?",
            [$calculated, $letter, $isPass, $mark['id']]
        );
    }

    public function getAssessments(int $classCourseId, int $term = 1): array {
        return $this->db->fetchAll(
            "SELECT * FROM assessments WHERE class_course_id = ? AND term = ? ORDER BY assessment_type, assessment_number",
            [$classCourseId, $term]
        );
    }

    public function getAssessmentMarks(int $assessmentId): array {
        return $this->db->fetchAll(
            "SELECT am.*, s.first_name, s.last_name, s.student_id as student_reg_id
             FROM assessment_marks am
             JOIN students s ON s.id = am.student_id
             WHERE am.assessment_id = ?
             ORDER BY s.last_name, s.first_name",
            [$assessmentId]
        );
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
                    m.formative_score, m.integrated_score, m.comprehensive_score,
                    m.calculated_grade, m.letter_grade, m.is_pass, m.status
             FROM marks m
             JOIN students s ON s.id = m.student_id
             WHERE m.class_course_id = ?
             ORDER BY m.calculated_grade DESC",
            [$classCourseId]
        );
    }
}
