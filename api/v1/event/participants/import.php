<?php
require_once __DIR__ . '/../config.php';

$auth = authenticateEventRequest();
requireRole($auth, ['Admin']);

$total_imported = 0;
$skipped_count = 0;
$errors = [];

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("INSERT INTO em_participants 
        (event_id, name, phone, city, address, age, gender, category, group_number, notes, entry_type, registered_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pre-registered', ?)");

    $rows = [];
    if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
        $file = fopen($_FILES['file']['tmp_name'], 'r');
        $header = fgetcsv($file); // skip header
        while (($data = fgetcsv($file)) !== false) {
            // expected: name,phone,city,address,age,gender,category,group_number,notes
            if (count($data) >= 1) {
                $rows[] = [
                    'name' => $data[0] ?? '',
                    'phone' => $data[1] ?? '',
                    'city' => $data[2] ?? '',
                    'address' => $data[3] ?? '',
                    'age' => $data[4] ?? '',
                    'gender' => $data[5] ?? '',
                    'category' => $data[6] ?? '',
                    'group_number' => $data[7] ?? '',
                    'notes' => $data[8] ?? ''
                ];
            }
        }
        fclose($file);
    } else {
        $input = json_decode(file_get_contents("php://input"), true);
        if (is_array($input) && isset($input['participants']) && is_array($input['participants'])) {
            $rows = $input['participants'];
        } else if (is_array($input)) {
            $rows = $input; // root array
        }
    }

    if (empty($rows)) {
        throw new Exception("आयात करने के लिए कोई डेटा नहीं मिला");
    }

    foreach ($rows as $index => $row) {
        $name = trim($row['name'] ?? '');
        if (empty($name)) {
            $skipped_count++;
            $errors[] = "Row " . ($index + 1) . ": नाम खाली है";
            continue;
        }

        $phone = trim($row['phone'] ?? '');
        $city = trim($row['city'] ?? '');
        $address = trim($row['address'] ?? '');
        $age = isset($row['age']) && is_numeric($row['age']) && $row['age'] !== '' ? (int)$row['age'] : null;
        $gender = trim($row['gender'] ?? '');
        $category = trim($row['category'] ?? '');
        $group_number = trim($row['group_number'] ?? '');
        $notes = trim($row['notes'] ?? '');

        $stmt->execute([$auth['event_id'], $name, $phone, $city, $address, $age, $gender, $category, $group_number, $notes, $auth['organizer_id']]);
        $total_imported++;
    }

    $pdo->commit();
    sendResponse(true, "आयात पूरा हुआ", [
        'total_imported' => $total_imported,
        'skipped_count' => $skipped_count,
        'errors' => $errors
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendResponse(false, "आयात विफल: " . $e->getMessage(), [
        'total_imported' => 0,
        'skipped_count' => count($rows ?? []),
        'errors' => [$e->getMessage()]
    ]);
}
