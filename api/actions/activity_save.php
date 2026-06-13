<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';
requireLogin();
csrf_verify();
if (!isMukhyashikshak() && !isAdmin()) {
    header('Location: ../../pages/dashboard.php');
    exit;
}

$shakhaIdRaw = getCurrentShakhaId();
$shakhaId = filter_var($shakhaIdRaw, FILTER_VALIDATE_INT);
if ($shakhaId === false) {
    http_response_code(400);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Invalid input data provided.']);
    exit;
}

$inputs = getRequestInputs();
$action = isset($inputs['action']) && is_string($inputs['action']) ? trim($inputs['action']) : '';

if ($action !== 'add' && $action !== 'edit' && $action !== 'delete') {
    http_response_code(400);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Invalid input data provided.']);
    exit;
}

$id = null;
if (isset($inputs['id']) && $inputs['id'] !== '') {
    $id = filter_var($inputs['id'], FILTER_VALIDATE_INT);
    if ($id === false) {
        http_response_code(400);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Invalid input data provided.']);
        exit;
    }
}

if ($action === 'add' || $action === 'edit') {
    if (!isset($inputs['name']) || !is_string($inputs['name']) || trim($inputs['name']) === '') {
        http_response_code(400);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Invalid input data provided.']);
        exit;
    }
    $name = htmlspecialchars(trim($inputs['name']), ENT_QUOTES, 'UTF-8');
    
    $sortOrderRaw = $inputs['sort_order'] ?? 10;
    $sortOrder = filter_var($sortOrderRaw, FILTER_VALIDATE_INT);
    if ($sortOrder === false) {
        http_response_code(400);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Invalid input data provided.']);
        exit;
    }
    
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO activities (name, sort_order, shakha_id, is_active, updated_at) VALUES (?, ?, ?, 1, NOW())");
        $stmt->execute([$name, $sortOrder, $shakhaId]);
        header('Location: ../../pages/activities.php?success=गतिविधि जोड़ी गई');
    } else {
        // Edit
        if ($id === null) {
            http_response_code(400);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => 'Invalid input data provided.']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE activities SET name = ?, sort_order = ?, updated_at = NOW() WHERE id = ? AND shakha_id = ?");
        $stmt->execute([$name, $sortOrder, $id, $shakhaId]);
        header('Location: ../../pages/activities.php?success=गतिविधि अपडेट की गई');
    }
} elseif ($action === 'delete') {
    if ($id === null) {
        http_response_code(400);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Invalid input data provided.']);
        exit;
    }
    $stmt = $pdo->prepare("UPDATE activities SET is_active = 0, updated_at = NOW() WHERE id = ? AND shakha_id = ?");
    $stmt->execute([$id, $shakhaId]);
    header('Location: ../../pages/activities.php?success=गतिविधि हटा दी गई');
}
