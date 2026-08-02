<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['event_user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: login.php');
    exit;
}

$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/pages/event/';
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>आयोजन - Aayojan Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --saffron: #FF6B00;
            --amber: #FFB300;
            --bg-color: #0F0F14;
            --container-bg: #1A1A24;
            --card-bg: #22222E;
            --text-color: #F5F5F5;
            --input-bg: #2A2A38;
            --border-color: #333344;
        }
        body {
            font-family: 'Noto Sans Devanagari', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }
        .navbar {
            background-color: var(--saffron);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }
        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        .nav-links a:hover {
            color: var(--amber);
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        .btn {
            background-color: var(--saffron);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn:hover {
            background-color: #e66000;
        }
        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--saffron);
            color: var(--saffron);
        }
        .btn-outline:hover {
            background-color: var(--saffron);
            color: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            color: var(--text-color);
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        th {
            background-color: var(--container-bg);
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .form-control {
            width: 100%;
            padding: 0.5rem;
            background-color: var(--input-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-family: inherit;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--saffron);
        }
        .status-bar {
            height: 10px;
            border-radius: 5px;
            background: #eee;
            margin-top: 5px;
            overflow: hidden;
        }
        .status-fill {
            height: 100%;
            border-radius: 5px;
        }
        .status-green { background: #4caf50; }
        .status-yellow { background: #ffeb3b; }
        .status-red { background: #f44336; }
    </style>
</head>
<body>
<?php if (isset($_SESSION['event_user_id'])): ?>
    <nav class="navbar">
        <a href="dashboard.php" class="navbar-brand">आयोजन</a>
        <div class="nav-links">
            <a href="dashboard.php">डैशबोर्ड (Dashboard)</a>
            <a href="participants.php">प्रतिभागी (Participants)</a>
            <a href="rooms.php">आवास (Rooms)</a>
            <a href="food.php">भोजन (Food)</a>
            <a href="schedule.php">अनुसूची (Schedule)</a>
            <a href="attendance.php">हाजिरी (Attendance)</a>
            <a href="tasks.php">कार्य (Tasks)</a>
            <?php if (isset($_SESSION['event_role']) && $_SESSION['event_role'] === 'admin'): ?>
            <a href="organizers.php">आयोजक (Organizers)</a>
            <?php endif; ?>
            <a href="logout.php">लॉगआउट (Logout)</a>
        </div>
    </nav>
<?php endif; ?>
<div class="container">
