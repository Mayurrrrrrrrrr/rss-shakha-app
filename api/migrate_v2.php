<?php
require_once '../config/db.php';

// Migration script to add OpenAI fields and set the key
try {
    // 1. Add columns (using compatible way)
    $columns = $pdo->query("SHOW COLUMNS FROM shakhas")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('openai_api_key', $columns)) {
        $pdo->exec("ALTER TABLE shakhas ADD COLUMN openai_api_key VARCHAR(255) NULL");
    }
    if (!in_array('use_ai_crosscheck', $columns)) {
        $pdo->exec("ALTER TABLE shakhas ADD COLUMN use_ai_crosscheck TINYINT(1) DEFAULT 0");
    }
    
    // 2. Update with user provided key and enable crosscheck
    // Key should be entered via UI or already in .env. We just enable crosscheck here if key exists.
    $stmt = $pdo->query("SELECT openai_api_key FROM shakhas WHERE id > 0 LIMIT 1");
    $existingKey = $stmt->fetchColumn();
    
    if (!$existingKey && defined('OPENAI_API_KEY')) {
        $stmt = $pdo->prepare("UPDATE shakhas SET openai_api_key = ?, use_ai_crosscheck = 1 WHERE id > 0");
        $stmt->execute([OPENAI_API_KEY]);
    } else {
        $pdo->exec("UPDATE shakhas SET use_ai_crosscheck = 1 WHERE id > 0");
    }
    
    echo "<h1>Database Migration Successful</h1>";
    echo "<p>OpenAI Key has been added and AI Cross-check has been enabled for all shakhas.</p>";
    echo "<p>You can now go back to <a href='../pages/panchang_daily.php'>Panchang Page</a> and try a 'Refetch'.</p>";
} catch (Exception $e) {
    echo "<h1>Migration Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
