<?php
/**
 * Database Update Script
 * Run this script ONCE from your browser: http://localhost/.../update_db.php
 */
require_once 'config/db.php';

try {
    try {
        $pdo->exec("ALTER TABLE swayamsevaks ADD COLUMN category VARCHAR(50) DEFAULT 'Tarun'");
        echo "<p>✅ Added 'category' column to 'swayamsevaks'</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) throw $e;
        echo "<p>ℹ️ 'category' column already exists in 'swayamsevaks'</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE daily_records ADD COLUMN shaka_samvat VARCHAR(50) DEFAULT NULL AFTER vikram_samvat");
        echo "<p>✅ Added 'shaka_samvat' column to 'daily_records'</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) throw $e;
        echo "<p>ℹ️ 'shaka_samvat' column already exists in 'daily_records'</p>";
    }

    // Create Events table
    $pdo->exec("CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shakha_id INT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        event_date DATE NOT NULL,
        event_time TIME NOT NULL,
        location VARCHAR(255),
        meeting_link VARCHAR(500),
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (shakha_id) REFERENCES shakhas(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "<p>✅ Setup 'events' table</p>";

    echo "<h1>✅ Database Updated Successfully!</h1>";
    echo "<p><a href='pages/dashboard.php'>Go back to Dashboard</a></p>";
} catch (PDOException $e) {
    echo "<h1>❌ Error Updating Database</h1>";
    echo "<p>Error details: " . htmlspecialchars($e->getMessage()) . "</p>";
}
