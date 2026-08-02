<?php
require_once __DIR__ . '/PanchangHelper.php';
require_once __DIR__ . '/PanchangCalculator.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("
    CREATE TABLE panchang_data (
        panchang_date TEXT PRIMARY KEY,
        tithi TEXT, paksha TEXT, nakshatra TEXT, chandra_rashi TEXT,
        vikram_month TEXT, amant_month TEXT, vikram_samvat TEXT,
        shaka_samvat TEXT, yugabdha TEXT, sunrise TEXT, sunset TEXT,
        utsav TEXT, yoga TEXT, karana TEXT, rahukaal TEXT,
        chandra_udaya TEXT, chandra_asta TEXT, shubh_muhurt TEXT
    )
");

// Test cases
$dates = [
    '2026-08-02', // Today
    '2026-05-18', // In Adhik Jyeshtha
    '2026-06-20', // In Nija Jyeshtha
    '2023-08-01', // In Adhik Shravana
];

echo "Panchang Upgrade Test\n";
echo "=====================\n\n";

foreach ($dates as $date) {
    $result = PanchangHelper::getForDate($pdo, $date);
    echo "Date: $date\n";
    echo "Tithi: " . $result['tithi'] . " (" . $result['paksha'] . ")\n";
    echo "Maah Purnimant: " . $result['vikram_month'] . "\n";
    echo "Maah Amant: " . $result['amant_month'] . "\n";
    echo "Nakshatra: " . $result['nakshatra'] . "\n";
    echo "Yoga: " . $result['yoga'] . "\n";
    echo "Karana: " . $result['karana'] . "\n";
    echo "Samvat: " . $result['vikram_samvat'] . "\n";
    echo "---------------------\n";
}
