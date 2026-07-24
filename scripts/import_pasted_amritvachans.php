<?php
require_once __DIR__ . '/../config/db.php';

// Read JSON file
$json_file = __DIR__ . '/../pasted_amritvachans.json';
if (!file_exists($json_file)) {
    die("Error: pasted_amritvachans.json not found!\n");
}

$json_data = file_get_contents($json_file);
$vachans = json_decode($json_data, true);

if (!is_array($vachans) || empty($vachans)) {
    die("Error: No data found or invalid JSON format.\n");
}

echo "Found " . count($vachans) . " Amrit Vachans.\n";

$sql = "INSERT INTO amrit_vachan (shakha_id, content, author, vachan_date, created_by, is_active, is_deleted) 
        VALUES (:shakha_id, :content, :author, :vachan_date, :created_by, :is_active, :is_deleted)";
$stmt = $pdo->prepare($sql);

$success_count = 0;
$error_count = 0;

$shakha_id = 1;
$created_by = 1;
$is_active = 1;
$is_deleted = 0;

$start_timestamp = strtotime('+1 day');

foreach ($vachans as $index => $item) {
    $content = trim($item['content'] ?? '');
    $author = trim($item['author'] ?? '');
    
    if (empty($content)) {
        continue;
    }
    
    // Assign sequential future dates starting from tomorrow
    $vachan_date = date('Y-m-d', strtotime("+$index days", $start_timestamp));
    
    try {
        $stmt->execute([
            ':shakha_id' => $shakha_id,
            ':content' => $content,
            ':author' => $author,
            ':vachan_date' => $vachan_date,
            ':created_by' => $created_by,
            ':is_active' => $is_active,
            ':is_deleted' => $is_deleted
        ]);
        $success_count++;
    } catch (PDOException $e) {
        echo "Error inserting vachan $index: " . $e->getMessage() . "\n";
        $error_count++;
    }
}

echo "Import complete. Successfully inserted: $success_count, Errors: $error_count.\n";
?>
