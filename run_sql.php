<?php
require_once "/var/www/html/sanghasthan/config/db.php";
$sql = file_get_contents("/tmp/import_participants.sql");
$pdo->exec($sql);
echo "Import successful!";
