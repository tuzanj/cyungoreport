<?php
// models/DisciplineModel.php

require_once ROOT_PATH . '/models/BaseModel.php';

class DisciplineModel extends BaseModel {
    protected string $table = 'student_discipline';

    public function getFaults(bool $activeOnly = true): array {
        $where = $activeOnly ? "WHERE is_active = 1" : "";
        return $this->db->fetchAll("SELECT * FROM faults $where ORDER BY name ASC");
    }

    public function createFault(string $name, int $points): int {
        return $this->db->insert(
            "INSERT INTO faults (name, points_deduction) VALUES (?, ?)",
            [$name, $points]
        );
    }

    public function updateFault(int $id, string $name, int $points, bool $isActive): int {
        return $this->db->execute(
            "UPDATE faults SET name = ?, points_deduction = ?, is_active = ? WHERE id = ?",
            [$name, $points, $isActive ? 1 : 0, $id]
        );
    }

    public function recordIncident(array $data): int {
        return $this->db->insert(
            "INSERT INTO student_discipline (student_id, fault_id, academic_year_id, term, incident_date, description, recorded_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['student_id'], $data['fault_id'], $data['academic_year_id'], 
                $data['term'], $data['incident_date'], $data['description'] ?? null, 
                $data['recorded_by']
            ]
        );
    }

    public function getStudentIncidents(int $studentId, int $yearId, int $term): array {
        return $this->db->fetchAll(
            "SELECT sd.*, f.name as fault_name, f.points_deduction, u.username as recorded_by_user
             FROM student_discipline sd
             JOIN faults f ON f.id = sd.fault_id
             JOIN users u ON u.id = sd.recorded_by
             WHERE sd.student_id = ? AND sd.academic_year_id = ? AND sd.term = ?
             ORDER BY sd.incident_date DESC",
            [$studentId, $yearId, $term]
        );
    }

    public function getStudentDisciplineMark(int $studentId, int $yearId, int $term): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM student_discipline_marks 
             WHERE student_id = ? AND academic_year_id = ? AND term = ?",
            [$studentId, $yearId, $term]
        );
    }

    public function updateDisciplineMark(int $studentId, int $yearId, int $term, float $deductions, int $recordedBy): void {
        $totalPoints = 40.00;
        $finalScore = max(0, $totalPoints - $deductions);

        $existing = $this->getStudentDisciplineMark($studentId, $yearId, $term);

        if ($existing) {
            $this->db->execute(
                "UPDATE student_discipline_marks SET deductions = ?, final_score = ?, recorded_by = ? 
                 WHERE id = ?",
                [$deductions, $finalScore, $recordedBy, $existing['id']]
            );
        } else {
            $this->db->insert(
                "INSERT INTO student_discipline_marks (student_id, academic_year_id, term, total_points, deductions, final_score, recorded_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$studentId, $yearId, $term, $totalPoints, $deductions, $finalScore, $recordedBy]
            );
        }
    }

    public function recalculateTermDiscipline(int $studentId, int $yearId, int $term, int $recordedBy): void {
        $row = $this->db->fetchOne(
            "SELECT SUM(f.points_deduction) as total_deduction
             FROM student_discipline sd
             JOIN faults f ON f.id = sd.fault_id
             WHERE sd.student_id = ? AND sd.academic_year_id = ? AND sd.term = ?",
            [$studentId, $yearId, $term]
        );

        $deductions = (float)($row['total_deduction'] ?? 0);
        $this->updateDisciplineMark($studentId, $yearId, $term, $deductions, $recordedBy);
    }
}
