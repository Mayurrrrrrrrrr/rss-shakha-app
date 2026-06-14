<?php
require_once '../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/sync/auth_api.php';
require_once 'config.php';

// Authenticate API Request using JWT
$userContext = authenticateAPIRequest();

try {
    // Fetch all active/non-deleted personalities
    $stmt = $pdo->query("SELECT * FROM personalities ORDER BY display_order ASC, name ASC");
    $personalities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(true, 'Personalities fetched', $personalities);
} catch (Exception $e) {
    sendResponse(false, 'Database Error: ' . $e->getMessage());
}
?>
