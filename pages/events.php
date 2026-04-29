<?php
require_once '../includes/auth.php';
$pageTitle = 'कार्यक्रम (Events)';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

if (!isAdmin() && !isMukhyashikshak()) {
    header("Location: ../index.php");
    exit;
}

// Auto-create table to prevent errors if update_db.php wasn't run
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shakha_id INT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        event_date DATE NOT NULL,
        event_time TIME NOT NULL,
        location VARCHAR(255),
        meeting_link VARCHAR(500),
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (shakha_id) REFERENCES shakhas(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {
    // Ignore error if it fails to create
}

// Fetch events
if (isAdmin()) {
    // Admin sees all events
    $stmt = $pdo->query("SELECT e.*, s.name as shakha_name, u.name as creator_name 
                         FROM events e 
                         LEFT JOIN shakhas s ON e.shakha_id = s.id 
                         LEFT JOIN admin_users u ON e.created_by = u.id 
                         ORDER BY e.event_date DESC, e.event_time DESC");
    $events = $stmt->fetchAll();
} else {
    // Mukhyashikshak sees global events (shakha_id IS NULL) OR their own shakha's events
    $stmt = $pdo->prepare("SELECT e.*, s.name as shakha_name, u.name as creator_name 
                           FROM events e 
                           LEFT JOIN shakhas s ON e.shakha_id = s.id 
                           LEFT JOIN admin_users u ON e.created_by = u.id 
                           WHERE e.shakha_id IS NULL OR e.shakha_id = ? 
                           ORDER BY e.event_date DESC, e.event_time DESC");
    $stmt->execute([$_SESSION['shakha_id']]);
    $events = $stmt->fetchAll();
}

$shakhas = [];
if (isAdmin()) {
    $shakhas = $pdo->query("SELECT id, name FROM shakhas ORDER BY name")->fetchAll();
}
?>

<div class="page-header">
    <h1>📅 कार्यक्रम (Events)</h1>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
<?php endif; ?>

<!-- Add Event Form -->
<div class="premium-card mb-20 fade-in">
    <div class="card-header">✨ नया कार्यक्रम जोड़ें</div>
    <div class="card-body">
        <form action="../actions/add_event.php" method="POST" class="add-form">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <?php if (isAdmin()): ?>
                <div class="form-group mb-12">
                    <label class="form-label">शाखा (Shakha)</label>
                    <select name="shakha_id" class="form-input">
                        <option value="">सभी शाखाओं के लिए (Global)</option>
                        <?php foreach ($shakhas as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>यदि आप "सभी शाखाओं के लिए" चुनते हैं, तो यह सभी को दिखाई देगा।</small>
                </div>
            <?php endif; ?>

            <div class="form-group mb-12">
                <label class="form-label">शीर्षक (Title) *</label>
                <input type="text" name="title" class="form-input" required placeholder="उदा. रविवार विशेष शाखा">
            </div>

            <div class="form-group mb-12">
                <label class="form-label">विवरण (Description)</label>
                <textarea name="description" class="form-input" rows="2" placeholder="कार्यक्रम का विवरण..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group mb-12">
                    <label class="form-label">दिनांक (Date) *</label>
                    <input type="date" name="event_date" class="form-input" required>
                </div>
                <div class="form-group mb-12">
                    <label class="form-label">समय (Time) *</label>
                    <input type="time" name="event_time" class="form-input" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group mb-12">
                    <label class="form-label">स्थान (Location)</label>
                    <input type="text" name="location" class="form-input" placeholder="उदा. पुलिस मैदान">
                </div>
                <div class="form-group mb-15">
                    <label class="form-label">ऑनलाइन मीटिंग लिंक</label>
                    <input type="url" name="meeting_link" class="form-input" placeholder="https://meet.google.com/...">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; font-size: 1.1rem; border-radius: 12px; padding: 15px;">
                ✨ कार्यक्रम सुरक्षित करें (Save Event)
            </button>
        </form>
    </div>
</div>

<!-- List Events -->
<div class="events-section fade-in" style="animation-delay: 0.1s;">
    <h2 class="section-title">📋 आगामी कार्यक्रम</h2>
    
    <?php if (empty($events)): ?>
        <div class="empty-state">
            <div class="icon">📅</div>
            <p>कोई कार्यक्रम निर्धारित नहीं है।</p>
        </div>
    <?php else: ?>
        <div class="events-grid">
            <?php foreach ($events as $ev): ?>
                <?php
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'];
                    $dir = dirname($_SERVER['PHP_SELF']); 
                    $baseDir = dirname($dir);
                    $appUrl = $protocol . "://" . $host . $baseDir;
                    
                    $icsLink = $appUrl . "/api/event_ics.php?id=" . $ev['id'];
                    $waMessage = "📅 *कार्यक्रम (Event):* " . $ev['title'] . "\n";
                    $waMessage .= "🗓️ *दिनांक:* " . date('d M Y', strtotime($ev['event_date'])) . "\n";
                    $waMessage .= "⏰ *समय:* " . date('h:i A', strtotime($ev['event_time'])) . "\n";
                    if (!empty($ev['location'])) $waMessage .= "📍 *स्थान:* " . $ev['location'] . "\n";
                    if (!empty($ev['meeting_link'])) $waMessage .= "🔗 *लिंक:* " . $ev['meeting_link'] . "\n";
                    
                    $waMessage .= "\n🗓️ *कैलेंडर में रिमाइंडर जोड़ने के लिए नीचे दिए गए लिंक पर क्लिक करें:*\n" . $icsLink;
                    $waUrl = "https://wa.me/?text=" . rawurlencode($waMessage);
                ?>
                <div class="event-card">
                    <div class="event-card-header">
                        <div class="event-date-badge">
                            <span class="day"><?php echo date('d', strtotime($ev['event_date'])); ?></span>
                            <span class="month"><?php echo date('M', strtotime($ev['event_date'])); ?></span>
                        </div>
                        <div class="event-type-badge">
                            <?php if (!$ev['shakha_id']): ?>
                                <span class="badge badge-saffron">🌐 Global</span>
                            <?php else: ?>
                                <span class="badge badge-green">🚩 <?php echo htmlspecialchars($ev['shakha_name']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="event-card-body">
                        <h3 class="event-title"><?php echo htmlspecialchars($ev['title']); ?></h3>
                        <?php if (!empty($ev['description'])): ?>
                            <p class="event-desc"><?php echo nl2br(htmlspecialchars($ev['description'])); ?></p>
                        <?php endif; ?>
                        
                        <div class="event-details">
                            <div class="detail-item">
                                <span class="detail-icon">⏰</span>
                                <span><?php echo date('h:i A', strtotime($ev['event_time'])); ?></span>
                            </div>
                            <?php if (!empty($ev['location'])): ?>
                                <div class="detail-item">
                                    <span class="detail-icon">📍</span>
                                    <span><?php echo htmlspecialchars($ev['location']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($ev['meeting_link'])): ?>
                                <div class="detail-item link-item">
                                    <span class="detail-icon">🔗</span>
                                    <a href="<?php echo htmlspecialchars($ev['meeting_link']); ?>" target="_blank">ऑनलाइन मीटिंग से जुड़ें</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="event-card-footer">
                        <a href="<?php echo htmlspecialchars($waUrl); ?>" target="_blank" class="event-btn btn-wa">
                            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                            Share on WhatsApp
                        </a>
                        
                        <?php if (isAdmin() || ($ev['shakha_id'] == $_SESSION['shakha_id'])): ?>
                            <a href="../actions/delete_event.php?id=<?php echo $ev['id']; ?>" class="event-action-delete" onclick="return confirm('क्या आप वाकई इसे हटाना चाहते हैं?')">
                                🗑️
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Events Specific Styles */
.premium-card {
    background: rgba(34, 34, 46, 0.7);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 107, 0, 0.2);
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.premium-card .card-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    font-size: 1.3rem;
    padding-bottom: 15px;
    margin-bottom: 25px;
}

.form-input {
    width: 100%;
    padding: 14px 18px;
    background: rgba(15, 15, 20, 0.6);
    border: 1px solid var(--border-light);
    border-radius: 10px;
    color: var(--text-primary);
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-input:focus {
    outline: none;
    border-color: var(--saffron);
    box-shadow: 0 0 0 3px var(--saffron-glow);
    background: rgba(15, 15, 20, 0.9);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

@media (max-width: 600px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.mb-15 { margin-bottom: 15px; }
.mb-12 { margin-bottom: 12px; }

/* Events Grid layout */
.events-section {
    margin-top: 30px;
}
.section-title {
    font-size: 1.4rem;
    margin-bottom: 20px;
    color: var(--text-primary);
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.event-card {
    background: var(--bg-card);
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid var(--border-color);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease;
    display: flex;
    flex-direction: column;
}

.event-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.4);
    border-color: rgba(255, 107, 0, 0.4);
}

.event-card-header {
    background: linear-gradient(135deg, rgba(26,26,36,1) 0%, rgba(34,34,46,1) 100%);
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.event-date-badge {
    background: linear-gradient(135deg, var(--saffron) 0%, var(--saffron-dark) 100%);
    color: #fff;
    border-radius: 12px;
    padding: 8px 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow: 0 4px 15px rgba(255, 107, 0, 0.3);
}

.event-date-badge .day {
    font-size: 1.5rem;
    font-weight: 800;
    line-height: 1;
}

.event-date-badge .month {
    font-size: 0.8rem;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 1px;
}

.event-type-badge .badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.badge-saffron { background: rgba(255, 107, 0, 0.15); color: var(--saffron-light); border: 1px solid var(--saffron); }
.badge-green { background: rgba(76, 175, 80, 0.15); color: var(--green-light); border: 1px solid var(--green-mid); }

.event-card-body {
    padding: 20px;
    flex-grow: 1;
}

.event-title {
    font-size: 1.3rem;
    color: var(--text-primary);
    margin-bottom: 10px;
    font-weight: 700;
    line-height: 1.4;
}

.event-desc {
    color: var(--text-secondary);
    font-size: 0.95rem;
    margin-bottom: 18px;
    line-height: 1.6;
}

.event-details {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-muted);
    font-size: 0.95rem;
}

.detail-icon {
    font-size: 1.1rem;
    background: rgba(255,255,255,0.05);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.link-item a {
    color: var(--info);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.link-item a:hover {
    color: var(--saffron-light);
    text-decoration: underline;
}

.event-card-footer {
    padding: 16px 20px;
    background: rgba(0,0,0,0.15);
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
}

.event-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    transition: all 0.3s;
    flex-grow: 1;
}

.btn-wa {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(37, 211, 102, 0.2);
}

.btn-wa:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
    color: white;
}

.event-action-delete {
    background: rgba(239, 83, 80, 0.1);
    color: var(--danger);
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    transition: all 0.2s;
    text-decoration: none;
}

.event-action-delete:hover {
    background: var(--danger);
    color: white;
    transform: scale(1.05);
}
</style>

<?php require_once '../includes/footer.php'; ?>
