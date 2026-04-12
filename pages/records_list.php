<?php
/**
 * Records List - रिकॉर्ड सूची
 */
$pageTitle = 'रिकॉर्ड सूची';
require_once '../includes/header.php';
require_once '../config/db.php';
requireLogin();

if (isSwayamsevak()) {
    header('Location: swayamsevak_dashboard.php');
    exit;
}

$shakhaId = getCurrentShakhaId();
$stmt = $pdo->prepare("SELECT dr.*, 
    (SELECT COUNT(*) FROM attendance a WHERE a.daily_record_id = dr.id AND a.is_present = 1) as present_count,
    (SELECT COUNT(*) FROM attendance a WHERE a.daily_record_id = dr.id) as total_members,
    (SELECT COUNT(*) FROM daily_activities da WHERE da.daily_record_id = dr.id AND da.is_done = 1) as activities_done,
    (SELECT COUNT(*) FROM daily_activities da WHERE da.daily_record_id = dr.id) as total_activities
    FROM daily_records dr 
    WHERE dr.shakha_id = ? 
    ORDER BY record_date DESC");
$stmt->execute([$shakhaId]);
$records = $stmt->fetchAll();

$hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
$hindiDays = ['रवि', 'सोम', 'मंगल', 'बुध', 'गुरु', 'शुक्र', 'शनि'];
?>

<div class="page-header">
    <h1>📄 रिकॉर्ड सूची</h1>
    <a href="../pages/daily_record.php" class="btn btn-primary">📝 नया रिकॉर्ड</a>
</div>

<?php if (empty($records)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="icon">📭</div>
            <p>अभी तक कोई रिकॉर्ड नहीं बना है।</p>
            <a href="../pages/daily_record.php" class="btn btn-primary">📝 पहला रिकॉर्ड बनाएँ</a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>तारीख</th>
                        <th>दिन</th>
                        <th>उपस्थिति</th>
                        <th>गतिविधियाँ</th>
                        <th>संदेश</th>
                        <th>कार्रवाई</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $rec):
                        $ts = strtotime($rec['record_date']);
                        $day = $hindiDays[date('w', $ts)];
                        $dateStr = date('j', $ts) . ' ' . $hindiMonths[date('n', $ts) - 1] . ' ' . date('Y', $ts);
                        ?>
                        <tr>
                            <td>
                                <?php echo $dateStr; ?>
                            </td>
                            <td>
                                <?php echo $day; ?>
                            </td>
                            <td>
                                <span class="badge badge-green">
                                    <?php echo $rec['present_count']; ?>/
                                    <?php echo $rec['total_members']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-saffron">
                                    <?php echo $rec['activities_done']; ?>/
                                    <?php echo $rec['total_activities']; ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $rec['custom_message'] ? '💬' : '-'; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="../pages/record_detail.php?id=<?php echo $rec['id']; ?>" class="btn btn-sm btn-outline">👁️
                                        देखें</a>
                                    <a href="../pages/daily_record.php?id=<?php echo $rec['id']; ?>"
                                        class="btn btn-sm btn-outline">✏️</a>
                                    <a href="../pages/snapshot.php?id=<?php echo $rec['id']; ?>" class="btn btn-sm btn-primary">📸</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
