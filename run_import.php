<?php
require_once "/var/www/html/sanghasthan/config/db.php";
$sql = file_get_contents('/tmp/import_participants_full.sql');
try {
    $pdo->exec($sql);
    echo "Successfully imported.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
