<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once BASE_PATH . '/config/db.php';
\App\Core\DB::init($pdo);

$jsonFile = BASE_PATH . '/extracted_subhashitas.json';
if (!file_exists($jsonFile)) {
    die("Error: JSON file not found.\n");
}

$data = json_decode(file_get_contents($jsonFile), true);
if (!$data) {
    die("Error: Invalid JSON data.\n");
}

echo "Starting import of " . count($data) . " subhashitas...\n";

// Ensure shakha_id = 1 exists as target. The requirements don't specify, but I'll use 1 or NULL if it's universal.
// But the schema says shakha_id INT NOT NULL. So I'll default to 1.
$shakhaId = 1;

$stmt = $pdo->prepare("
    INSERT INTO subhashits (shakha_id, sanskrit_text, hindi_meaning, shabdarth, subhashit_date, created_by, is_active, is_deleted)
    VALUES (?, ?, ?, ?, ?, ?, 1, 0)
");

// Let's generate sequential dates starting from tomorrow, or simply leave subhashit_date to today since it's required (NO NULL).
// In subhashit.php, they are randomly picked or ordered, so date can be current date.
$today = date('Y-m-d');
$count = 0;
$errors = 0;

foreach ($data as $item) {
    $sanskrit = $item['sanskrit'] ?? '';
    $hindi = $item['hindi'] ?? '';
    $shabdarth = isset($item['shabdarth']) ? json_encode($item['shabdarth'], JSON_UNESCAPED_UNICODE) : null;
    
    if (empty($sanskrit)) continue;

    try {
        $stmt->execute([
            $shakhaId,
            $sanskrit,
            $hindi,
            $shabdarth,
            $today,
            1 // created_by
        ]);
        $count++;
    } catch (\PDOException $e) {
        echo "Error inserting item {$item['id']}: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "Import Complete. Successfully inserted: $count, Errors: $errors\n";
