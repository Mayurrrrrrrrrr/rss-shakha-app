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
                    $message = "सफलतापूर्वक $affected रिकॉर्ड्स अपडेट किए गए। (Successfully merged and updated $affected records.)";
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

$selected_column = $_GET['column'] ?? $_POST['column'] ?? '';
$distinct_values_counts = [];
$suggestions = [];

if (array_key_exists($selected_column, $allowed_columns) && $event_id) {
    // Fetch values and their frequencies
    $stmt = $pdo->prepare("SELECT $selected_column as val, COUNT(*) as cnt FROM em_participants WHERE event_id = ? AND $selected_column IS NOT NULL AND $selected_column != '' GROUP BY $selected_column ORDER BY $selected_column ASC");
    $stmt->execute([$event_id]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $distinct_values_counts[$row['val']] = $row['cnt'];
    }

    // Auto-detect similar spellings (Duplicate Detection)
    $keys = array_keys($distinct_values_counts);
    for ($i = 0; $i < count($keys); $i++) {
        for ($j = $i + 1; $j < count($keys); $j++) {
            $str1 = mb_strtolower(trim($keys[$i]));
            $str2 = mb_strtolower(trim($keys[$j]));
            
            // Skip if strings are too short for meaningful comparison
            if (mb_strlen($str1) < 3 || mb_strlen($str2) < 3) continue;

            similar_text($str1, $str2, $percent);
            
            // Suggest if similarity is > 85% or if one contains the other (and > 5 chars to avoid false positives)
            if ($percent > 85 || (mb_strlen($str1) > 5 && mb_strlen($str2) > 5 && (mb_strpos($str1, $str2) !== false || mb_strpos($str2, $str1) !== false))) {
                $suggestions[] = [
                    'val1' => $keys[$i], 'count1' => $distinct_values_counts[$keys[$i]],
                    'val2' => $keys[$j], 'count2' => $distinct_values_counts[$keys[$j]],
                    'similarity' => round($percent)
                ];
            }
        }
    }
    
    // Sort suggestions by similarity DESC
    usort($suggestions, function($a, $b) {
        return $b['similarity'] <=> $a['similarity'];
    });
}
?>

<style>
.dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.dashboard-header h1 { font-size: 1.8rem; font-weight: 800; color: var(--text-color); margin: 0; }
.split-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1.5rem; }
@media (max-width: 992px) { .split-layout { grid-template-columns: 1fr; } }
.panel { background: var(--card-bg); border: 1px solid rgba(255,255,255,0.05); border-radius: var(--radius-lg); padding: 1.5rem; }
.panel-title { font-size: 1.1rem; font-weight: 600; color: var(--saffron); margin-top: 0; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
.badge { background: rgba(255,255,255,0.1); color: var(--text-muted); padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; }
.suggestion-card { background: rgba(0,0,0,0.2); border: 1px dashed var(--border-color); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
.suggestion-card:hover { border-color: var(--saffron); }
.preview-box { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); padding: 1rem; border-radius: 8px; margin-top: 1rem; display: none; }
.preview-box.active { display: block; animation: fadeInUp 0.3s ease; }
</style>

<div class="container" style="max-width: 1200px;">
    <div class="dashboard-header">
        <h1>डेटा मानकीकरण (Data Normalization)</h1>
        <a href="dashboard.php" class="btn btn-outline">वापस जाएं (Back)</a>
    </div>

    <?php if (typeof showToast === 'function'): // Handled by JS mostly now, but fallback here ?>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--success);">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--danger);">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom: 1.5rem;">
        <form method="GET" action="">
            <div class="form-group" style="margin: 0;">
                <label for="column" style="font-weight: 600;">अपडेट करने के लिए कॉलम चुनें (Select Column to Clean)</label>
                <select name="column" id="column" class="form-control" onchange="this.form.submit()" style="max-width: 400px;">
                    <option value="">-- चुनें (Select) --</option>
                    <?php foreach ($allowed_columns as $col => $label): ?>
                        <option value="<?= htmlspecialchars($col) ?>" <?= $selected_column === $col ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if (array_key_exists($selected_column, $allowed_columns)): ?>
        <?php if (!empty($distinct_values_counts)): ?>
            
            <div class="split-layout">
                <!-- Left: Manual Merge -->
                <div class="panel">
                    <h3 class="panel-title">मैनुअल मर्ज (Manual Merge)</h3>
                    <form method="POST" action="?column=<?= urlencode($selected_column) ?>" id="mergeForm">
                        <input type="hidden" name="action" value="merge_data">
                        <input type="hidden" name="column" value="<?= htmlspecialchars($selected_column) ?>">
                        
                        <div class="form-group">
                            <label for="target_value" style="color: var(--success); font-weight: bold;">1. लक्ष्य मान (Target Master Value)</label>
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0;">Select the correct spelling that should be kept.</p>
                            <select name="target_value" id="target_value" class="form-control" required>
                                <option value="" data-count="0">-- चुनें (Select) --</option>
                                <?php foreach ($distinct_values_counts as $val => $cnt): ?>
                                    <option value="<?= htmlspecialchars($val) ?>" data-count="<?= $cnt ?>"><?= htmlspecialchars($val) ?> (<?= $cnt ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label style="color: var(--danger); font-weight: bold;">2. मर्ज करने वाले मान (Values to Merge / Replace)</label>
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0;">Select the incorrect spellings that should be replaced.</p>
                            <div style="max-height: 250px; overflow-y: auto; background: rgba(0,0,0,0.1); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem;">
                                <?php foreach ($distinct_values_counts as $val => $cnt): ?>
                                    <div style="margin-bottom: 0.5rem;" class="merge-checkbox-wrapper">
                                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; color: var(--text-color); cursor: pointer;">
                                            <input type="checkbox" name="merge_values[]" value="<?= htmlspecialchars($val) ?>" data-count="<?= $cnt ?>" class="merge-checkbox" style="width: auto;">
                                            <?= htmlspecialchars($val) ?> <span class="badge"><?= $cnt ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Side-by-side Preview -->
                        <div id="previewBox" class="preview-box">
                            <h4 style="margin-top: 0; color: var(--success);">पूर्वावलोकन (Preview)</h4>
                            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.95rem;">
                                <div style="text-align: center; flex: 1;">
                                    <div style="color: var(--danger); font-weight: bold; margin-bottom: 0.5rem;">Replacing</div>
                                    <div id="previewSourceRecords" style="font-size: 1.5rem; font-weight: bold;">0</div>
                                    <div style="color: var(--text-muted); font-size: 0.8rem;">records</div>
                                </div>
                                <div style="flex: 0 0 50px; text-align: center;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </div>
                                <div style="text-align: center; flex: 1;">
                                    <div style="color: var(--success); font-weight: bold; margin-bottom: 0.5rem;">Target</div>
                                    <div id="previewTargetName" style="color: var(--text-color); font-weight: 600;">-</div>
                                    <div style="color: var(--text-muted); font-size: 0.8rem;">New Total: <span id="previewTotalRecords" style="font-weight: bold; color: var(--success);">0</span></div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 1.5rem;">
                            <button type="submit" id="mergeSubmitBtn" class="btn" style="width: 100%;" disabled onclick="return confirm('क्या आप निश्चित हैं? (Are you sure? This action is irreversible.)')">
                                मर्ज करें (Execute Merge)
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right: AI Suggestions -->
                <div class="panel">
                    <h3 class="panel-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 0.5rem; color: var(--saffron);"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                        संभावित डुप्लिकेट (Smart Suggestions)
                    </h3>
                    
                    <?php if (empty($suggestions)): ?>
                        <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.3; margin-bottom: 1rem;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            <p>कोई संभावित डुप्लिकेट नहीं मिला।<br>Spelling variations look clean!</p>
                        </div>
                    <?php else: ?>
                        <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0;">Here are similar spellings that might be typos. Click 'Select' to quickly load them into the manual merge panel.</p>
                        
                        <div style="max-height: 500px; overflow-y: auto; padding-right: 0.5rem;">
                            <?php foreach ($suggestions as $idx => $sug): ?>
                                <div class="suggestion-card">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                        <span style="font-size: 0.8rem; background: rgba(249, 115, 22, 0.15); color: var(--saffron); padding: 2px 8px; border-radius: 12px;">~<?= $sug['similarity'] ?>% Match</span>
                                        <button type="button" class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;" onclick="loadSuggestion('<?= htmlspecialchars(addslashes($sug['val1'])) ?>', '<?= htmlspecialchars(addslashes($sug['val2'])) ?>')">
                                            Select
                                        </button>
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                        <div style="display: flex; justify-content: space-between; background: rgba(0,0,0,0.2); padding: 0.5rem; border-radius: 4px;">
                                            <span><?= htmlspecialchars($sug['val1']) ?></span>
                                            <span class="badge"><?= $sug['count1'] ?> records</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; background: rgba(0,0,0,0.2); padding: 0.5rem; border-radius: 4px;">
                                            <span><?= htmlspecialchars($sug['val2']) ?></span>
                                            <span class="badge"><?= $sug['count2'] ?> records</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <script>
                const targetSelect = document.getElementById('target_value');
                const checkboxes = document.querySelectorAll('.merge-checkbox');
                const previewBox = document.getElementById('previewBox');
                const previewSourceRecords = document.getElementById('previewSourceRecords');
                const previewTargetName = document.getElementById('previewTargetName');
                const previewTotalRecords = document.getElementById('previewTotalRecords');
                const mergeSubmitBtn = document.getElementById('mergeSubmitBtn');

                function updatePreview() {
                    const targetVal = targetSelect.value;
                    const targetOpt = targetSelect.options[targetSelect.selectedIndex];
                    const targetCount = parseInt(targetOpt.getAttribute('data-count') || 0);
                    
                    let sourceCount = 0;
                    let hasChecked = false;

                    checkboxes.forEach(cb => {
                        // Disable the checkbox if it matches target
                        if (cb.value === targetVal && targetVal !== '') {
                            cb.checked = false;
                            cb.disabled = true;
                            cb.closest('.merge-checkbox-wrapper').style.opacity = '0.3';
                        } else {
                            cb.disabled = false;
                            cb.closest('.merge-checkbox-wrapper').style.opacity = '1';
                        }

                        if (cb.checked) {
                            sourceCount += parseInt(cb.getAttribute('data-count') || 0);
                            hasChecked = true;
                        }
                    });

                    if (targetVal !== '' && hasChecked) {
                        previewBox.classList.add('active');
                        previewSourceRecords.textContent = sourceCount;
                        previewTargetName.textContent = targetVal;
                        previewTotalRecords.textContent = (targetCount + sourceCount);
                        mergeSubmitBtn.disabled = false;
                    } else {
                        previewBox.classList.remove('active');
                        mergeSubmitBtn.disabled = true;
                    }
                }

                targetSelect.addEventListener('change', updatePreview);
                checkboxes.forEach(cb => cb.addEventListener('change', updatePreview));

                // Load suggestion into manual panel
                function loadSuggestion(val1, val2) {
                    // Decide target (the one with more records usually, but let user decide. For now, set val1 as target, check val2)
                    // Find counts
                    const opt1 = Array.from(targetSelect.options).find(o => o.value === val1);
                    const opt2 = Array.from(targetSelect.options).find(o => o.value === val2);
                    
                    const c1 = opt1 ? parseInt(opt1.getAttribute('data-count') || 0) : 0;
                    const c2 = opt2 ? parseInt(opt2.getAttribute('data-count') || 0) : 0;
                    
                    let target = val1;
                    let toMerge = val2;
                    
                    if (c2 > c1) {
                        target = val2;
                        toMerge = val1;
                    }
                    
                    // Set Target
                    targetSelect.value = target;
                    
                    // Set Checkbox
                    checkboxes.forEach(cb => {
                        cb.checked = (cb.value === toMerge);
                    });
                    
                    updatePreview();
                    
                    // Scroll to manual form
                    document.getElementById('mergeForm').scrollIntoView({behavior: 'smooth'});
                }
            </script>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 3rem;">
                <p style="color: var(--text-muted); font-size: 1.1rem;">इस कॉलम में कोई डेटा नहीं है। (No data available in this column.)</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
