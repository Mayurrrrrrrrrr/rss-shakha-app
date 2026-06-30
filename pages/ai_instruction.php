<?php
require_once '../includes/auth.php';
/**
 * AI Analysis Instruction Settings - AI विश्लेषण निर्देश
 */
require_once '../config/db.php';
requireLogin();

if (!isAdmin() && !isMukhyashikshak()) {
    header('Location: dashboard.php');
    exit;
}

$shakhaId = getCurrentShakhaId();
if ($shakhaId === null) {
    die("No Shakha assigned.");
}

$success = '';
$error = '';

$defaultPrompt = "तुम एक RSS शाखा (Rashtriya Swayamsevak Sangh शाखा) के data analyst हो। तुम्हें शाखा की गतिविधियों का विश्लेषण करना है और actionable insights देनी हैं।

महत्वपूर्ण संदर्भ:
- स्वयंसेवकों की श्रेणियाँ: बाल (Baal - बच्चे), तरुण (Tarun - युवा), प्रौढ़ (Praudh - वयस्क)
- अभ्यागत (Abhyagat) शाखा के सदस्य नहीं हैं — वे अतिथि/guest हैं। वे कभी-कभी आते हैं, अपने अनुभव साझा करते हैं, और कभी-कभी वे अधिकारी (senior RSS functionaries) भी होते हैं। अभ्यागतों का आना शाखा के लिए अच्छा है, उन्हें नियमित सदस्य बनाने का सुझाव मत दो।
- नए सदस्य = बाल/तरुण/प्रौढ़ श्रेणी में जुड़े लोग (अभ्यागत नहीं)

तुम्हें Hindi (Devanagari) में जवाब देना है। Response को इन sections में बांटो:

📊 **कुल सारांश (Overall Summary)**
- कितने दिन शाखा लगी, कुल उपस्थिति का overview, नए सदस्य कितने जुड़े, अभ्यागत कितनी बार आए

👥 **उपस्थिति विश्लेषण (Attendance Analysis)**  
- सबसे नियमित स्वयंसेवक (top 5)
- सबसे कम आने वाले (need attention)
- श्रेणी-वार (Baal/Tarun/Praudh) breakdown
- अभ्यागतों की उपस्थिति (positive highlight — ये अतिथि हैं)

🆕 **नए सदस्य (New Members)**
- कितने नए स्वयंसेवक जुड़े (बाल/तरुण/प्रौढ़ में), अभ्यागत गिनती अलग रखो
- ग्रोथ रेट पर टिप्पणी

📋 **गतिविधि विश्लेषण (Activity Analysis)**
- सबसे ज्यादा होने वाली गतिविधियाँ
- छूटने वाली गतिविधियाँ  
- सबसे सक्रिय संचालक

📅 **समय-सारणी पालन (Schedule Adherence)**
- निर्धारित समय-सारणी (timetable) की तुलना में वास्तविक गतिविधियों का पालन कितना हुआ
- कौन से निर्धारित विषय सबसे ज्यादा छूटे
- अनुशासन और नियमितता पर अवलोकन

💬 **विषय-वस्तु अंतर्दृष्टि (Content Insights)**
- विशेष सूचना, सुभाषित, notices से themes/patterns

🎯 **सुझाव (Recommendations)**
- उपस्थिति बढ़ाने के उपाय
- समय-सारणी पालन सुधार
- नए सदस्य (बाल/तरुण/प्रौढ़) जोड़ने के लिए सुझाव
- अभ्यागतों को और बुलाने के सुझाव (ये सम्मानित अतिथि हैं)
- 3-4 actionable suggestions

Response concise रखो, bullet points use करो, और data-driven insights दो। If data is limited, work with what's available and mention that more data would give better insights.";

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    if (isset($_POST['reset'])) {
        $stmt = $pdo->prepare("UPDATE shakhas SET ai_insight_prompt = ? WHERE id = ?");
        $stmt->execute([$defaultPrompt, $shakhaId]);
        $success = 'AI विश्लेषण निर्देश को सफलतापूर्वक डिफ़ॉल्ट पर रीसेट कर दिया गया है।';
    } else {
        $prompt = trim($_POST['ai_insight_prompt'] ?? '');
        if (empty($prompt)) {
            $error = 'निर्देश खाली नहीं हो सकते।';
        } else {
            $stmt = $pdo->prepare("UPDATE shakhas SET ai_insight_prompt = ? WHERE id = ?");
            $stmt->execute([$prompt, $shakhaId]);
            $success = 'AI विश्लेषण निर्देश सफलतापूर्वक सहेजे गए।';
        }
    }
}

// Fetch current prompt
$stmt = $pdo->prepare("SELECT ai_insight_prompt FROM shakhas WHERE id = ?");
$stmt->execute([$shakhaId]);
$currentPrompt = $stmt->fetchColumn();

if (empty($currentPrompt)) {
    $currentPrompt = $defaultPrompt;
}

$pageTitle = 'AI विश्लेषण निर्देश';
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>🧠 Gemini AI विश्लेषण निर्देश (AI Instruction)</h1>
    <a href="../pages/insights.php" class="btn btn-outline btn-sm">◀ AI Insights पर जाएं</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">📋 विश्लेषण निर्देश कस्टमाइज़ करें</div>
    <div style="padding: 20px;">
        <p style="margin-top: 0; color: #555; font-size: 0.95rem; line-height: 1.6;">
            यहाँ आप वह निर्देश (System Prompt) बदल सकते हैं जो Gemini AI को शाखा डेटा का विश्लेषण करते समय दिया जाता है। 
            आप निर्देश में यह तय कर सकते हैं कि रिपोर्ट में कौन-कौन से बिंदु शामिल हों, AI की भाषा कैसी हो, और कौन-सी जानकारियों को प्राथमिकता दी जाए।
        </p>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="form-group">
                <label for="ai_insight_prompt" style="font-weight: 600; display: block; margin-bottom: 8px;">AI को निर्देश (Analysis Prompt) *</label>
                <textarea id="ai_insight_prompt" name="ai_insight_prompt" class="form-control" rows="22" required style="font-family: 'Courier New', Courier, monospace; font-size: 0.95rem; line-height: 1.5; padding: 12px;"><?php echo htmlspecialchars($currentPrompt); ?></textarea>
            </div>
            
            <div class="form-actions" style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">💾 निर्देश सहेजें</button>
                <button type="submit" name="reset" value="1" class="btn btn-outline" onclick="return confirm('क्या आप वाकई निर्देश को डिफ़ॉल्ट पर रीसेट करना चाहते हैं?')" style="color: #c0392b; border-color: #c0392b;">🔄 डिफ़ॉल्ट पर रीसेट करें</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
