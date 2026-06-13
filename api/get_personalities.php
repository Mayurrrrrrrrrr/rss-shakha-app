<?php
require_once '../includes/auth.php';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, 'Invalid request method');
}

try {
    $stmt = $pdo->query("SELECT * FROM personalities ORDER BY display_order ASC, name ASC");
    $personalities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(true, 'Personalities fetched', $personalities);
} catch (Exception $e) {
    sendResponse(false, 'Database Error: ' . $e->getMessage());
}
?>
