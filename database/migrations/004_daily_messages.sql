-- Migration: Create daily message tables
-- Version: 004

CREATE TABLE IF NOT EXISTS daily_message_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shakha_id INT NOT NULL,
    whatsapp_enabled TINYINT(1) DEFAULT 0,
    whatsapp_api_instance VARCHAR(100) DEFAULT '',
    whatsapp_api_token VARCHAR(255) DEFAULT '',
    whatsapp_group_id VARCHAR(100) DEFAULT '',
    send_time TIME DEFAULT '06:00:00',
    email_enabled TINYINT(1) DEFAULT 0,
    email_list TEXT,
    last_subhashit_id INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_shakha (shakha_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS daily_message_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shakha_id INT NOT NULL,
    message_date DATE NOT NULL,
    channel ENUM('whatsapp','email') DEFAULT 'whatsapp',
    status ENUM('success','failed') DEFAULT 'failed',
    error_message TEXT,
    image_path VARCHAR(255),
    subhashit_id INT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_shakha_date (shakha_id, message_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
