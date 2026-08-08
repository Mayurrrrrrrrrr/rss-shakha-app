<?php
// Dummy session to test output
session_start();
$_SESSION['event_id'] = 1;
ob_start();
include __DIR__ . '/pages/event/analytics.php';
$html = ob_get_clean();
echo substr($html, 0, 1000) . "\n...\n" . substr($html, -1000);
