<?php
require_once __DIR__ . '/../config/db.php';

$json_file = __DIR__ . '/subhashitas.json';
if (!file_exists($json_file)) {
    die("Error: subhashitas.json not found.\n");
}

$data = json_decode(file_get_contents($json_file), true);
if (!$data) {
    die("Error: Invalid JSON data.\n");
}

// Fetch the maximum subhashit_date from the table to start appending after it
$stmt = $pdo->query("SELECT MAX(subhashit_date) as max_date FROM subhashits");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$start_date = $row['max_date'];

if (!$start_date || $start_date < date('Y-m-d')) {
    $current_date = new DateTime('tomorrow');
} else {
    $current_date = new DateTime($start_date);
    $current_date->modify('+1 day');
}

$success_count = 0;
$error_count = 0;

$insert_stmt = $pdo->prepare("
    INSERT INTO subhashits 
    (shakha_id, sanskrit_text, hindi_meaning, subhashit_date, created_by, is_active, is_deleted) 
    VALUES 
    (1, :sanskrit_text, :hindi_meaning, :subhashit_date, 1, 1, 0)
");

foreach ($data as $item) {
    try {
        $insert_stmt->execute([
            ':sanskrit_text' => $item['sanskrit_text'],
            ':hindi_meaning' => $item['hindi_meaning'],
            ':subhashit_date' => $current_date->format('Y-m-d')
        ]);
        $success_count++;
        $current_date->modify('+1 day');
    } catch (PDOException $e) {
        echo "Error inserting item: " . $e->getMessage() . "\n";
        $error_count++;
    }
}

echo "Successfully imported $success_count subhashitas. Errors: $error_count.\n";
?>
