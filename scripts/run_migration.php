<?php
/**
 * Run this script on the server to apply sync migration
 * Usage: php scripts/run_migration.php
 */
require_once __DIR__ . '/../config/db.php';

echo "Starting sync migration...\n";

// 1. Add updated_at to notices
try {
    $pdo->exec("ALTER TABLE notices ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    echo "[OK] notices: updated_at column added\n";
} catch (Exception $e) {
    echo "[SKIP] notices: " . $e->getMessage() . "\n";
}

// Backfill notices
try {
    $count = $pdo->exec("UPDATE notices SET updated_at = COALESCE(created_at, NOW()) WHERE updated_at IS NULL");
    echo "[OK] notices: backfilled $count rows\n";
} catch (Exception $e) {
    echo "[SKIP] notices backfill: " . $e->getMessage() . "\n";
}

// 2. Add updated_at to personalities
try {
    $pdo->exec("ALTER TABLE personalities ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    echo "[OK] personalities: updated_at column added\n";
} catch (Exception $e) {
    echo "[SKIP] personalities: " . $e->getMessage() . "\n";
}

// Add created_at to personalities
try {
    $pdo->exec("ALTER TABLE personalities ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "[OK] personalities: created_at column added\n";
} catch (Exception $e) {
    echo "[SKIP] personalities created_at: " . $e->getMessage() . "\n";
}

// Backfill personalities
try {
    $count = $pdo->exec("UPDATE personalities SET updated_at = NOW() WHERE updated_at IS NULL");
    echo "[OK] personalities: backfilled $count rows\n";
} catch (Exception $e) {
    echo "[SKIP] personalities backfill: " . $e->getMessage() . "\n";
}

// 3. Backfill events missing updated_at
try {
    $count = $pdo->exec("UPDATE events SET updated_at = COALESCE(created_at, NOW()) WHERE updated_at IS NULL");
    echo "[OK] events: backfilled $count rows\n";
} catch (Exception $e) {
    echo "[SKIP] events backfill: " . $e->getMessage() . "\n";
}

echo "\nMigration complete!\n";
