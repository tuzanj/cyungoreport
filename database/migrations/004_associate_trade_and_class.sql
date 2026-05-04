-- ============================================================
-- MIGRATION: Associate Students with Trades and Courses with Classes
-- ============================================================

-- 1. Add trade_id to students table
ALTER TABLE students ADD COLUMN trade_id INT AFTER emergency_contact;
ALTER TABLE students ADD FOREIGN KEY (trade_id) REFERENCES trades(id) ON DELETE SET NULL;

-- 2. Ensure courses can be linked to trades (already exists in schema but good to verify)
-- Courses table should already have trade_id from previous migration/schema.

-- 3. We will handle course-class association in the application logic 
-- by inserting into class_courses when a course is created.
