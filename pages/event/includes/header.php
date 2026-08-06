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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        :root {
            /* Premium Dark Theme Colors */
            --saffron: #F97316;
            --saffron-dark: #EA580C;
            --bg-color: #0B0E14; /* Deep, rich background */
            --container-bg: #151821;
            --card-bg: #1A1D27;
            --text-color: #F8FAFC;
            --text-muted: #94A3B8;
            --input-bg: #151821;
            --border-color: #2D3748;
            --success: #10B981;
            --danger: #EF4444;
            --radius-md: 12px;
            --radius-lg: 16px;
        }
        body {
            font-family: 'Noto Sans Devanagari', 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        
        /* Modern Navbar with Glassmorphism */
        .navbar {
            background: rgba(26, 29, 39, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            letter-spacing: 0.5px;
        }
        .navbar-brand::before {
            content: '';
            display: inline-block;
            width: 12px;
            height: 12px;
            background: linear-gradient(135deg, var(--saffron), var(--saffron-dark));
            border-radius: 50%;
            box-shadow: 0 0 12px rgba(249, 115, 22, 0.5);
        }
        .menu-toggle {
            display: none;
            font-size: 1.8rem;
            background: none;
            border: none;
            color: var(--text-color);
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .menu-toggle:hover {
            color: var(--saffron);
        }
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        .nav-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            padding: 0.5rem 0;
            position: relative;
        }
        .nav-links a:hover, .nav-links a.active {
            color: var(--text-color);
        }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--saffron);
            transition: width 0.3s ease;
            border-radius: 2px;
        }
        .nav-links a:hover::after, .nav-links a.active::after {
            width: 100%;
        }
        @media (max-width: 768px) {
            .navbar { padding: 1rem; }
            .menu-toggle { display: block; }
            .nav-links {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: rgba(26, 29, 39, 0.95);
                backdrop-filter: blur(16px);
                border-bottom: 1px solid var(--border-color);
                padding: 1.5rem;
                gap: 1.25rem;
                box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            }
            .nav-links.active { display: flex; }
            .nav-links a {
                width: 100%;
                padding: 0.5rem 0;
            }
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        /* Premium Cards */
        .card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.03);
            border-radius: var(--radius-lg);
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.3);
            padding: 2rem;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card h2, .card h3 {
            margin-top: 0;
            color: var(--text-color);
            font-weight: 600;
            letter-spacing: 0.2px;
        }
        
        /* Refined Grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        /* Elegant Buttons */
        .btn {
            background: linear-gradient(135deg, var(--saffron), var(--saffron-dark));
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25);
            letter-spacing: 0.5px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(234, 88, 12, 0.4);
        }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-color);
            box-shadow: none;
        }
        .btn-outline:hover {
            background: var(--container-bg);
            border-color: var(--text-muted);
            transform: translateY(-2px);
        }
        
        /* Polished Tables */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            margin-top: 1.5rem;
            background: var(--container-bg);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-color);
            min-width: 800px;
            font-size: 0.95rem;
        }
        th, td {
            padding: 1rem 1.25rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        th {
            background: rgba(255,255,255,0.02);
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        tbody tr {
            transition: background 0.2s ease;
        }
        tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Refined Forms */
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: var(--input-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--saffron);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
            background-color: var(--container-bg);
        }
        
        /* Status Elements */
        .status-bar {
            height: 8px;
            border-radius: 4px;
            background: var(--input-bg);
            margin-top: 8px;
            overflow: hidden;
        }
        .status-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, var(--saffron), var(--saffron-dark));
        }
    </style>
</head>
<body>
<?php if (isset($_SESSION['event_user_id'])): ?>
    <?php 
        $vyavastha = $_SESSION['event_vyavastha'] ?? 'all';
        $role = $_SESSION['event_role'] ?? '';
    ?>
    <nav class="navbar">
        <a href="dashboard.php" class="navbar-brand">आयोजन</a>
        <button class="menu-toggle" aria-label="Toggle navigation" id="mobile-menu-btn">☰</button>
        <div class="nav-links" id="nav-links">
            <?php if ($role === 'admin' || $vyavastha === 'all'): ?>
                <a href="dashboard.php">डैशबोर्ड (Dashboard)</a>
                <a href="participants.php">प्रतिभागी (Participants)</a>
                <a href="rooms.php">आवास (Rooms)</a>
                <a href="food.php">भोजन (Food)</a>
                <a href="schedule.php">अनुसूची (Schedule)</a>
                <a href="attendance.php">हाजिरी (Attendance)</a>
                <a href="tasks.php">कार्य (Tasks)</a>
                <?php if ($role === 'admin'): ?>
                <a href="organizers.php">आयोजक (Organizers)</a>
                <a href="room_inventory.php">कक्ष सूची (Room Inventory)</a>
                <?php endif; ?>
                <a href="logout.php">लॉगआउट (Logout)</a>
            <?php elseif ($vyavastha === 'hajiri'): ?>
                <a href="dashboard.php">डैशबोर्ड (Dashboard)</a>
                <a href="attendance.php">हाजिरी (Attendance)</a>
                <a href="logout.php">लॉगआउट (Logout)</a>
            <?php elseif ($vyavastha === 'bhojan'): ?>
                <a href="dashboard.php">डैशबोर्ड (Dashboard)</a>
                <a href="food.php">भोजन (Food)</a>
                <a href="logout.php">लॉगआउट (Logout)</a>
            <?php elseif ($vyavastha === 'nivas'): ?>
                <a href="dashboard.php">डैशबोर्ड (Dashboard)</a>
                <a href="rooms.php">आवास (Rooms)</a>
                <a href="room_inventory.php">कक्ष सूची (Room Inventory)</a>
                <a href="logout.php">लॉगआउट (Logout)</a>
            <?php else: ?>
                <a href="dashboard.php">डैशबोर्ड (Dashboard)</a>
                <a href="attendance.php">हाजिरी (Attendance)</a>
                <a href="logout.php">लॉगआउट (Logout)</a>
            <?php endif; ?>
        </div>
    </nav>
<?php endif; ?>
<div class="container">
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuBtn = document.getElementById('mobile-menu-btn');
        const navLinks = document.getElementById('nav-links');
        
        if (menuBtn && navLinks) {
            menuBtn.addEventListener('click', function() {
                navLinks.classList.toggle('active');
            });
        }

        // Auto-wrap tables for mobile responsiveness
        const tables = document.querySelectorAll('table');
        tables.forEach(table => {
            if (!table.parentElement.classList.contains('table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
    });
</script>
