<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['event_role']) || $_SESSION['event_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

require_once '../../config/db.php';
require_once 'includes/header.php';

$event_id = $_SESSION['event_id'] ?? null;

$allowed_columns = [
    'organization' => 'संस्था (Organization)',
    'level_type' => 'स्तर (Level Type)',
    'responsibility' => 'दायित्व (Responsibility)',
    'sangh_shikshan' => 'संघ शिक्षा (Sangh Shikshan)',
    'age_group' => 'आयु वर्ग (Age Group)',
    'city' => 'नगर (City)',
    'vasti' => 'बस्ती (Vasti)',
    'category' => 'श्रेणी (Category)',
    'bhag' => 'भाग (Bhag)'
];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'merge_data') {
    $column = $_POST['column'] ?? '';
    $target_value = $_POST['target_value'] ?? '';
    $merge_values = $_POST['merge_values'] ?? [];

    if (array_key_exists($column, $allowed_columns)) {
        if (!empty($target_value) && !empty($merge_values) && is_array($merge_values)) {
            // Ensure target_value is not in merge_values
            $merge_values = array_diff($merge_values, [$target_value]);
            
            if (!empty($merge_values)) {
                $placeholders = implode(',', array_fill(0, count($merge_values), '?'));
                $sql = "UPDATE em_participants SET $column = ? WHERE $column IN ($placeholders) AND event_id = ?";
                $stmt = $pdo->prepare($sql);
                
                $params = [$target_value];
                foreach ($merge_values as $val) {
                    $params[] = $val;
                }
                $params[] = $event_id;
                
                try {
                    $stmt->execute($params);
                    $affected = $stmt->rowCount();
                    $message = "सफलतापूर्वक $affected रिकॉर्ड्स अपडेट किए गए। (Successfully updated $affected records.)";
                } catch (PDOException $e) {
                    $error = "डेटा अपडेट करने में त्रुटि (Error updating data): " . $e->getMessage();
                }
            } else {
                $error = "मर्ज करने के लिए कोई मान्य मान नहीं चुना गया। (No valid values selected for merge.)";
            }
        } else {
            $error = "कृपया लक्ष्य मान और मर्ज करने वाले मान चुनें। (Please select target and merge values.)";
        }
    } else {
         $error = "अमान्य कॉलम। (Invalid column.)";
    }
}

$selected_column = $_GET['column'] ?? '';
$distinct_values = [];

if (array_key_exists($selected_column, $allowed_columns) && $event_id) {
    // We sanitize $selected_column by verifying it's a key in $allowed_columns
    $stmt = $pdo->prepare("SELECT DISTINCT $selected_column FROM em_participants WHERE event_id = ? AND $selected_column IS NOT NULL AND $selected_column != '' ORDER BY $selected_column ASC");
    $stmt->execute([$event_id]);
    $distinct_values = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<div class="card">
    <h2>मास्टर डेटा अपडेट (Master Data Update)</h2>
    
    <?php if ($message): ?>
        <div style="background: var(--success); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background: var(--danger); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="GET" action="">
        <div class="form-group">
            <label for="column">अपडेट करने के लिए कॉलम चुनें (Select Column to Clean)</label>
            <select name="column" id="column" class="form-control" onchange="this.form.submit()">
                <option value="">-- चुनें (Select) --</option>
                <?php foreach ($allowed_columns as $col => $label): ?>
                    <option value="<?= htmlspecialchars($col) ?>" <?= $selected_column === $col ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if (array_key_exists($selected_column, $allowed_columns)): ?>
        <?php if (!empty($distinct_values)): ?>
            <hr style="border-color: var(--border-color); margin: 2rem 0;">
            <form method="POST" action="">
                <input type="hidden" name="action" value="merge_data">
                <input type="hidden" name="column" value="<?= htmlspecialchars($selected_column) ?>">
                
                <div class="form-group">
                    <label for="target_value">लक्ष्य मान (Target Master Value)</label>
                    <select name="target_value" id="target_value" class="form-control" required>
                        <option value="">-- चुनें (Select) --</option>
                        <?php foreach ($distinct_values as $val): ?>
                            <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($val) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>मर्ज करने वाले मान चुनें (Select Values to Merge)</label>
                    <div style="max-height: 300px; overflow-y: auto; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem;">
                        <?php foreach ($distinct_values as $val): ?>
                            <div style="margin-bottom: 0.5rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; color: var(--text-color); cursor: pointer;">
                                    <input type="checkbox" name="merge_values[]" value="<?= htmlspecialchars($val) ?>" class="merge-checkbox" style="width: auto;">
                                    <?= htmlspecialchars($val) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn" onclick="return confirm('क्या आप निश्चित हैं? यह क्रिया अपरिवर्तनीय है। (Are you sure? This action is irreversible.)')">
                    मर्ज करें (Merge)
                </button>
            </form>

            <script>
                // Disable target value in checkbox list so it cannot be selected for merging
                document.getElementById('target_value').addEventListener('change', function() {
                    const targetVal = this.value;
                    const checkboxes = document.querySelectorAll('.merge-checkbox');
                    checkboxes.forEach(cb => {
                        if (cb.value === targetVal && targetVal !== '') {
                            cb.checked = false;
                            cb.disabled = true;
                            cb.parentElement.style.opacity = '0.5';
                        } else {
                            cb.disabled = false;
                            cb.parentElement.style.opacity = '1';
                        }
                    });
                });
            </script>
        <?php else: ?>
            <p style="margin-top: 1.5rem; color: var(--text-muted);">इस कॉलम में कोई डेटा नहीं है। (No data available in this column.)</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
