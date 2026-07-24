<?php
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../config/db.php';
\App\Core\DB::init($pdo);

$json = file_get_contents(__DIR__ . '/../amrit_vachans_corrected.json');
$vachans = json_decode($json, true);

if (!$vachans) {
    die("Error parsing JSON\n");
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("UPDATE amrit_vachan SET content = ?, author = ? WHERE id = ?");
    $updatedCount = 0;
    foreach ($vachans as $v) {
        if (!empty($v['id']) && isset($v['content']) && isset($v['author'])) {
            $stmt->execute([$v['content'], $v['author'], $v['id']]);
            $updatedCount++;
        }
    }
    $pdo->commit();
    echo "Successfully updated $updatedCount Amrit Vachans.\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error updating database: " . $e->getMessage() . "\n";
}
