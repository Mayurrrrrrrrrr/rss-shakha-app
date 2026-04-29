<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS geet (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shakha_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        geet_type ENUM('Ekal', 'Sanghik') NOT NULL DEFAULT 'Sanghik',
        lyrics TEXT NOT NULL,
        meaning_or_context TEXT,
        geet_date DATE NOT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (shakha_id) REFERENCES shakhas(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table 'geet' created/verified.\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS ghoshnayein (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shakha_id INT NOT NULL,
        slogan_sanskrit TEXT,
        slogan_hindi TEXT,
        context TEXT,
        ghoshna_date DATE NOT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (shakha_id) REFERENCES shakhas(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table 'ghoshnayein' created/verified.\n";

} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
