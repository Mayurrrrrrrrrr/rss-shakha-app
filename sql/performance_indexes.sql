-- Run once on your database
ALTER TABLE admin_users 
    ADD INDEX IF NOT EXISTS idx_username (username);

ALTER TABLE swayamsevaks 
    ADD INDEX IF NOT EXISTS idx_username_active (username, is_active);

ALTER TABLE daily_records 
    ADD INDEX IF NOT EXISTS idx_date_shakha (record_date, shakha_id);

ALTER TABLE login_attempts 
    ADD INDEX IF NOT EXISTS idx_ip_time (ip, attempted_at);
