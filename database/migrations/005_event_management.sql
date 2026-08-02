-- Aayojan (Event Management) Module Migration
-- Creates all tables for event management including events, organizers, participants, attendance, meals, rooms, and schedules.

-- 1. em_events - Master event registry
CREATE TABLE IF NOT EXISTS `em_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `venue` VARCHAR(255),
    `start_date` DATE,
    `end_date` DATE,
    `status` ENUM('draft','active','completed','archived') DEFAULT 'draft',
    `created_by` INT,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. em_organizers - Organizing committee users
CREATE TABLE IF NOT EXISTS `em_organizers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(15),
    `username` VARCHAR(50),
    `password` VARCHAR(255),
    `role` ENUM('admin','coordinator','volunteer') DEFAULT 'volunteer',
    `is_active` TINYINT(1) DEFAULT 1,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_event_username` (`event_id`, `username`),
    INDEX `idx_event_id` (`event_id`),
    FOREIGN KEY (`event_id`) REFERENCES `em_events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. em_participants - Participant registry
CREATE TABLE IF NOT EXISTS `em_participants` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(15),
    `city` VARCHAR(100),
    `address` TEXT,
    `age` INT,
    `gender` ENUM('M','F','Other'),
    `category` VARCHAR(50),
    `group_number` INT DEFAULT 0,
    `notes` TEXT,
    `entry_type` ENUM('pre-registered','spot') DEFAULT 'pre-registered',
    `registered_by` INT,
    `is_active` TINYINT(1) DEFAULT 1,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_event_group` (`event_id`, `group_number`),
    INDEX `idx_event_name` (`event_id`, `name`),
    FULLTEXT INDEX `ft_name_phone_city` (`name`, `phone`, `city`),
    FOREIGN KEY (`event_id`) REFERENCES `em_events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. em_work_categories - Types of organizing work
CREATE TABLE IF NOT EXISTS `em_work_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_event_id` (`event_id`),
    FOREIGN KEY (`event_id`) REFERENCES `em_events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. em_work_assignments - Task assignments to organizers
CREATE TABLE IF NOT EXISTS `em_work_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `work_category_id` INT NOT NULL,
    `organizer_id` INT NOT NULL,
    `description` TEXT,
    `assignment_date` DATE,
    `time_slot` VARCHAR(50),
    `status` ENUM('pending','in_progress','completed') DEFAULT 'pending',
    `assigned_by` INT,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_event_organizer` (`event_id`, `organizer_id`),
    INDEX `idx_event_date` (`event_id`, `assignment_date`),
    FOREIGN KEY (`event_id`) REFERENCES `em_events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`work_category_id`) REFERENCES `em_work_categories`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`organizer_id`) REFERENCES `em_organizers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. em_attendance_sessions - Attendance checkpoints
CREATE TABLE IF NOT EXISTS `em_attendance_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `session_name` VARCHAR(100) NOT NULL,
    `session_date` DATE NOT NULL,
    `session_time` TIME,
    `description` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_event_date` (`event_id`, `session_date`),
    FOREIGN KEY (`event_id`) REFERENCES `em_events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. em_attendance_duties - Who marks attendance for which group
CREATE TABLE IF NOT EXISTS `em_attendance_duties` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `attendance_session_id` INT NOT NULL,
    `organizer_id` INT NOT NULL,
    `participant_group` INT NOT NULL,
    `assigned_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_session_organizer_group` (`attendance_session_id`, `organizer_id`, `participant_group`),
    FOREIGN KEY (`event_id`) REFERENCES `em_events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`attendance_session_id`) REFERENCES `em_attendance_sessions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`organizer_id`) REFERENCES `em_organizers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. em_participant_attendance - Actual attendance records
CREATE TABLE IF NOT EXISTS `em_participant_attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `attendance_session_id` INT NOT NULL,
    `participant_id` INT NOT NULL,
    `is_present` TINYINT(1) DEFAULT 0,
    `marked_by` INT,
    `marked_at` TIMESTAMP NULL,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_session_participant` (`attendance_session_id`, `participant_id`),
    INDEX `idx_event_session` (`event_id`, `attendance_session_id`),
    FOREIGN KEY (`event_id`) REFERENCES `em_events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`attendance_session_id`) REFERENCES `em_attendance_sessions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`participant_id`) REFERENCES `em_participants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. em_rooms - Room inventory
CREATE TABLE IF NOT EXISTS `em_rooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `room_name` VARCHAR(50) NOT NULL,
    `room_type` VARCHAR(50),
    `capacity` INT DEFAULT 0,
    `floor` VARCHAR(20),
    `building` VARCHAR(100),
    `notes` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_event_id` (`event_id`),
    FOREIGN KEY (`event_id`) REFERENCES `em_events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. em_room_allotments - Room assignments for BOTH participants and organizers
CREATE TABLE IF NOT EXISTS `em_room_allotments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `room_id` INT NOT NULL,
    `allottee_type` ENUM('participant','organizer') NOT NULL,
    `allottee_id` INT NOT NULL,
    `allotted_by` INT,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_room_allottee` (`room_id`, `allottee_type`, `allottee_id`),
    INDEX `idx_event_room` (`event_id`, `room_id`),
    FOREIGN KEY (`event_id`) REFERENCES `em_events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`room_id`) REFERENCES `em_rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. em_meals - Meal schedule
CREATE TABLE IF NOT EXISTS `em_meals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `meal_name` VARCHAR(50) NOT NULL,
    `meal_date` DATE NOT NULL,
    `meal_time` TIME,
    `expected_count` INT DEFAULT 0,
    `actual_count` INT DEFAULT 0,
    `expected_upcoming` INT DEFAULT 0,
    `notes` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_event_date` (`event_id`, `meal_date`),
    FOREIGN KEY (`event_id`) REFERENCES `em_events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. em_meal_tracking - Per-person per-meal tracking
CREATE TABLE IF NOT EXISTS `em_meal_tracking` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `meal_id` INT NOT NULL,
    `person_type` ENUM('participant','organizer') NOT NULL,
    `person_id` INT NOT NULL,
    `status` ENUM('opted','consumed','skipped') DEFAULT 'opted',
    `marked_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_meal_person` (`meal_id`, `person_type`, `person_id`),
    INDEX `idx_event_meal` (`event_id`, `meal_id`),
    FOREIGN KEY (`event_id`) REFERENCES `em_events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`meal_id`) REFERENCES `em_meals`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. em_schedule - Event timetable / activity schedule
CREATE TABLE IF NOT EXISTS `em_schedule` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `activity_name` VARCHAR(200) NOT NULL,
    `activity_date` DATE NOT NULL,
    `start_time` TIME,
    `end_time` TIME,
    `venue` VARCHAR(200),
    `responsible_organizer_id` INT,
    `description` TEXT,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_event_date` (`event_id`, `activity_date`),
    FOREIGN KEY (`event_id`) REFERENCES `em_events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`responsible_organizer_id`) REFERENCES `em_organizers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. em_login_attempts - Rate limiting for event organizer logins
CREATE TABLE IF NOT EXISTS `em_login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip` VARCHAR(45) NOT NULL,
    `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip_attempted_at` (`ip`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
