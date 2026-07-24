<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->prepare("UPDATE amrit_vachan SET content = 'ज्ञान तभी सार्थक है जब वह समाज के कल्याण में लगे।' WHERE id = 140");
$stmt->execute();
echo "Updated ID 140 successfully.";
