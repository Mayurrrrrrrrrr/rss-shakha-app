<?php
session_start();
require_once '../../config/db.php';
include 'includes/header.php';

try {
    $rooms = $pdo->query("SELECT * FROM em_rooms")->fetchAll();
} catch (Exception $e) {
    // Mock data if table doesn't exist
    $rooms = [
        ['id' => 1, 'room_number' => 'A-101', 'capacity' => 10, 'occupancy' => 8, 'building' => 'मुख्य भवन'],
        ['id' => 2, 'room_number' => 'A-102', 'capacity' => 10, 'occupancy' => 10, 'building' => 'मुख्य भवन'],
        ['id' => 3, 'room_number' => 'B-201', 'capacity' => 20, 'occupancy' => 5, 'building' => 'छात्रावास'],
    ];
}
?>

<h2>आवास व्यवस्था (Room Allotment)</h2>

<div class="grid">
    <?php foreach($rooms as $room): 
        $percent = ($room['capacity'] > 0) ? ($room['occupancy'] / $room['capacity']) * 100 : 0;
        $statusClass = 'status-green';
        if ($percent >= 100) $statusClass = 'status-red';
        elseif ($percent >= 70) $statusClass = 'status-yellow';
    ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between;">
            <h3 style="margin: 0; color: var(--saffron);"><?= htmlspecialchars($room['room_number']) ?></h3>
            <span style="font-size: 0.9em; color: #666;"><?= htmlspecialchars($room['building'] ?? '') ?></span>
        </div>
        <div style="margin-top: 1rem;">
            <div style="display: flex; justify-content: space-between; font-size: 0.9em;">
                <span>उपलब्धता (Occupancy)</span>
                <strong><?= $room['occupancy'] ?> / <?= $room['capacity'] ?></strong>
            </div>
            <div class="status-bar">
                <div class="status-fill <?= $statusClass ?>" style="width: <?= min($percent, 100) ?>%"></div>
            </div>
        </div>
        <div style="margin-top: 1rem; text-align: center;">
            <button class="btn btn-outline" style="width: 100%;" <?= $percent >= 100 ? 'disabled' : '' ?>>
                <?= $percent >= 100 ? 'पूर्ण (Full)' : 'आवंटित करें (Allot)' ?>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>
