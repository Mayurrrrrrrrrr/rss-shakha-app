<?php
require_once "/var/www/html/sanghasthan/config/db.php";

$pdo->exec("INSERT INTO em_events (id, name, venue, start_date, end_date, status) VALUES (1, 'Test Event', 'Test Venue', '2026-08-01', '2026-08-31', 'active')");
$hash = password_hash('12345', PASSWORD_DEFAULT);
$pdo->exec("INSERT INTO em_organizers (event_id, name, phone, username, password, role) VALUES (1, 'Test Organizer', '9644771118', '9644771118', '$hash', 'admin')");
echo "Inserted test organizer!\n";
