<?php
require_once __DIR__ . '/../config/db.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS panchang_data (
        panchang_date DATE PRIMARY KEY,
        tithi VARCHAR(50),
        paksha VARCHAR(30),
        nakshatra VARCHAR(50),
        chandra_rashi VARCHAR(30),
        vikram_month VARCHAR(50),
        vikram_samvat INT,
        shaka_samvat INT,
        yugabdha INT,
        sunrise VARCHAR(20) DEFAULT '06:00 AM',
        sunset VARCHAR(20) DEFAULT '06:30 PM',
        utsav VARCHAR(255) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($sql);
    echo "Table panchang_data created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating panchang_data table: " . $e->getMessage() . "\n";
}
?>
