<?php
require_once __DIR__ . '/config/db.php';
$event_id = 1;
try {
    $queries = [
        "cat" => "SELECT COALESCE(category, 'अज्ञात') as category, COUNT(*) as cnt FROM em_participants WHERE event_id = ? GROUP BY category ORDER BY cnt DESC",
        "age" => "SELECT COALESCE(age_group, 'अज्ञात') as age_group, COUNT(*) as cnt FROM em_participants WHERE event_id = ? GROUP BY age_group ORDER BY cnt DESC",
        "shikshan" => "SELECT COALESCE(sangh_shikshan, 'अज्ञात') as sangh_shikshan, COUNT(*) as cnt FROM em_participants WHERE event_id = ? GROUP BY sangh_shikshan ORDER BY cnt DESC",
        "bhag" => "SELECT COALESCE(bhag, city, 'अज्ञात') as loc, COUNT(*) as cnt FROM em_participants WHERE event_id = ? GROUP BY loc ORDER BY cnt DESC LIMIT 15",
        "org" => "SELECT COALESCE(organization, 'अज्ञात') as organization, COUNT(*) as cnt FROM em_participants WHERE event_id = ? GROUP BY organization ORDER BY cnt DESC LIMIT 10"
    ];
    
    foreach ($queries as $key => $sql) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$event_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $json = json_encode($data);
        if ($json === false) {
            echo "FAILED ON $key: " . json_last_error_msg() . "\n";
        } else {
            echo "SUCCESS $key\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
