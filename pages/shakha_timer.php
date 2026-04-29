<?php
require_once '../includes/auth.php';
/**
 * Shakha Timer - Automated Whistle Tool
 * Accessible by Mukhyashikshak and Admin
 */
require_once '../config/db.php';
requireLogin();

if (!isMukhyashikshak() && !isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$shakhaId = getCurrentShakhaId();
$viewDate = date('Y-m-d');
$dayOfWeek = date('w', strtotime($viewDate));

// Load Timetable
$isOverride = false;
$stmtOvr = $pdo->prepare("SELECT slots FROM timetable_overrides WHERE shakha_id = ? AND override_date = ?");
$stmtOvr->execute([$shakhaId, $viewDate]);
$ovrRow = $stmtOvr->fetch();

if ($ovrRow) {
    $slots = json_decode($ovrRow['slots'], true) ?: [];
    $isOverride = true;
} else {
    $stmtDef = $pdo->prepare("SELECT slots FROM timetable_defaults WHERE shakha_id = ? AND day_of_week = ?");
    $stmtDef->execute([$shakhaId, $dayOfWeek]);
    $defRow = $stmtDef->fetch();
    $slots = $defRow ? (json_decode($defRow['slots'], true) ?: []) : [];
}

// Auto-sort slots by start time
usort($slots, function($a, $b) {
    return $a['start_min'] <=> $b['start_min'];
});

$pageTitle = 'शाखा टाइमर';
require_once '../includes/header.php';
?>

<style>
.timer-container { max-width: 600px; margin: 0 auto; text-align: center; }
.timer-display { background: rgba(15,15,20,0.8); border: 2px solid var(--saffron); border-radius: 20px; padding: 40px 20px; margin-bottom: 20px; box-shadow: 0 0 30px rgba(255,107,0,0.2); transition: all 0.3s; }
.timer-display.running { box-shadow: 0 0 50px rgba(255,107,0,0.5); border-color: #FF9800; }
.timer-digits { font-size: 5rem; font-family: monospace; font-weight: 700; color: white; line-height: 1; margin-bottom: 10px; display: block; }
.timer-subtext { color: var(--saffron-light); font-size: 1.2rem; font-weight: 500; }

.slot-list { margin-top: 30px; text-align: left; }
.slot-item { display: flex; align-items: stretch; margin-bottom: 8px; border-radius: 10px; overflow: hidden; background: rgba(34,34,46,0.7); border: 1px solid rgba(255,107,0,0.15); transition: all 0.3s; }
.slot-item.active { background: rgba(255,107,0,0.2); border-color: var(--saffron); transform: scale(1.02); }
.slot-time { min-width: 90px; padding: 12px; background: rgba(0,0,0,0.3); color: var(--saffron-light); font-weight: 700; display: flex; align-items: center; justify-content: center; }
.slot-topic { flex: 1; padding: 12px 16px; color: var(--text-primary); display: flex; align-items: center; font-size: 1.1rem; }
.slot-item.active .slot-time { background: var(--saffron); color: white; }

.btn-massive { font-size: 1.5rem; padding: 20px 40px; border-radius: 50px; text-transform: uppercase; font-weight: 700; letter-spacing: 2px; width: 100%; box-shadow: 0 10px 20px rgba(0,0,0,0.3); transition: all 0.2s; }
.btn-start { background: linear-gradient(135deg, #FF6B00, #E65100); color: white; border: none; }
.btn-start:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(230,81,0,0.4); }
.btn-stop { background: rgba(239,83,80,0.1); color: var(--danger); border: 2px solid var(--danger); }

.controls-row { display: flex; gap: 15px; }

#debug-log { margin-top: 20px; font-size: 0.8rem; color: #888; height: 100px; overflow-y: auto; text-align: left; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px; }
</style>

<div class="page-header">
    <h1>⏱️ शाखा टाइमर</h1>
</div>

<div class="timer-container">
    
    <?php if (empty($slots)): ?>
        <div class="alert alert-warning">⚠️ आज के लिए कोई समय-सारणी निर्धारित नहीं है। टाइमर शुरू करने से पहले समय-सारणी भरें।</div>
        <a href="timetable.php?tab=override" class="btn btn-outline" style="margin-top: 20px;">📅 समय-सारणी बनाएं</a>
    <?php else: ?>
        <div class="timer-display" id="timer-box">
            <span class="timer-digits" id="timer-text">00:00</span>
            <span class="timer-subtext" id="status-text">तैयार</span>
        </div>

        <div class="controls-row">
            <button id="btn-toggle" class="btn btn-massive btn-start">🚀 शाखा शुरू करें</button>
        </div>

        <div class="slot-list" id="slot-list-container">
            <?php foreach($slots as $idx => $slot): ?>
                <div class="slot-item" id="slot-<?php echo $idx; ?>" data-start="<?php echo $slot['start_min'] * 60; ?>" data-end="<?php echo $slot['end_min'] * 60; ?>">
                    <div class="slot-time"><?php echo $slot['start_min']; ?>-<?php echo $slot['end_min']; ?> मि.</div>
                    <div class="slot-topic"><?php echo htmlspecialchars($slot['topic']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Hidden Debug Log for verifying Whistle Calls -->
        <div id="debug-log" style="display: none;"></div>
    <?php endif; ?>
</div>

<script>
<?php if (!empty($slots)): ?>
document.addEventListener('DOMContentLoaded', () => {
    const btnToggle = document.getElementById('btn-toggle');
    const timerText = document.getElementById('timer-text');
    const statusText = document.getElementById('status-text');
    const timerBox = document.getElementById('timer-box');
    const debugLog = document.getElementById('debug-log');
    
    const slots = <?php echo json_encode($slots); ?>;
    
    let isRunning = false;
    let startTime = 0;
    let timerInterval = null;
    let audioCtx = null;
    
    // Compute exact trigger times for whistles
    let timestamps = new Set();
    slots.forEach(slot => {
        if (slot.start_min > 0) timestamps.add(slot.start_min * 60);
        timestamps.add(slot.end_min * 60);
    });
    
    let scheduleEvents = [];
    let sortedTimes = Array.from(timestamps).sort((a,b) => a - b);
    sortedTimes.forEach(t => {
        scheduleEvents.push({ time: t - 30, type: 'warning', triggered: false });
        scheduleEvents.push({ time: t, type: 'long', triggered: false });
    });

    function logEvent(msg) {
        console.log(msg);
        debugLog.innerHTML += `<div>[${new Date().toLocaleTimeString()}] ${msg}</div>`;
        debugLog.scrollTop = debugLog.scrollHeight;
    }

    // Audio Generating Functions
    function initAudio() {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
    }

    function playTone(frequency, durationSec, delaySec = 0) {
        if (!audioCtx) return;
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();
        
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(frequency, audioCtx.currentTime + delaySec);
        
        // Smooth envelope to prevent harsh pops
        gainNode.gain.setValueAtTime(0, audioCtx.currentTime + delaySec);
        gainNode.gain.linearRampToValueAtTime(1, audioCtx.currentTime + delaySec + 0.05);
        gainNode.gain.setValueAtTime(1, audioCtx.currentTime + delaySec + durationSec - 0.05);
        gainNode.gain.linearRampToValueAtTime(0, audioCtx.currentTime + delaySec + durationSec);
        
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        
        oscillator.start(audioCtx.currentTime + delaySec);
        oscillator.stop(audioCtx.currentTime + delaySec + durationSec);
    }

    function playLongWhistle() {
        logEvent('📢 LONG Whistle Played (3s)');
        playTone(1000, 3.0);
    }

    function playShortWhistle(delay = 0) {
        playTone(1000, 1.0, delay);
    }

    function playWarningWhistle() {
        logEvent('⚠️ WARNING Whistle Played (Short, Short, Long - 30s remaining)');
        // Short (1s), gap (0.5s), Short (1s), gap (0.5s), Long (3s)
        playShortWhistle(0);
        playShortWhistle(1.5);
        playTone(1000, 3.0, 3.0);
    }

    function updateDisplay(elapsedSecs) {
        let m = Math.floor(elapsedSecs / 60);
        let s = Math.floor(elapsedSecs % 60);
        timerText.innerText = (m < 10 ? '0'+m : m) + ':' + (s < 10 ? '0'+s : s);
        
        // Update active slot
        let activeIdx = -1;
        document.querySelectorAll('.slot-item').forEach((item, idx) => {
            let sStart = parseFloat(item.getAttribute('data-start'));
            let sEnd = parseFloat(item.getAttribute('data-end'));
            if (elapsedSecs >= sStart && elapsedSecs < sEnd) {
                item.classList.add('active');
                activeIdx = idx;
            } else {
                item.classList.remove('active');
            }
        });
        
        if (activeIdx !== -1) {
            statusText.innerText = `वर्तमान: ${slots[activeIdx].topic}`;
        } else {
            // Check if finished
            let maxEnd = Math.max(...slots.map(s => s.end_min * 60));
            if (elapsedSecs >= maxEnd) {
                statusText.innerText = 'शाखा संपन्न';
                stopTimer(false); // Stop visually, but keep whistle running if pending
            } else {
                statusText.innerText = 'प्रतीक्षा या अंतराल...';
            }
        }
    }

    function checkWhistles(elapsedSecs) {
        scheduleEvents.forEach(evt => {
            if (!evt.triggered && elapsedSecs >= evt.time && elapsedSecs < evt.time + 3) {
                evt.triggered = true;
                if (evt.type === 'long') playLongWhistle();
                if (evt.type === 'warning') playWarningWhistle();
            }
        });
    }

    function tick() {
        let defaultTime = Date.now();
        let elapsed = Math.floor((defaultTime - startTime) / 1000);
        updateDisplay(elapsed);
        checkWhistles(elapsed);
    }

    function stopTimer(manual) {
        clearInterval(timerInterval);
        isRunning = false;
        timerBox.classList.remove('running');
        btnToggle.innerText = '🔄 टाइमर रीसेट करें';
        btnToggle.classList.remove('btn-stop');
        btnToggle.classList.add('btn-start');
        
        if (manual) {
            statusText.innerText = 'रोक दिया गया';
        }
    }

    btnToggle.addEventListener('click', () => {
        initAudio();
        
        if (!isRunning) {
            // Reset / Start
            startTime = Date.now();
            scheduleEvents.forEach(e => e.triggered = false);
            
            // Initial Whistle
            playLongWhistle();
            
            timerInterval = setInterval(tick, 200); // Check 5 times a sec
            isRunning = true;
            timerBox.classList.add('running');
            btnToggle.innerText = '⏹️ शाखा रोकें';
            btnToggle.classList.remove('btn-start');
            btnToggle.classList.add('btn-stop');
            logEvent('▶️ Timer Started');
        } else {
            // Stop
            stopTimer(true);
            logEvent('⏹️ Timer Stopped Manually');
        }
    });

});
<?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>
