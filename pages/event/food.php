<?php
session_start();
require_once '../../config/db.php';
include 'includes/header.php';

// Logic for meals based on participant counts
$total_participants = 0;
$total_organizers = 0;
try {
    $total_participants = $pdo->query("SELECT COUNT(*) FROM em_participants")->fetchColumn() ?: 0;
    $total_organizers = $pdo->query("SELECT COUNT(*) FROM em_organizers")->fetchColumn() ?: 0;
} catch (Exception $e) {
    $total_participants = 150;
    $total_organizers = 30;
}

$base_count = $total_participants + $total_organizers;
$breakfast_buffer = ceil($base_count * 0.05);
$lunch_buffer = ceil($base_count * 0.10);
$dinner_buffer = ceil($base_count * 0.08);

$meals = [
    [
        'name' => 'अल्पाहार (Breakfast)',
        'time' => '08:00 AM - 09:30 AM',
        'base' => $base_count,
        'buffer' => $breakfast_buffer,
        'total' => $base_count + $breakfast_buffer,
        'icon' => '☕'
    ],
    [
        'name' => 'भोजन - दोपहर (Lunch)',
        'time' => '01:00 PM - 02:30 PM',
        'base' => $base_count,
        'buffer' => $lunch_buffer,
        'total' => $base_count + $lunch_buffer,
        'icon' => '🍛'
    ],
    [
        'name' => 'भोजन - रात्रि (Dinner)',
        'time' => '08:00 PM - 09:30 PM',
        'base' => $base_count,
        'buffer' => $dinner_buffer,
        'total' => $base_count + $dinner_buffer,
        'icon' => '🍽️'
    ]
];
?>

<h2>भोजन व्यवस्था (Food Count Calculator)</h2>
<p>अनुमानित उपस्थित जन: <strong><?= $base_count ?></strong> (<?= $total_participants ?> प्रतिभागी + <?= $total_organizers ?> प्रबंधक)</p>

<div class="grid">
    <?php foreach($meals as $meal): ?>
    <div class="card" style="text-align: center;">
        <div style="font-size: 3rem; margin-bottom: 0.5rem;"><?= $meal['icon'] ?></div>
        <h3 style="color: var(--saffron); margin-bottom: 0.5rem;"><?= $meal['name'] ?></h3>
        <p style="color: #666; font-size: 0.9em; margin-bottom: 1rem;"><?= $meal['time'] ?> </p>
        
        <div style="display: flex; justify-content: space-around; background: var(--cream); padding: 1rem; border-radius: 8px;">
            <div>
                <div style="font-size: 0.9em; color: #666;">मूल (Base)</div>
                <div style="font-weight: bold; font-size: 1.2em;"><?= $meal['base'] ?></div>
            </div>
            <div>
                <div style="font-size: 0.9em; color: #666;">अतिरिक्त (Buffer)</div>
                <div style="font-weight: bold; font-size: 1.2em; color: var(--amber);">+<?= $meal['buffer'] ?></div>
            </div>
            <div>
                <div style="font-size: 0.9em; color: #666;">कुल (Total)</div>
                <div style="font-weight: bold; font-size: 1.5em; color: #4caf50;"><?= $meal['total'] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>
