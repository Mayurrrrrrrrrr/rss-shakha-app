<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/sync/auth_api.php';
/**
 * Local Panchang API
 * ----------------------------
 * Calculates daily Tithi, Paksha, and Hindu Month using Surya Siddhanta approximation.
 */
header('Content-Type: application/json; charset=UTF-8');
require_once '../includes/PanchangCalculator.php';

$userContext = authenticateAPIRequest();

$date = $_GET['date'] ?? date('Y-m-d');

try {
    $calc = new PanchangCalculator();
    $result = $calc->getPanchang($date);

    // Map the output to match what the frontend expects
    echo json_encode([
        'status' => 'success',
        'date' => $date,
        'cached' => false,
        'panchang' => [
            'tithi' => $result['tithi'],
            'paksha' => $result['paksha'],
            'vikram_month' => $result['maah'],
            'shaka_month' => $result['maah'], // Same month generally
            'vikram_samvat' => $result['vikram_samvat'],
            'shaka_samvat' => $result['shak_samvat']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
