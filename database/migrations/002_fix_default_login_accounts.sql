-- Fix default login accounts used for initial system access
-- Default password after this migration: Admin@123

UPDATE users
SET password_hash = '$2y$12$D92.fujYzbHJzUegfpl/1eH2ntiKjHyzOLh7JQKNF5W0DE4SNkjJi',
    is_active = 1,
    failed_attempts = 0,
    locked_until = NULL
WHERE username IN ('admin', 'secretary');
