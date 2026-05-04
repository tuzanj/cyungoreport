-- ============================================================
-- MIGRATION: Discipline Management System
-- ============================================================

-- 1. Update users table role enum to include discipline_master
ALTER TABLE users MODIFY COLUMN role ENUM('admin','secretary','teacher','student','parent','discipline_master') NOT NULL;

-- 2. Create faults table (Predefined disciplinary issues and their points)
CREATE TABLE IF NOT EXISTS faults (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    points_deduction INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Create student_discipline table (Recording specific incidents)
CREATE TABLE IF NOT EXISTS student_discipline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fault_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    term INT NOT NULL DEFAULT 1,
    incident_date DATE NOT NULL,
    description TEXT,
    recorded_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (fault_id) REFERENCES faults(id),
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- 4. Create student_discipline_marks table (Summary per term)
CREATE TABLE IF NOT EXISTS student_discipline_marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    term INT NOT NULL DEFAULT 1,
    total_points DECIMAL(5,2) DEFAULT 40.00, -- Default start points (e.g., 40/40)
    deductions DECIMAL(5,2) DEFAULT 0.00,
    final_score DECIMAL(5,2) DEFAULT 40.00,
    recorded_by INT NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_discipline_term (student_id, academic_year_id, term),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- 5. Seed default faults
INSERT INTO faults (name, points_deduction) VALUES 
('Discipline Term Decision', 0),
('GUCIRA AHO UBONYE', 2),
('Gufatanwa Telephone mu kigo (kuyamburwa burundu)', 5),
('gufatirwa ahabujijwe mu kigo', 2);
