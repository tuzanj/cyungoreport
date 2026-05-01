-- ============================================================
-- MIGRATION: Add Behavior Tracking and Change Department to Trade
-- ============================================================
-- This migration adds behavior tracking and renames departments to trades (RTB terminology)

-- 1. Add behavior field to marks table
ALTER TABLE marks ADD COLUMN behavior_grade VARCHAR(5) DEFAULT NULL AFTER letter_grade;
ALTER TABLE marks ADD COLUMN behavior_remarks TEXT DEFAULT NULL AFTER remarks;

-- 2. Create behavior tracking table
CREATE TABLE IF NOT EXISTS behavior_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_course_id INT NOT NULL,
    term INT DEFAULT 1,
    academic_year_id INT NOT NULL,
    behavior_grade VARCHAR(5) DEFAULT NULL,
    conduct_score DECIMAL(5,2) DEFAULT NULL,
    remarks TEXT,
    recorded_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_behavior (student_id, class_course_id, term, academic_year_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_course_id) REFERENCES class_courses(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- 3. Rename departments table to trades
RENAME TABLE departments TO trades;

-- 4. Update foreign key in courses table
ALTER TABLE courses CHANGE COLUMN department_id trade_id INT;
ALTER TABLE courses DROP FOREIGN KEY courses_ibfk_2;
ALTER TABLE courses ADD CONSTRAINT courses_ibfk_2 FOREIGN KEY (trade_id) REFERENCES trades(id);

-- 5. Update foreign key in teachers table
ALTER TABLE teachers CHANGE COLUMN department_id trade_id INT;
ALTER TABLE teachers DROP FOREIGN KEY teachers_ibfk_2;
ALTER TABLE teachers ADD CONSTRAINT teachers_ibfk_2 FOREIGN KEY (trade_id) REFERENCES trades(id);

-- 6. Add role field to users table for extended roles
ALTER TABLE users MODIFY COLUMN role ENUM('admin','secretary','teacher','student','parent','discipline_master') NOT NULL;

-- 7. Create file uploads table for student registration imports
CREATE TABLE IF NOT EXISTS file_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT,
    upload_type ENUM('student_bulk','marks_import','other') DEFAULT 'other',
    status ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    error_message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- DATA MIGRATION: Rename existing department names to trades (if any)
-- ============================================================
-- This is handled separately to avoid conflicts
