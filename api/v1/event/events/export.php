<?php
require_once __DIR__ . '/../config.php';

$auth = authenticateEventRequest();
requireRole($auth, ['admin', 'coordinator']);

$event_id = $_GET['event_id'] ?? $auth['event_id'];
$format = $_GET['format'] ?? 'json';

if ($auth['role'] !== 'admin' && $event_id != $auth['event_id']) {
    sendResponse(false, "Unauthorized access to this event");
}

try {
    $exportData = [];
    
    $stmt = $pdo->prepare("SELECT * FROM em_events WHERE id = ? AND is_deleted = 0");
    $stmt->execute([$event_id]);
    $exportData['event'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT id, name, phone, role FROM em_organizers WHERE event_id = ? AND is_deleted = 0");
    $stmt->execute([$event_id]);
    $exportData['organizers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT id, name, phone, district, mandal FROM em_participants WHERE event_id = ? AND is_deleted = 0");
    $stmt->execute([$event_id]);
    $exportData['participants'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch logic for other tables like em_attendance, em_room_allotments, etc. would go here...
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="participants.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Name', 'Phone', 'District', 'Mandal']); 
        foreach ($exportData['participants'] as $p) {
            fputcsv($output, [$p['id'], $p['name'], $p['phone'], $p['district'], $p['mandal']]);
        }
        fclose($output);
        exit;
    }
    
    sendResponse(true, "Event data exported successfully", $exportData);
} catch (PDOException $e) {
    sendResponse(false, "Database error: " . $e->getMessage());
}
