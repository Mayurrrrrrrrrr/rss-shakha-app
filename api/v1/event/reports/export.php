<?php
require_once __DIR__ . '/../../config.php';
$auth = authenticateEventRequest();
requireRole($auth, ['Admin', 'Coordinator']);

$type = $_GET['type'] ?? 'all';
$format = $_GET['format'] ?? 'json';

try {
    $data = [];
    
    if ($type === 'participants' || $type === 'all') {
        $stmt = $pdo->prepare("SELECT * FROM em_participants WHERE event_id = ?");
        $stmt->execute([$auth['event_id']]);
        $data['participants'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if ($format === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="export_'.$type.'.json"');
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    } else if ($format === 'csv' && $type === 'participants') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="participants.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Name', 'Phone', 'City', 'Age', 'Gender', 'Category', 'Group', 'Entry Type']);
        foreach ($data['participants'] as $row) {
            fputcsv($output, [$row['name'], $row['phone'], $row['city'], $row['age'], $row['gender'], $row['category'], $row['group'], $row['entry_type']]);
        }
        fclose($output);
        exit;
    } else {
        sendResponse(false, "Format/Type combination not supported yet");
    }
} catch (PDOException $e) {
    if ($format === 'json') {
        sendResponse(false, "Database error: " . $e->getMessage());
    } else {
        echo "Database error: " . $e->getMessage();
    }
}
