<?php
/**
 * Standalone Offline Fallback Page
 */
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ऑफ़लाइन | संघस्थान</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/home.css">
    <style>
        body {
            margin: 0;
            font-family: 'Noto Sans Devanagari', sans-serif;
            background: #FAFAFA;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .offline-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }
        .offline-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #FF6B00;
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        p {
            font-size: 1.1rem;
            max-width: 500px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn-retry {
            background: #FF6B00;
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(255,107,0,0.3);
        }
        .btn-retry:hover {
            background: #e65c00;
            transform: translateY(-2px);
        }
        .cached-links {
            margin-top: 40px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .cached-link {
            padding: 10px 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            color: #FF6B00;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        .cached-link:hover {
            border-color: #FF6B00;
            background: #fff5eb;
        }
    </style>
</head>
<body>
    <nav class="home-nav" style="background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: relative;">
        <div class="nav-inner" style="justify-content: center;">
            <a href="/home.php" class="nav-logo-link">
                <img src="/assets/images/flag_icon.png" alt="ध्वज" class="nav-flag">
                <span class="nav-title">संघस्थान</span>
            </a>
        </div>
    </nav>

    <main class="offline-container">
        <div class="offline-icon">📡</div>
        <h1>आप ऑफ़लाइन हैं</h1>
        <p>ऐसा लगता है कि आपका इंटरनेट कनेक्शन टूट गया है। कृपया अपना नेटवर्क जांचें और पुनः प्रयास करें।</p>
        
        <button onclick="window.location.reload()" class="btn-retry">पुनः प्रयास करें</button>

        <div class="cached-links">
            <!-- These are likely cached if the user visited the home page -->
            <a href="/ekatmata-stotra/" class="cached-link">एकात्मता स्तोत्र (यदि कैश हो)</a>
            <a href="/prarthna/" class="cached-link">प्रार्थना (यदि कैश हो)</a>
            <a href="/home.php" class="cached-link">मुखपृष्ठ (यदि कैश हो)</a>
        </div>
    </main>
</body>
</html>
