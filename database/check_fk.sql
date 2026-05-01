-- Diagnostic script to find actual foreign key constraint names
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'school_db'
AND (TABLE_NAME IN ('courses', 'teachers') 
     AND COLUMN_NAME IN ('department_id'))
ORDER BY TABLE_NAME, CONSTRAINT_NAME;
