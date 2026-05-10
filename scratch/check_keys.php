<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT id, gemini_api_key, openai_api_key, use_ai_crosscheck FROM shakhas");
while ($row = $stmt->fetch()) {
    echo "Shakha ID: " . $row['id'] . "\n";
    echo "Gemini Key: " . ($row['gemini_api_key'] ? "Present" : "Missing") . "\n";
    echo "OpenAI Key: " . ($row['openai_api_key'] ? "Present" : "Missing") . "\n";
    echo "Cross-check: " . ($row['use_ai_crosscheck'] ? "On" : "Off") . "\n";
}
?>
