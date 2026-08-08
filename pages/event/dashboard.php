<?php
session_start();
require_once '../../config/db.php';

$event_id = $_SESSION['event_id'] ?? null;
$vyavastha = $_SESSION['event_vyavastha'] ?? 'all';
$role = $_SESSION['event_role'] ?? '';
$user_name = $_SESSION['event_user_name'] ?? 'User';
$assigned_bhag = $_SESSION['event_assigned_bhag'] ?? '';
$is_admin = ($role === 'admin' || $vyavastha === 'all');
$is_volunteer = ($vyavastha === 'hajiri');

$total_participants = 0;
$total_organizers = 0;

$today_attendance = 0;
$recent_participants = [];

try {
    $total_participants = $pdo->prepare("SELECT COUNT(*) FROM em_participants WHERE event_id = ?");
    $total_participants->execute([$event_id]);
    $total_participants = $total_participants->fetchColumn() ?: 0;

    if ($is_admin) {
        $total_organizers = $pdo->prepare("SELECT COUNT(*) FROM em_organizers WHERE event_id = ?");
        $total_organizers->execute([$event_id]);
        $total_organizers = $total_organizers->fetchColumn() ?: 0;


        $recent_participants = $pdo->prepare("SELECT name, city, phone FROM em_participants WHERE event_id = ? ORDER BY id DESC LIMIT 5");
        $recent_participants->execute([$event_id]);
        $recent_participants = $recent_participants->fetchAll(PDO::FETCH_ASSOC);
    }

    // Today's attendance using the correct table
    try {
        $attStmt = $pdo->prepare("SELECT COUNT(*) FROM em_participant_attendance WHERE event_id = ? AND is_present = 1 AND DATE(marked_at) = CURDATE()");
        $attStmt->execute([$event_id]);
        $today_attendance = $attStmt->fetchColumn() ?: 0;
    } catch (Exception $e) { /* table may not exist */ }

} catch (Exception $e) {
    // Tables may not exist yet
}

include 'includes/header.php';
?>

<style>
    .dash-greeting {
        margin-bottom: 2rem;
    }
    .dash-greeting h2 {
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0 0 0.25rem 0;
        background: linear-gradient(135deg, var(--text-color), var(--text-muted));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .dash-greeting p {
        color: var(--text-muted);
        margin: 0;
        font-size: 0.95rem;
    }
    .dash-greeting .event-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background: rgba(249, 115, 22, 0.15);
        color: var(--saffron);
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-top: 0.5rem;
    }
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .stat-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px -12px rgba(0,0,0,0.4);
    }
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }
    .stat-card.saffron::before { background: linear-gradient(90deg, #F97316, #FB923C); }
    .stat-card.purple::before { background: linear-gradient(90deg, #8B5CF6, #A78BFA); }
    .stat-card.green::before { background: linear-gradient(90deg, #10B981, #34D399); }
    .stat-card.blue::before { background: linear-gradient(90deg, #3B82F6, #60A5FA); }
    .stat-card.cyan::before { background: linear-gradient(90deg, #06B6D4, #22D3EE); }
    .stat-value {
        font-size: 2.25rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 0.5rem;
    }
    .stat-card.saffron .stat-value { color: #F97316; }
    .stat-card.purple .stat-value { color: #8B5CF6; }
    .stat-card.green .stat-value { color: #10B981; }
    .stat-card.blue .stat-value { color: #3B82F6; }
    .stat-card.cyan .stat-value { color: #06B6D4; }
    .stat-label {
        color: var(--text-muted);
        font-size: 0.85rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .dash-section {
        margin-bottom: 2rem;
    }
    .dash-section h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-muted);
        margin-bottom: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
    }
    .action-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 1.25rem 1.5rem;
        text-decoration: none;
        color: var(--text-color);
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .action-card:hover {
        border-color: var(--saffron);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px -8px rgba(249, 115, 22, 0.2);
    }
    .action-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    .action-icon.att { background: rgba(16, 185, 129, 0.15); }
    .action-icon.food { background: rgba(249, 115, 22, 0.15); }
    .action-icon.room { background: rgba(59, 130, 246, 0.15); }
    .action-icon.people { background: rgba(139, 92, 246, 0.15); }
    .action-icon.chart { background: rgba(6, 182, 212, 0.15); }
    .action-icon.spot { background: rgba(236, 72, 153, 0.15); }
    .action-text h4 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
    }
    .action-text p {
        margin: 0.15rem 0 0;
        font-size: 0.8rem;
        color: var(--text-muted);
    }
    .recent-table {
        width: 100%;
        border-collapse: collapse;
    }
    .recent-table th {
        background: rgba(255,255,255,0.02);
        padding: 0.75rem 1rem;
        text-align: left;
        color: var(--text-muted);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        border-bottom: 1px solid var(--border-color);
    }
    .recent-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.03);
        font-size: 0.9rem;
    }
    .recent-table tr:last-child td { border-bottom: none; }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .fade-in {
        animation: fadeInUp 0.5s ease forwards;
    }
    .fade-in:nth-child(2) { animation-delay: 0.1s; }
    .fade-in:nth-child(3) { animation-delay: 0.15s; }
    .fade-in:nth-child(4) { animation-delay: 0.2s; }
    .fade-in:nth-child(5) { animation-delay: 0.25s; }
</style>

<!-- Greeting -->
<div class="dash-greeting fade-in">
    <h2>नमस्ते, <?= htmlspecialchars($user_name) ?> 🙏</h2>
    <p>आपके आयोजन का अवलोकन</p>
    <span class="event-badge">📋 <?= htmlspecialchars($_SESSION['event_name'] ?? 'Event') ?></span>
</div>

<!-- Stats -->
<div class="stat-grid">
    <div class="stat-card saffron fade-in">
        <div class="stat-value"><?= $total_participants ?></div>
        <div class="stat-label">कुल प्रतिभागी</div>
    </div>
    <div class="stat-card green fade-in">
        <div class="stat-value"><?= $today_attendance ?></div>
        <div class="stat-label">आज उपस्थित</div>
    </div>
    <?php if ($is_admin): ?>
    <div class="stat-card purple fade-in">
        <div class="stat-value"><?= $total_organizers ?></div>
        <div class="stat-label">प्रबंधक</div>
    </div>
    <?php endif; ?>
</div>

<!-- Quick Actions -->
<div class="dash-section fade-in">
    <h3>त्वरित क्रिया (Quick Actions)</h3>
    <div class="quick-actions">
        <?php if ($is_volunteer): ?>
            <a href="attendance.php" class="action-card">
                <div class="action-icon att">✅</div>
                <div class="action-text">
                    <h4>हाजिरी लें</h4>
                    <p>Take Attendance</p>
                </div>
            </a>
        <?php elseif ($is_admin): ?>
            <a href="attendance.php" class="action-card">
                <div class="action-icon att">✅</div>
                <div class="action-text">
                    <h4>हाजिरी</h4>
                    <p>Attendance</p>
                </div>
            </a>
            <a href="participants.php" class="action-card">
                <div class="action-icon people">👥</div>
                <div class="action-text">
                    <h4>प्रतिभागी</h4>
                    <p>Participants</p>
                </div>
            </a>

        <?php endif; ?>
    </div>
</div>

<?php if ($is_admin && !empty($recent_participants)): ?>
<!-- Recent Registrations (Admin Only) -->
<div class="dash-section fade-in">
    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 1.5rem 1.5rem 1rem; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">नवीनतम पंजीकरण</h3>
            <a href="participants.php" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.4rem 1rem;">सभी देखें</a>
        </div>
        <table class="recent-table">
            <thead>
                <tr>
                    <th>नाम</th>
                    <th>नगर</th>
                    <th>संपर्क</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_participants as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['city'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['phone'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
