<?php
require_once '../includes/auth.php';
/**
 * Share Content API
 * Allows Super Admin to share content (Subhashit, Geet, Ghoshnayein) across multiple Shakhas.
 */
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied. Super Admin access required.']);
    exit;
}

$contentType = $_POST['content_type'] ?? '';
$sourceId = intval($_POST['source_id'] ?? 0);
$targetShakhas = json_decode($_POST['target_shakhas'] ?? '[]', true);

if (!$contentType || !$sourceId || empty($targetShakhas)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

try {
    $pdo->beginTransaction();

    $successCount = 0;

    if ($contentType === 'subhashit') {
        $stmt = $pdo->prepare("SELECT * FROM subhashits WHERE id = ?");
        $stmt->execute([$sourceId]);
        $sourceData = $stmt->fetch();

        if ($sourceData) {
            $insertStmt = $pdo->prepare("INSERT INTO subhashits (shakha_id, sanskrit_text, hindi_meaning, shabdarth, subhashit_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($targetShakhas as $tShakhaId) {
                // Don't copy to itself if it's somehow in the list
                if ($tShakhaId == $sourceData['shakha_id']) continue;
                
                $insertStmt->execute([
                    $tShakhaId,
                    $sourceData['sanskrit_text'],
                    $sourceData['hindi_meaning'],
                    $sourceData['shabdarth'],
                    $sourceData['subhashit_date'],
                    $_SESSION['user_id']
                ]);
                $successCount++;
            }
        }
    } elseif ($contentType === 'geet') {
        $stmt = $pdo->prepare("SELECT * FROM geet WHERE id = ?");
        $stmt->execute([$sourceId]);
        $sourceData = $stmt->fetch();

        if ($sourceData) {
            $insertStmt = $pdo->prepare("INSERT INTO geet (shakha_id, title, geet_type, lyrics, meaning_or_context, geet_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($targetShakhas as $tShakhaId) {
                if ($tShakhaId == $sourceData['shakha_id']) continue;
                
                $insertStmt->execute([
                    $tShakhaId,
                    $sourceData['title'],
                    $sourceData['geet_type'],
                    $sourceData['lyrics'],
                    $sourceData['meaning_or_context'],
                    $sourceData['geet_date'],
                    $_SESSION['user_id']
                ]);
                $successCount++;
            }
        }
    } elseif ($contentType === 'ghoshna') {
        $stmt = $pdo->prepare("SELECT * FROM ghoshnayein WHERE id = ?");
        $stmt->execute([$sourceId]);
        $sourceData = $stmt->fetch();

        if ($sourceData) {
            $insertStmt = $pdo->prepare("INSERT INTO ghoshnayein (shakha_id, slogan_sanskrit, slogan_hindi, context, ghoshna_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($targetShakhas as $tShakhaId) {
                if ($tShakhaId == $sourceData['shakha_id']) continue;
                
                $insertStmt->execute([
                    $tShakhaId,
                    $sourceData['slogan_sanskrit'],
                    $sourceData['slogan_hindi'],
                    $sourceData['context'],
                    $sourceData['ghoshna_date'],
                    $_SESSION['user_id']
                ]);
                $successCount++;
            }
        }
    } else {
        throw new Exception("Invalid content type");
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Successfully shared to $successCount shakha(s)!"]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
