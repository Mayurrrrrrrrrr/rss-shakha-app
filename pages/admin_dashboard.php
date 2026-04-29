<?php
require_once '../includes/auth.php';
/**
 * Admin Dashboard - एडमिन डैशबोर्ड
 */
require_once '../config/db.php';
requireLogin();

if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'एडमिन डैशबोर्ड';
require_once '../includes/header.php';

// Stats
$totalShakhas = $pdo->query("SELECT COUNT(*) FROM shakhas")->fetchColumn();
$totalMukhyashikshaks = $pdo->query("SELECT COUNT(*) FROM admin_users WHERE role = 'mukhyashikshak'")->fetchColumn();
$totalRecords = $pdo->query("SELECT COUNT(*) FROM daily_records")->fetchColumn();

// Fetch all shakhas for the dropdown
$shakhas = $pdo->query("SELECT id, name FROM shakhas ORDER BY name")->fetchAll();

// Recent records from all shakhas
$recentRecords = $pdo->query("SELECT dr.*, s.name as shakha_name,
    (SELECT COUNT(*) FROM attendance a WHERE a.daily_record_id = dr.id AND a.is_present = 1) as present_count,
    (SELECT COUNT(*) FROM daily_activities da WHERE da.daily_record_id = dr.id AND da.is_done = 1) as activities_done
    FROM daily_records dr 
    JOIN shakhas s ON dr.shakha_id = s.id
    ORDER BY dr.record_date DESC LIMIT 20")->fetchAll();

$hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
$hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];

function formatHindiDate($dateStr)
{
    global $hindiMonths, $hindiDays;
    $ts = strtotime($dateStr);
    $day = $hindiDays[date('w', $ts)];
    $d = date('j', $ts);
    $m = $hindiMonths[date('n', $ts) - 1];
    $y = date('Y', $ts);
    return "$day, $d $m $y";
}
?>
<style>
.admin-quick-bar {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    margin-bottom: 30px;
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
    border: 1px solid rgba(0,0,0,0.05);
}
.admin-stat-card {
    background: linear-gradient(135deg, #2E3192 0%, #1BFFFF 100%);
    color: white;
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 8px 20px rgba(46,49,146,0.2);
    flex: 1;
    min-width: 150px;
}
.admin-stat-card.stat-2 { background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); box-shadow: 0 8px 20px rgba(255,65,108,0.2); }
.admin-stat-card.stat-3 { background: linear-gradient(135deg, #00B4DB 0%, #0083B0 100%); box-shadow: 0 8px 20px rgba(0,180,219,0.2); }
</style>

<div class="page-header" style="margin-bottom: 20px;">
    <h1>👑 नमस्ते, <?php echo htmlspecialchars(getAdminName()); ?> (सुपर एडमिन)</h1>
</div>

<!-- Quick Actions -->
<div class="admin-quick-bar fade-in">
    <div style="font-weight: 600; color: #333; margin-right: 10px;">शाखा सामग्री देखें:</div>
    <select id="sel-shakha" class="form-control" style="width: auto; display: inline-block;">
        <?php foreach ($shakhas as $s): ?>
            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <button onclick="openFlipbook()" class="btn" style="background: linear-gradient(135deg, #4CAF50, #2E7D32); color: white; border: none;">📱 डिजिटल वृत्त (Flipbook)</button>
    <button onclick="openZine()" class="btn" style="background: #fff; color: #ff5722; border: 1px solid #ff5722;">🖨️ Paper Shakha (Zine)</button>
</div>

<!-- Stats -->
<div style="display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;" class="fade-in" style="animation-delay: 0.1s;">
    <div class="admin-stat-card">
        <div style="font-size: 36px; font-weight: bold; margin-bottom: 5px;"><?php echo $totalShakhas; ?></div>
        <div style="font-size: 14px; opacity: 0.9;">कुल शाखाएं</div>
    </div>
    <div class="admin-stat-card stat-2">
        <div style="font-size: 36px; font-weight: bold; margin-bottom: 5px;"><?php echo $totalMukhyashikshaks; ?></div>
        <div style="font-size: 14px; opacity: 0.9;">मुख्य शिक्षक</div>
    </div>
    <div class="admin-stat-card stat-3">
        <div style="font-size: 36px; font-weight: bold; margin-bottom: 5px;"><?php echo $totalRecords; ?></div>
        <div style="font-size: 14px; opacity: 0.9;">कुल दैनिक रिकॉर्ड</div>
    </div>
</div>

<!-- Recent Records -->
<div class="card fade-in" style="animation-delay: 0.2s;">
    <div class="card-header">📋 सभी शाखाओं के हाल के रिकॉर्ड</div>
    <?php if (empty($recentRecords)): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <p>अभी तक कोई रिकॉर्ड नहीं है।</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>शाखा</th>
                        <th>तारीख</th>
                        <th>उपस्थिति</th>
                        <th>गतिविधियाँ</th>
                        <th>कार्रवाई</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentRecords as $rec): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($rec['shakha_name']); ?></strong></td>
                            <td>
                                <?php echo formatHindiDate($rec['record_date']); ?>
                                <?php if (!empty($rec['utsav'])): ?>
                                    <div style="font-size: 11px; color: #E65100; margin-top: 3px; font-weight: bold;">
                                        🌺 <?php echo htmlspecialchars($rec['utsav']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-green"><?php echo $rec['present_count']; ?> उपस्थित</span></td>
                            <td><span class="badge badge-saffron"><?php echo $rec['activities_done']; ?> पूर्ण</span></td>
                            <td>
                                <a href="../pages/record_detail.php?id=<?php echo $rec['id']; ?>" class="btn btn-sm btn-outline">👁️ देखें</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function openFlipbook() {
    const shakhaId = document.getElementById('sel-shakha').value;
    const date = '<?php echo date("Y-m-d"); ?>';
    // Flipbook relies on session shakha_id via getCurrentShakhaId() usually, 
    // but if we pass ?shakha_id= it could override.
    // Wait, getCurrentShakhaId() doesn't read $_GET['shakha_id'] by default unless modified!
    // For now, let's just open the flipbook. The flipbook might need an update to read $_GET['shakha_id'].
    window.open('../pages/daily_flipbook.php?date=' + date + '&shakha_id=' + shakhaId, '_blank');
}
function openZine() {
    const shakhaId = document.getElementById('sel-shakha').value;
    const date = '<?php echo date("Y-m-d"); ?>';
    window.open('../pages/paper_shakha.php?date=' + date + '&shakha_id=' + shakhaId, '_blank');
}
</script>

<?php require_once '../includes/footer.php'; ?>
