-- ============================================================
-- MIGRATION: Update Marks Structure and Module Categories
-- ============================================================

-- 1. Update courses table module categories
ALTER TABLE courses MODIFY COLUMN type ENUM('complementary','general','specific','co-curricular') DEFAULT 'specific';
ALTER TABLE courses ADD COLUMN IF NOT EXISTS module_weight INT DEFAULT 0 AFTER credits;

-- 2. Create assessments table
CREATE TABLE IF NOT EXISTS assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_course_id INT NOT NULL,
    assessment_type ENUM('formative', 'integrated', 'comprehensive') NOT NULL,
    assessment_number INT NOT NULL,
    assessment_name VARCHAR(100) DEFAULT NULL,
    date_of_assessment DATE NOT NULL,
    max_marks DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    term INT NOT NULL DEFAULT 1,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_course_id) REFERENCES class_courses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- 3. Create assessment_marks table
CREATE TABLE IF NOT EXISTS assessment_marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT NOT NULL,
    student_id INT NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_assessment_student (assessment_id, student_id),
    FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- 4. Update marks table columns
ALTER TABLE marks DROP COLUMN IF EXISTS assignments_score;
ALTER TABLE marks DROP COLUMN IF EXISTS quizzes_score;
ALTER TABLE marks DROP COLUMN IF EXISTS midterm_score;
ALTER TABLE marks DROP COLUMN IF EXISTS final_score;

ALTER TABLE marks ADD COLUMN IF NOT EXISTS formative_score DECIMAL(5,2) DEFAULT NULL AFTER class_course_id;
ALTER TABLE marks ADD COLUMN IF NOT EXISTS integrated_score DECIMAL(5,2) DEFAULT NULL AFTER formative_score;
ALTER TABLE marks ADD COLUMN IF NOT EXISTS comprehensive_score DECIMAL(5,2) DEFAULT NULL AFTER integrated_score;
