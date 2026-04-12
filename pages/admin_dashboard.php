<?php
/**
 * Admin Dashboard - एडमिन डैशबोर्ड
 */
$pageTitle = 'एडमिन डैशबोर्ड';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

// Stats
$totalShakhas = $pdo->query("SELECT COUNT(*) FROM shakhas")->fetchColumn();
$totalMukhyashikshaks = $pdo->query("SELECT COUNT(*) FROM admin_users WHERE role = 'mukhyashikshak'")->fetchColumn();
$totalRecords = $pdo->query("SELECT COUNT(*) FROM daily_records")->fetchColumn();

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

<div class="page-header">
    <h1>👑 नमस्ते,
        <?php echo htmlspecialchars(getAdminName()); ?> (सुपर एडमिन)
    </h1>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><img src="../assets/images/flag_icon.png" class="brand-icon" style="height: 1.5em;" alt="🚩"></div>
        <div class="stat-number">
            <?php echo $totalShakhas; ?>
        </div>
        <div class="stat-label">कुल शाखाएं</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">👤</div>
        <div class="stat-number">
            <?php echo $totalMukhyashikshaks; ?>
        </div>
        <div class="stat-label">मुख्य शिक्षक</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-number">
            <?php echo $totalRecords; ?>
        </div>
        <div class="stat-label">कुल दैनिक रिकॉर्ड</div>
    </div>
</div>

<!-- Recent Records -->
<div class="card">
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
                            <td><strong>
                                    <?php echo htmlspecialchars($rec['shakha_name']); ?>
                                </strong></td>
                            <td>
                                <?php echo formatHindiDate($rec['record_date']); ?>
                            </td>
                            <td><span class="badge badge-green">
                                    <?php echo $rec['present_count']; ?> उपस्थित
                                </span></td>
                            <td><span class="badge badge-saffron">
                                    <?php echo $rec['activities_done']; ?> पूर्ण
                                </span></td>
                            <td>
                                <div class="table-actions">
                                    <a href="../pages/record_detail.php?id=<?php echo $rec['id']; ?>" class="btn btn-sm btn-outline">👁️
                                        देखें</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
