-- ============================================================
-- SCHOOL MANAGEMENT SYSTEM - DATABASE SCHEMA
-- ============================================================

CREATE DATABASE IF NOT EXISTS school_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE school_db;

-- ============================================================
-- USERS & AUTHENTICATION
-- ============================================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','secretary','teacher','student','parent','discipline_master') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    failed_attempts INT DEFAULT 0,
    locked_until DATETIME NULL,
    last_login DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- ACADEMIC STRUCTURE
-- ============================================================

CREATE TABLE academic_years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    grade_level VARCHAR(20),
    section VARCHAR(10),
    trade_id INT,
    academic_year_id INT NOT NULL,
    max_students INT DEFAULT 40,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id),
    FOREIGN KEY (trade_id) REFERENCES trades(id) ON DELETE SET NULL
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('complementary','general','specific','co-curricular') DEFAULT 'specific',
    credits INT DEFAULT 3,
    module_weight INT DEFAULT 0,
    trade_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trade_id) REFERENCES trades(id) ON DELETE SET NULL
);

CREATE TABLE grading_criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    assignments_weight DECIMAL(5,2) DEFAULT 20.00,
    quizzes_weight DECIMAL(5,2) DEFAULT 10.00,
    midterm_weight DECIMAL(5,2) DEFAULT 30.00,
    final_weight DECIMAL(5,2) DEFAULT 40.00,
    passing_score DECIMAL(5,2) DEFAULT 50.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id)
);

-- ============================================================
-- PEOPLE
-- ============================================================

CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    gender ENUM('male','female','other'),
    date_of_birth DATE,
    phone VARCHAR(20),
    address TEXT,
    department_id INT,
    qualification VARCHAR(100),
    hire_date DATE,
    profile_photo VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    gender ENUM('male','female','other'),
    date_of_birth DATE,
    phone VARCHAR(20),
    address TEXT,
    emergency_contact VARCHAR(100),
    trade_id INT,
    profile_photo VARCHAR(255),
    enrollment_date DATE,
    status ENUM('active','suspended','graduated','withdrawn') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (trade_id) REFERENCES trades(id) ON DELETE SET NULL
);

CREATE TABLE parents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    relationship ENUM('father','mother','guardian') DEFAULT 'guardian',
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE parent_student (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NOT NULL,
    student_id INT NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    UNIQUE KEY unique_link (parent_id, student_id),
    FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
-- ENROLLMENT & SCHEDULING
-- ============================================================

CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('active','dropped','completed') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment (student_id, class_id, academic_year_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id)
);

CREATE TABLE class_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    UNIQUE KEY unique_class_course (class_id, course_id, academic_year_id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id)
);

CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_course_id INT NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_course_id) REFERENCES class_courses(id) ON DELETE CASCADE
);

-- ============================================================
-- MARKS & GRADES
-- ============================================================

CREATE TABLE assessments (
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

CREATE TABLE assessment_marks (
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

CREATE TABLE marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_course_id INT NOT NULL,
    formative_score DECIMAL(5,2) DEFAULT NULL,
    integrated_score DECIMAL(5,2) DEFAULT NULL,
    comprehensive_score DECIMAL(5,2) DEFAULT NULL,
    calculated_grade DECIMAL(5,2) DEFAULT NULL,
    letter_grade VARCHAR(5),
    status ENUM('draft','published') DEFAULT 'draft',
    is_pass TINYINT(1) DEFAULT NULL,
    is_supplementary TINYINT(1) DEFAULT 0,
    supplementary_score DECIMAL(5,2) DEFAULT NULL,
    remarks TEXT,
    published_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_mark (student_id, class_course_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_course_id) REFERENCES class_courses(id) ON DELETE CASCADE
);

CREATE TABLE grade_claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mark_id INT NOT NULL,
    student_id INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending','under_review','resolved','rejected') DEFAULT 'pending',
    response TEXT,
    resolved_by INT,
    resolved_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mark_id) REFERENCES marks(id),
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (resolved_by) REFERENCES users(id)
);

-- ============================================================
-- ATTENDANCE
-- ============================================================

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_course_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present','absent','late','excused') DEFAULT 'present',
    remarks VARCHAR(255),
    recorded_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attendance (student_id, class_course_id, date),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_course_id) REFERENCES class_courses(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- ============================================================
-- NOTIFICATIONS & MESSAGING
-- ============================================================

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info','success','warning','danger') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(150),
    body TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id)
);

-- ============================================================
-- AUDIT LOGS
-- ============================================================

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- SECRETARY RECORDS
-- ============================================================

CREATE TABLE credentials_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    sent_to VARCHAR(100),
    method ENUM('email','sms','print') DEFAULT 'email',
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
-- DEFAULT ADMIN ACCOUNT
-- password: Admin@123 (bcrypt hash)
-- ============================================================

INSERT INTO academic_years (name, start_date, end_date, is_current) VALUES
('2024-2025', '2024-09-01', '2025-06-30', 1);

INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@school.edu', '$2y$12$D92.fujYzbHJzUegfpl/1eH2ntiKjHyzOLh7JQKNF5W0DE4SNkjJi', 'admin'),
('secretary', 'secretary@school.edu', '$2y$12$D92.fujYzbHJzUegfpl/1eH2ntiKjHyzOLh7JQKNF5W0DE4SNkjJi', 'secretary');

-- NOTE: Default password for all seeded users is: Admin@123
