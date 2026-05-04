-- ============================================================
-- MIGRATION: Associate Students with Trades and Courses with Classes
-- ============================================================

-- 1. Add trade_id to students table (if not exists)
ALTER TABLE students ADD COLUMN IF NOT EXISTS trade_id INT AFTER emergency_contact;
ALTER TABLE students ADD CONSTRAINT fk_students_trade FOREIGN KEY IF NOT EXISTS (trade_id) REFERENCES trades(id) ON DELETE SET NULL;

-- 2. Ensure courses can be linked to trades (already exists in schema but good to verify)
-- Courses table should already have trade_id from previous migration/schema.

-- 3. Add trade_id to classes table
ALTER TABLE classes ADD COLUMN IF NOT EXISTS trade_id INT AFTER section;
ALTER TABLE classes ADD CONSTRAINT fk_classes_trade FOREIGN KEY (trade_id) REFERENCES trades(id) ON DELETE SET NULL;

-- 4. Course-class association logic is handled in AdminController.
