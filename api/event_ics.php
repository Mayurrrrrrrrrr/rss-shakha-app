<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

if (!isset($_GET['id'])) {
    die("Event ID missing.");
}

$event_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    die("Event not found.");
}

$title = $event['title'];
$desc = $event['description'];

if (!empty($event['meeting_link'])) {
    $desc .= "\\n\\nMeeting Link: " . $event['meeting_link'];
}

$location = $event['location'];

// Ensure dates are parsed correctly
$dateTimeString = $event['event_date'] . ' ' . $event['event_time'];
$startDateTime = new DateTime($dateTimeString);
// Let's assume an event lasts 1 hour by default if no end time is specified
$endDateTime = clone $startDateTime;
$endDateTime->modify('+1 hour');

$dtstart = $startDateTime->format('Ymd\THis');
$dtend = $endDateTime->format('Ymd\THis');
$dtstamp = gmdate('Ymd\THis\Z');

$uid = "event-" . $event['id'] . "-" . time() . "@" . $_SERVER['HTTP_HOST'];

$ics = "BEGIN:VCALENDAR\r\n";
$ics .= "VERSION:2.0\r\n";
$ics .= "PRODID:-//RSS Shakha App//EN\r\n";
$ics .= "CALSCALE:GREGORIAN\r\n";
$ics .= "BEGIN:VEVENT\r\n";
$ics .= "DTSTAMP:" . $dtstamp . "\r\n";
$ics .= "UID:" . $uid . "\r\n";
$ics .= "DTSTART:" . $dtstart . "\r\n";
$ics .= "DTEND:" . $dtend . "\r\n";
$ics .= "SUMMARY:" . escapeString($title) . "\r\n";
if (!empty($location)) {
    $ics .= "LOCATION:" . escapeString($location) . "\r\n";
}
if (!empty($desc)) {
    $ics .= "DESCRIPTION:" . escapeString($desc) . "\r\n";
}

// Alarm 1 Day Before
$ics .= "BEGIN:VALARM\r\n";
$ics .= "TRIGGER:-P1D\r\n";
$ics .= "ACTION:DISPLAY\r\n";
$ics .= "DESCRIPTION:Reminder\r\n";
$ics .= "END:VALARM\r\n";

// Alarm 30 Minutes Before
$ics .= "BEGIN:VALARM\r\n";
$ics .= "TRIGGER:-PT30M\r\n";
$ics .= "ACTION:DISPLAY\r\n";
$ics .= "DESCRIPTION:Reminder\r\n";
$ics .= "END:VALARM\r\n";

$ics .= "END:VEVENT\r\n";
$ics .= "END:VCALENDAR\r\n";

// Function to escape text for ICS format
function escapeString($string) {
    return preg_replace('/([\,;])/', '\\\\$1', str_replace(array("\r\n", "\n", "\r"), "\\n", $string));
}

// Set correct headers for download as inline so OS handles it natively
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="event_' . $event['id'] . '.ics"');

echo $ics;
exit;
