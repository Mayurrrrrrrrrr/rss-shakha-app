<?php
require_once __DIR__ . '/auth.php';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
require_once __DIR__ . '/../config/db.php';

// Fetch current shakha name if logged in
$currentShakhaName = 'संघस्थान';
if (isLoggedIn() && isset($_SESSION['shakha_id'])) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT name FROM shakhas WHERE id = ?");
        $stmt->execute([$_SESSION['shakha_id']]);
        $res = $stmt->fetchColumn();
        if ($res) {
                $currentShakhaName = $res;
        }
}
?>
<!DOCTYPE html>
<html lang="hi">

<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="आर.एस.एस. संघस्थान - दैनिक गतिविधि एवं उपस्थिति प्रबंधन">
        <meta property="og:title" content="संघस्थान दैनिक रिपोर्ट">
        <meta property="og:image" content="https://sanghasthan.yuktaa.com/assets/images/shakha_preview.jpg">
        <meta property="og:description" content="आज की शाखा की जानकारी एवं उपस्थिति">
        <meta property="og:type" content="website">
        <title>संघस्थान
                <?php echo isset($pageTitle) ? ' - ' . $pageTitle : ''; ?>
        </title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@300;400;500;600;700&display=swap"
                rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo substr(md5_file(__DIR__.'/../assets/css/style.css'), 0, 8); ?>">
        <link rel="icon" href="../assets/images/favicon.png" type="image/png">
</head>

<body>
        <?php if (isLoggedIn()): ?>
                <nav class="navbar">
                        <div class="nav-container">
                                <button class="hamburger-btn" id="sidebarToggle" aria-label="मेनू">
                                        <span></span><span></span><span></span>
                                </button>
                                
                                <a href="../pages/dashboard.php" class="nav-brand">
                                        <?php if (!empty($shakhaLogoImg) && file_exists("../" . $shakhaLogoImg)): ?>
                                                <img src="../<?php echo htmlspecialchars($shakhaLogoImg); ?>" alt="शाखा" class="nav-logo" loading="lazy">
                                        <?php else: ?>
                                                <img src="../assets/images/logo.svg" alt="शाखा" class="nav-logo" loading="lazy">
                                        <?php endif; ?>
                                        <div class="brand-text">
                                                <div class="brand-title">संघस्थान</div>
                                                <?php if ($currentShakhaName !== 'संघस्थान'): ?>
                                                        <div class="brand-subtitle"><?php echo htmlspecialchars($currentShakhaName); ?></div>
                                                <?php else: ?>
                                                        <div class="brand-subtitle">शाखा प्रबंधन</div>
                                                <?php endif; ?>
                                        </div>
                                </a>
                                
                                <div id="google_translate_element" class="nav-translate"></div>
                        </div>
                </nav>

                <!-- SIDEBAR OVERLAY -->
                <div class="sidebar-overlay" id="sidebarOverlay"></div>

                <!-- LEFT SIDEBAR -->
                <aside class="sidebar" id="sidebar">
                        <div class="sidebar-header">
                                <div class="sidebar-brand">
                                        <img src="../assets/images/flag_icon.png" class="brand-icon" alt="🚩" loading="lazy"> संघस्थान
                                </div>
                                <button class="close-sidebar" id="closeSidebar">&times;</button>
                        </div>
                        <ul class="sidebar-menu">
                                <?php if (isAdmin()): ?>
                                        <li><a href="../pages/admin_dashboard.php" class="<?php echo $currentPage === 'admin_dashboard' ? 'active' : ''; ?>">👑 एडमिन डैशबोर्ड</a></li>
                                        
                                        <?php $adminOpsActive = in_array($currentPage, ['shakhas', 'mukhyashikshaks', 'events', 'notice']) ? 'open' : ''; ?>
                                        <li class="nav-group <?php echo $adminOpsActive; ?>">
                                            <div class="nav-group-header"><span>📊 प्रबंधन (Manage)</span> <span class="chevron">▼</span></div>
                                            <ul class="nav-group-items">
                                                <li><a href="../pages/shakhas.php" class="<?php echo $currentPage === 'shakhas' ? 'active' : ''; ?>">🚩 शाखाएं</a></li>
                                                <li><a href="../pages/mukhyashikshaks.php" class="<?php echo $currentPage === 'mukhyashikshaks' ? 'active' : ''; ?>">👤 मुख्य शिक्षक</a></li>
                                                <li><a href="../pages/events.php" class="<?php echo $currentPage === 'events' ? 'active' : ''; ?>">📅 कार्यक्रम</a></li>
                                                <li><a href="../pages/notice.php" class="<?php echo $currentPage === 'notice' ? 'active' : ''; ?>">📢 सूचना</a></li>
                                                <li><a href="../pages/greetings.php" class="<?php echo $currentPage === 'greetings' ? 'active' : ''; ?>">🎨 शुभकामनाएं</a></li>
                                            </ul>
                                        </li>
                                        
                                        <?php $adminSetActive = in_array($currentPage, ['shakha_timer', 'shakha_settings', 'change_password']) ? 'open' : ''; ?>
                                        <li class="nav-group <?php echo $adminSetActive; ?>">
                                            <div class="nav-group-header"><span>⚙️ टूल्स एवं सेटिंग्स</span> <span class="chevron">▼</span></div>
                                            <ul class="nav-group-items">
                                                <li><a href="../pages/shakha_timer.php" class="<?php echo $currentPage === 'shakha_timer' ? 'active' : ''; ?>">⏱️ शाखा टाइमर</a></li>
                                                <li><a href="../pages/shakha_settings.php" class="<?php echo $currentPage === 'shakha_settings' ? 'active' : ''; ?>">⚙️ शाखा सेटिंग्स</a></li>
                                                <li><a href="../pages/change_password.php" class="<?php echo $currentPage === 'change_password' ? 'active' : ''; ?>">🔑 पासवर्ड बदलें</a></li>
                                            </ul>
                                        </li>
                                        
                                <?php elseif (isMukhyashikshak()): ?>
                                        <li><a href="../pages/dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">🏠 मुख्य पृष्ठ</a></li>
                                        <li><a href="../pages/swayamsevaks.php" class="<?php echo $currentPage === 'swayamsevaks' ? 'active' : ''; ?>">👥 स्वयंसेवक</a></li>
                                        
                                        <?php $dailyOpsActive = in_array($currentPage, ['daily_record', 'activities', 'notice', 'subhashit', 'geet', 'ghoshnayein', 'greetings']) ? 'open' : ''; ?>
                                        <li class="nav-group <?php echo $dailyOpsActive; ?>">
                                            <div class="nav-group-header"><span>📅 दैनिक कार्य (Daily Ops)</span> <span class="chevron">▼</span></div>
                                            <ul class="nav-group-items">
                                                <li><a href="../pages/daily_record.php" class="<?php echo $currentPage === 'daily_record' ? 'active' : ''; ?>">📝 दैनिक रिकॉर्ड</a></li>
                                                <li><a href="../pages/activities.php" class="<?php echo $currentPage === 'activities' ? 'active' : ''; ?>">📋 गतिविधियाँ</a></li>
                                                <li><a href="../pages/notice.php" class="<?php echo $currentPage === 'notice' ? 'active' : ''; ?>">📢 सूचना</a></li>
                                                <li><a href="../pages/subhashit.php" class="<?php echo $currentPage === 'subhashit' ? 'active' : ''; ?>">📜 सुभाषित</a></li>
                                                <li><a href="../pages/geet.php" class="<?php echo $currentPage === 'geet' ? 'active' : ''; ?>">🎵 गीत</a></li>
                                                <li><a href="../pages/ghoshnayein.php" class="<?php echo $currentPage === 'ghoshnayein' ? 'active' : ''; ?>">🗣️ घोषणाएं</a></li>
                                                <li><a href="../pages/greetings.php" class="<?php echo $currentPage === 'greetings' ? 'active' : ''; ?>">🎨 शुभकामनाएं</a></li>
                                            </ul>
                                        </li>
                                        
                                        <?php $repActive = in_array($currentPage, ['records_list', 'records_calendar', 'monthly_report', 'analytics', 'insights']) ? 'open' : ''; ?>
                                        <li class="nav-group <?php echo $repActive; ?>">
                                            <div class="nav-group-header"><span>📊 रिपोर्ट्स (Reports)</span> <span class="chevron">▼</span></div>
                                            <ul class="nav-group-items">
                                                <li><a href="../pages/records_list.php" class="<?php echo $currentPage === 'records_list' ? 'active' : ''; ?>">📄 सूची</a></li>
                                                <li><a href="../pages/records_calendar.php" class="<?php echo $currentPage === 'records_calendar' ? 'active' : ''; ?>">📅 कैलेंडर</a></li>
                                                <li><a href="../pages/monthly_report.php" class="<?php echo $currentPage === 'monthly_report' ? 'active' : ''; ?>">📊 मासिक रिपोर्ट</a></li>
                                                <li><a href="../pages/analytics.php" class="<?php echo $currentPage === 'analytics' ? 'active' : ''; ?>">📈 एनालिटिक्स</a></li>
                                                <li><a href="../pages/insights.php" class="<?php echo $currentPage === 'insights' ? 'active' : ''; ?>">🧠 AI Insights</a></li>
                                            </ul>
                                        </li>
                                        
                                        <?php $mngActive = in_array($currentPage, ['timetable', 'shakha_timer', 'events', 'shakha_settings', 'change_password']) ? 'open' : ''; ?>
                                        <li class="nav-group <?php echo $mngActive; ?>">
                                            <div class="nav-group-header"><span>⚙️ प्रबंधन (Manage)</span> <span class="chevron">▼</span></div>
                                            <ul class="nav-group-items">
                                                <li><a href="../pages/timetable.php" class="<?php echo $currentPage === 'timetable' ? 'active' : ''; ?>">📋 समय-सारणी</a></li>
                                                <li><a href="../pages/shakha_timer.php" class="<?php echo $currentPage === 'shakha_timer' ? 'active' : ''; ?>">⏱️ शाखा टाइमर</a></li>
                                                <li><a href="../pages/events.php" class="<?php echo $currentPage === 'events' ? 'active' : ''; ?>">📅 कार्यक्रम</a></li>
                                                <li><a href="../pages/shakha_settings.php" class="<?php echo $currentPage === 'shakha_settings' ? 'active' : ''; ?>">⚙️ शाखा सेटिंग्स</a></li>
                                                <li><a href="../pages/change_password.php" class="<?php echo $currentPage === 'change_password' ? 'active' : ''; ?>">🔑 पासवर्ड बदलें</a></li>
                                            </ul>
                                        </li>
                                        
                                <?php elseif (isSwayamsevak()): ?>
                                        <li><a href="../pages/swayamsevak_dashboard.php" class="<?php echo $currentPage === 'swayamsevak_dashboard' ? 'active' : ''; ?>">🏠 मुख्य पृष्ठ</a></li>
                                        <li><a href="../pages/timetable_view.php" class="<?php echo $currentPage === 'timetable_view' ? 'active' : ''; ?>">📋 समय-सारणी</a></li>
                                        <li><a href="../pages/subhashit_view.php" class="<?php echo $currentPage === 'subhashit_view' ? 'active' : ''; ?>">📜 सुभाषित</a></li>
                                        <li><a href="../pages/change_password.php" class="<?php echo $currentPage === 'change_password' ? 'active' : ''; ?>">🔑 पासवर्ड बदलें</a></li>
                                <?php endif; ?>
                                <li style="margin-top: 20px;"><a href="../logout.php" class="nav-logout">🚪 लॉग आउट</a></li>
                        </ul>
                </aside>

                <script>
                        // Sidebar Toggles
                        const sidebarToggle = document.getElementById('sidebarToggle');
                        const closeSidebar = document.getElementById('closeSidebar');
                        const sidebar = document.getElementById('sidebar');
                        const sidebarOverlay = document.getElementById('sidebarOverlay');

                        function openSidebar() {
                                sidebar.classList.add('open');
                                sidebarOverlay.classList.add('active');
                                document.body.style.overflow = 'hidden'; // Prevent background scrolling
                        }

                        function closeSidebarFn() {
                                sidebar.classList.remove('open');
                                sidebarOverlay.classList.remove('active');
                                document.body.style.overflow = '';
                        }

                        sidebarToggle.addEventListener('click', openSidebar);
                        closeSidebar.addEventListener('click', closeSidebarFn);
                        sidebarOverlay.addEventListener('click', closeSidebarFn);
                        
                        // Accordion Logic
                        const navGroups = document.querySelectorAll('.nav-group-header');
                        navGroups.forEach(header => {
                                header.addEventListener('click', () => {
                                        const parent = header.parentElement;
                                        // Optional: Close others
                                        // document.querySelectorAll('.nav-group').forEach(g => {
                                        //         if(g !== parent) g.classList.remove('open');
                                        // });
                                        parent.classList.toggle('open');
                                });
                        });
                </script>

                <!-- MOBILE BOTTOM NAVIGATION (Quick Access) -->
                <nav class="bottom-nav">
                        <ul>
                                <?php if (isAdmin()): ?>
                                        <li><a href="../pages/admin_dashboard.php" class="<?php echo $currentPage === 'admin_dashboard' ? 'active' : ''; ?>"><span class="nav-icon">👑</span><span>मुख्य</span></a></li>
                                        <li><a href="../pages/shakhas.php" class="<?php echo $currentPage === 'shakhas' ? 'active' : ''; ?>"><span class="nav-icon">🚩</span><span>शाखाएं</span></a></li>
                                        <li><a href="../pages/mukhyashikshaks.php" class="<?php echo $currentPage === 'mukhyashikshaks' ? 'active' : ''; ?>"><span class="nav-icon">👤</span><span>शिक्षक</span></a></li>
                                        <li><a href="../pages/events.php" class="<?php echo $currentPage === 'events' ? 'active' : ''; ?>"><span class="nav-icon">📅</span><span>कार्यक्रम</span></a></li>
                                        <li><a href="../pages/notice.php" class="<?php echo $currentPage === 'notice' ? 'active' : ''; ?>"><span class="nav-icon">📢</span><span>सूचना</span></a></li>
                                        <li><a href="../pages/shakha_settings.php" class="<?php echo $currentPage === 'shakha_settings' ? 'active' : ''; ?>"><span class="nav-icon">⚙️</span><span>सेटिंग्स</span></a></li>
                                <?php elseif (isMukhyashikshak()): ?>
                                        <li><a href="../pages/dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>"><span class="nav-icon">🏠</span><span>मुख्य</span></a></li>
                                        <li><a href="../pages/activities.php" class="<?php echo $currentPage === 'activities' ? 'active' : ''; ?>"><span class="nav-icon">📋</span><span>गतिविधियाँ</span></a></li>
                                        <li><a href="../pages/daily_record.php" class="<?php echo $currentPage === 'daily_record' ? 'active' : ''; ?>"><span class="nav-icon">📝</span><span>रिकॉर्ड</span></a></li>
                                        <li><a href="../pages/events.php" class="<?php echo $currentPage === 'events' ? 'active' : ''; ?>"><span class="nav-icon">📅</span><span>कार्यक्रम</span></a></li>
                                        <li><a href="../pages/subhashit.php" class="<?php echo $currentPage === 'subhashit' ? 'active' : ''; ?>"><span class="nav-icon">📜</span><span>सुभाषित</span></a></li>
                                        <li><a href="../pages/greetings.php" class="<?php echo $currentPage === 'greetings' ? 'active' : ''; ?>"><span class="nav-icon">🎨</span><span>बधाई</span></a></li>
                                        <li><a href="../pages/notice.php" class="<?php echo $currentPage === 'notice' ? 'active' : ''; ?>"><span class="nav-icon">📢</span><span>सूचना</span></a></li>
                                        <li><a href="javascript:void(0)" onclick="openSidebar()"><span class="nav-icon">☰</span><span>मेनू</span></a></li>
                                <?php elseif (isSwayamsevak()): ?>
                                        <li><a href="../pages/swayamsevak_dashboard.php" class="<?php echo $currentPage === 'swayamsevak_dashboard' ? 'active' : ''; ?>"><span class="nav-icon">🏠</span><span>मुख्य</span></a></li>
                                        <li><a href="../pages/timetable_view.php" class="<?php echo $currentPage === 'timetable_view' ? 'active' : ''; ?>"><span class="nav-icon">📋</span><span>समय-सारणी</span></a></li>
                                        <li><a href="../pages/subhashit_view.php" class="<?php echo $currentPage === 'subhashit_view' ? 'active' : ''; ?>"><span class="nav-icon">📜</span><span>सुभाषित</span></a></li>
                                <?php endif; ?>
                        </ul>
                </nav>
        <?php endif; ?>

        <!-- Google Translate Widget CSS Fix -->
        <style>
                /* Hide Google Translate top bar */
                .goog-te-banner-frame.skiptranslate, .goog-te-gadget-icon { display: none !important; }
                body { top: 0px !important; }
                /* Hide 'Original text' popup */
                .goog-te-balloon-frame { display: none !important; }
                #goog-gt-tt { display: none !important; visibility: hidden !important; }
                .goog-text-highlight { background-color: transparent !important; box-shadow: none !important; }
                /* Ensure container has space */
                #google_translate_element { min-height: 30px; min-width: 100px; display: inline-block !important; }
        </style>

        <!-- Google Translate Widget Scripts -->
        <script type="text/javascript">
                function googleTranslateElementInit() {
                        new google.translate.TranslateElement({
                                pageLanguage: 'hi',
                                includedLanguages: 'hi,en,mr,gu,bn,te,ta,kn,ml,pa,ur,or,as,sa',
                                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                                autoDisplay: false
                        }, 'google_translate_element');
                }
        </script>
        <script type="text/javascript" src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

        <main class="main-content">
