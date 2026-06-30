<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Allow CLI execution or Admin user execution
if (php_sapi_name() !== 'cli') {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
}

try {
    // 1. Check & Update shakhas table
    $columnsShakhas = $pdo->query("SHOW COLUMNS FROM shakhas")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('shakha_gat', $columnsShakhas)) {
        $pdo->exec("ALTER TABLE shakhas ADD COLUMN shakha_gat VARCHAR(255) DEFAULT 'Baal,Tarun,Praudh,Abhyagat'");
    } else {
        $pdo->exec("ALTER TABLE shakhas MODIFY COLUMN shakha_gat VARCHAR(255) DEFAULT 'Baal,Tarun,Praudh,Abhyagat'");
    }
    
    if (!in_array('shakha_roles', $columnsShakhas)) {
        $pdo->exec("ALTER TABLE shakhas ADD COLUMN shakha_roles TEXT NULL");
    }
    $pdo->exec("UPDATE shakhas SET shakha_roles = 'Swayamsevak, Seva Karyakarta, Mukhya Shikshak, Shakha Karyavaah, Gat Nayak' WHERE shakha_roles IS NULL OR shakha_roles = ''");
    
    if (!in_array('ai_insight_prompt', $columnsShakhas)) {
        $pdo->exec("ALTER TABLE shakhas ADD COLUMN ai_insight_prompt TEXT NULL");
    }
    
    // Populate default system prompt
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
    
    $stmt = $pdo->prepare("UPDATE shakhas SET ai_insight_prompt = ? WHERE ai_insight_prompt IS NULL OR ai_insight_prompt = ''");
    $stmt->execute([$defaultPrompt]);
    
    // 2. Check & Update swayamsevaks table
    $columnsSwayamsevaks = $pdo->query("SHOW COLUMNS FROM swayamsevaks")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('role', $columnsSwayamsevaks)) {
        $pdo->exec("ALTER TABLE swayamsevaks ADD COLUMN role VARCHAR(100) DEFAULT 'Swayamsevak'");
    }
    
    echo "<h1>Database Migration (v3) Successful</h1>";
    echo "<p>Added 'role' to swayamsevaks table.</p>";
    echo "<p>Added 'shakha_gat', 'shakha_roles', and 'ai_insight_prompt' to shakhas table.</p>";
    echo "<p><a href='../pages/dashboard.php'>Go to Dashboard</a></p>";
} catch (Exception $e) {
    echo "<h1>Migration Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
