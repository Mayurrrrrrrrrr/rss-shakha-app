<?php
require_once __DIR__ . '/../config.php';

$auth = authenticateEventRequest();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
$offset = ($page - 1) * $per_page;

$group = isset($_GET['group']) ? $_GET['group'] : null;
$category = isset($_GET['category']) ? $_GET['category'] : null;
$entry_type = isset($_GET['entry_type']) ? $_GET['entry_type'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

$query = "SELECT id, name, phone, city, age, gender, category, group_number, entry_type, notes, registered_by, is_active 
          FROM em_participants 
          WHERE event_id = :event_id AND is_deleted = 0 AND is_active = 1";
$params = [':event_id' => $auth['event_id']];

if ($group !== null && $group !== '') {
    $query .= " AND group_number = :group";
    $params[':group'] = $group;
}
if ($category !== null && $category !== '') {
    $query .= " AND category = :category";
    $params[':category'] = $category;
}
if ($entry_type !== null && $entry_type !== '') {
    $query .= " AND entry_type = :entry_type";
    $params[':entry_type'] = $entry_type;
}
if ($search !== null && $search !== '') {
    $query .= " AND (name LIKE :search1 OR phone LIKE :search2 OR city LIKE :search3)";
    $params[':search1'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
    $params[':search3'] = '%' . $search . '%';
}

$countQuery = "SELECT COUNT(*) FROM (" . $query . ") as count_table";
$stmtCount = $pdo->prepare($countQuery);
$stmtCount->execute($params);
$total = $stmtCount->fetchColumn();

$query .= " ORDER BY name ASC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_pages = ceil($total / $per_page);

sendResponse(true, "प्रतिभागी सूची सफलतापूर्वक प्राप्त की गई", [
    'participants' => $participants,
    'pagination' => [
        'total' => (int)$total,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => $total_pages
    ]
]);
