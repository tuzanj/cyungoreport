<?php
// models/ClassModel.php

require_once ROOT_PATH . '/models/BaseModel.php';

class ClassModel extends BaseModel {
    protected string $table = 'classes';

    public function create(array $data): int {
        return $this->db->insert(
            "INSERT INTO classes (name, grade_level, section, trade_id, academic_year_id, max_students) VALUES (?,?,?,?,?,?)",
            [$data['name'], $data['grade_level'] ?? null, $data['section'] ?? null,
             $data['trade_id'] ?? null, $data['academic_year_id'], $data['max_students'] ?? 40]
        );
    }

    public function getForYear(int $yearId): array {
        return $this->db->fetchAll(
            "SELECT c.*, ay.name as year_name,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.class_id=c.id AND e.academic_year_id=? AND e.status='active') as enrolled
             FROM classes c
             JOIN academic_years ay ON ay.id = c.academic_year_id
             WHERE c.academic_year_id = ?
             ORDER BY c.name",
            [$yearId, $yearId]
        );
    }

    public function assignCourse(int $classId, int $courseId, int $teacherId, int $yearId): int {
        $existing = $this->db->fetchOne(
            "SELECT id FROM class_courses WHERE class_id=? AND course_id=? AND academic_year_id=?",
            [$classId, $courseId, $yearId]
        );
        if ($existing) {
            $this->db->execute(
                "UPDATE class_courses SET teacher_id=? WHERE id=?",
                [$teacherId, $existing['id']]
            );
            return (int)$existing['id'];
        }
        return $this->db->insert(
            "INSERT INTO class_courses (class_id, course_id, teacher_id, academic_year_id) VALUES (?,?,?,?)",
            [$classId, $courseId, $teacherId, $yearId]
        );
    }

    public function getSchedules(int $classCourseId): array {
        return $this->db->fetchAll(
            "SELECT * FROM schedules WHERE class_course_id=? ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), start_time",
            [$classCourseId]
        );
    }

    public function addSchedule(int $classCourseId, string $day, string $start, string $end, ?string $room): int {
        return $this->db->insert(
            "INSERT INTO schedules (class_course_id, day_of_week, start_time, end_time, room) VALUES (?,?,?,?,?)",
            [$classCourseId, $day, $start, $end, $room]
        );
    }

    public function getFullTimetable(int $classId, int $yearId): array {
        return $this->db->fetchAll(
            "SELECT s.day_of_week, s.start_time, s.end_time, s.room,
                    c.name as course_name, c.code,
                    t.first_name as teacher_first, t.last_name as teacher_last
             FROM schedules s
             JOIN class_courses cc ON cc.id = s.class_course_id
             JOIN courses c ON c.id = cc.course_id
             JOIN teachers t ON t.id = cc.teacher_id
             WHERE cc.class_id=? AND cc.academic_year_id=?
             ORDER BY FIELD(s.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), s.start_time",
            [$classId, $yearId]
        );
    }
}
