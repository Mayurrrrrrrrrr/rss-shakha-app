<?php
require_once __DIR__ . '/config/db.php';

$updates = [
    'प्रदिप नलावडे' => 'घाटकोपर',
    'Pradip nalavade' => 'घाटकोपर',
    'Kiran Shewani' => 'चेंबूर',
    'अशोक भास्करन' => 'घाटकोपर',
    'वाई. पी. सिंह' => 'घाटकोपर',
    'वाघजी भाई सोनी' => 'घाटकोपर',
    'श्रवण शर्मा' => 'घाटकोपर',
    'जयेश पिंगुलकर' => 'घाटकोपर',
    'पी. कुलशेखरन' => 'घाटकोपर',
    'महादेव जगदाले' => 'घाटकोपर',
    'हर्षित प्रजापति' => 'घाटकोपर',
    'हरीश प्रजापति' => 'घाटकोपर',
    'देवीकांत ठाकुर' => 'घाटकोपर',
    'दर्शन पिलणकर' => 'घाटकोपर',
    'भारत बेनीवाल' => 'घाटकोपर',
    'आशीष चौहान' => 'घाटकोपर'
];

try {
    $stmt = $pdo->prepare("UPDATE em_participants SET bhag = ? WHERE name LIKE ?");
    $total_updated = 0;
    
    foreach ($updates as $name => $bhag) {
        $stmt->execute([$bhag, '%' . $name . '%']);
        $updated = $stmt->rowCount();
        echo "Updated $updated rows for $name -> $bhag\n";
        $total_updated += $updated;
    }
    
    echo "Total rows updated: $total_updated\n";
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
