<?php
require_once '../includes/auth.php';
/**
 * Shakha Settings - शाखा सेटिंग्स
 */
require_once '../config/db.php';
requireLogin();

if (!isAdmin() && !isMukhyashikshak()) {
    header('Location: dashboard.php');
    exit;
}

$shakhaId = getCurrentShakhaId();
if (!$shakhaId) {
    die("No Shakha assigned.");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $newName = trim($_POST['name'] ?? '');

    // Image Resize & Crop Function (1:1 Aspect Ratio)
    function processImage($sourceUrl, $destination, $ext)
    {
        $info = getimagesize($sourceUrl);
        if (!$info)
            return false;

        $width = $info[0];
        $height = $info[1];

        // Calculate crop to make it square
        $size = min($width, $height);
        $src_x = ($width - $size) / 2;
        $src_y = ($height - $size) / 2;

        $targetSize = 500;
        $targetImage = imagecreatetruecolor($targetSize, $targetSize);

        // Handle transparency for PNG and WebP
        if ($ext === 'png' || $ext === 'webp') {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
            imagefilledrectangle($targetImage, 0, 0, $targetSize, $targetSize, $transparent);
        }

        $sourceImage = null;
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $sourceImage = @imagecreatefromjpeg($sourceUrl);
                break;
            case 'png':
                $sourceImage = @imagecreatefrompng($sourceUrl);
                break;
            case 'webp':
                $sourceImage = @imagecreatefromwebp($sourceUrl);
                break;
        }

        if (!$sourceImage) {
            // For SVG or unsupported types, we just return false and use standard move
            return false;
        }

        imagecopyresampled($targetImage, $sourceImage, 0, 0, (int) $src_x, (int) $src_y, $targetSize, $targetSize, (int) $size, (int) $size);

        $success = false;
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $success = imagejpeg($targetImage, $destination, 90);
                break;
            case 'png':
                $success = imagepng($targetImage, $destination, 9);
                break;
            case 'webp':
                $success = imagewebp($targetImage, $destination, 90);
                break;
        }

        imagedestroy($targetImage);
        imagedestroy($sourceImage);
        return $success;
    }

    // Handle logo upload
    $logoPath = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $dbUploadDir = 'assets/images/uploads/';
        $uploadDir = dirname(__DIR__) . '/' . $dbUploadDir;
        if (!is_dir($uploadDir)) {
            if (!@mkdir($uploadDir, 0777, true)) {
                $error = 'सर्वर पर फ़ोल्डर बनाने की अनुमति नहीं है। कृपया अपने सर्वर पर "' . $uploadDir . '" फ़ोल्डर बनाएं और उसे 777 परमिशन्स दें। (Permission Denied)';
            }
        }

        if (empty($error)) {
            $fileInfo = pathinfo($_FILES['logo']['name']);
            $ext = strtolower($fileInfo['extension']);
            $allowed = ['jpg', 'jpeg', 'png', 'svg', 'webp'];

            if (in_array($ext, $allowed)) {
                $mimeType = mime_content_type($_FILES['logo']['tmp_name']);
                $allowedMimes = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp'];

                if (!in_array($mimeType, $allowedMimes)) {
                    $error = 'अमान्य फ़ाइल प्रकार। कृपया केवल वास्तविक छवियां अपलोड करें।';
                } else {
                    $newFileName = 'shakha_' . $shakhaId . '_' . time() . '.' . $ext;
                    $destination = $uploadDir . $newFileName;

                    // Try to resize and crop first (unless SVG)
                    $processed = false;
                    if ($ext !== 'svg') {
                        $processed = processImage($_FILES['logo']['tmp_name'], $destination, $ext);
                    }

                    // If processing failed or it's an SVG, fallback to move_uploaded_file
                    if (!$processed) {
                        if (@move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
                            $logoPath = $dbUploadDir . $newFileName;
                        } else {
                            $error = 'इमेज अपलोड करने में विफल। कृपया अपलोड फ़ोल्डर की परमिशन्स (chmod 777) चेक करें।';
                        }
                    } else {
                        $logoPath = $dbUploadDir . $newFileName;
                    }
                }
            } else {
                if (empty($error)) {
                    $error = 'केवल JPG, PNG, SVG या WEBP फॉर्मेट स्वीकार किए जाते हैं।';
                }
            }
        }
    }

    if (empty($error)) {
        $geminiKey = trim($_POST['gemini_api_key'] ?? '');
        $openaiKey = trim($_POST['openai_api_key'] ?? '');
        $useCrossCheck = isset($_POST['use_ai_crosscheck']) ? 1 : 0;
        $cityName = trim($_POST['city_name'] ?? '');
        if (!empty($newName)) {
            if ($logoPath) {
                // Also get old logo to delete if exists
                $stmt = $pdo->prepare("SELECT logo FROM shakhas WHERE id = ?");
                $stmt->execute([$shakhaId]);
                $oldLogo = $stmt->fetchColumn();
                if ($oldLogo && file_exists("../" . $oldLogo)) {
                    unlink("../" . $oldLogo);
                }

                $stmt = $pdo->prepare("UPDATE shakhas SET name = ?, logo = ?, gemini_api_key = ?, openai_api_key = ?, use_ai_crosscheck = ?, city_name = ? WHERE id = ?");
                $stmt->execute([$newName, $logoPath, $geminiKey, $openaiKey, $useCrossCheck, $cityName, $shakhaId]);
            } else {
                $stmt = $pdo->prepare("UPDATE shakhas SET name = ?, gemini_api_key = ?, openai_api_key = ?, use_ai_crosscheck = ?, city_name = ? WHERE id = ?");
                $stmt->execute([$newName, $geminiKey, $openaiKey, $useCrossCheck, $cityName, $shakhaId]);
            }
            $success = 'शाखा सेटिंग्स सफलतापूर्वक अपडेट कर दी गईं।';
        } else {
            $error = 'कृपया शाखा का नाम दर्ज करें।';
        }
    }
}

// Fetch current details
$stmt = $pdo->prepare("SELECT * FROM shakhas WHERE id = ?");
$stmt->execute([$shakhaId]);
$shakha = $stmt->fetch();

$pageTitle = 'शाखा सेटिंग्स';
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>⚙️ शाखा सेटिंग्स</h1>
    <a href="../pages/dashboard.php" class="btn btn-outline btn-sm">◀ वापस जाएँ</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">✅
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger">⚠️
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">अपनी शाखा का विवरण अपडेट करें</div>
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <div class="form-group">
            <label for="name">शाखा का नाम</label>
            <input type="text" id="name" name="name" class="form-control"
                value="<?php echo htmlspecialchars($shakha['name']); ?>" required>
        </div>

        <div class="form-group">
            <label for="city_name">शहर का नाम (पंचांग के लिए)</label>
            <input type="text" id="city_name" name="city_name" class="form-control"
                value="<?php echo htmlspecialchars($shakha['city_name'] ?? 'मुम्बई'); ?>" placeholder="उदा. मुम्बई, पुणे, दिल्ली (हिंदी में लिखें)">
            <small style="color: #888;">AI इसी शहर के अनुसार पंचांग की गणना करेगा। कृपया शहर का नाम **हिंदी** में ही लिखें।</small>
        </div>

        <div class="form-group">
            <label>वर्तमान लोगो (स्नैपशॉट के लिए)</label>
            <div style="margin-bottom:10px;">
                <?php if (!empty($shakha['logo']) && file_exists("../" . $shakha['logo'])): ?>
                    <img src="../<?php echo htmlspecialchars($shakha['logo']); ?>" alt="Logo" loading="lazy"
                        style="max-height: 100px; border-radius: 8px;">
                <?php else: ?>
                    <img src="../assets/images/logo.svg" alt="Default Logo" style="max-height: 100px; border-radius: 8px;" loading="lazy">
                    <p class="small-text">डिफ़ॉल्ट लोगो का उपयोग किया जा रहा है।</p>
                <?php endif; ?>
            </div>
            <label for="logo">नया लोगो अपलोड करें (वैकल्पिक)</label>
            <input type="file" id="logo" name="logo" class="form-control"
                accept="image/jpeg, image/png, image/svg+xml, image/webp">
            <small style="color: #666;">इमेज स्वचालित रूप से 500x500 पिक्सल के वर्गाकार (Square) आकार में क्रॉप कर दी
                जाएगी। (स्वचालित आकार विकल्प सक्रिय)</small>
        </div>

        <div class="form-group" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px;">
            <label for="gemini_api_key">Gemini AI API Key</label>
            <input type="password" id="gemini_api_key" name="gemini_api_key" class="form-control" 
                value="<?php echo htmlspecialchars($shakha['gemini_api_key'] ?? ''); ?>" 
                placeholder="AI फीचर्स के लिए अपनी Gemini API Key डालें">
            <small style="color: #888; display: block; margin-top: 6px;">
                यदि आप इसे खाली छोड़ते हैं, तो सिस्टम की डिफ़ॉल्ट API Key का उपयोग किया जाएगा। 
                <a href="https://aistudio.google.com/app/apikey" target="_blank" style="color: var(--saffron);">अपनी API Key यहाँ से प्राप्त करें</a>
            </small>
        </div>

        <div class="form-group" style="margin-top: 10px;">
            <label for="openai_api_key">OpenAI API Key (Cross-check के लिए)</label>
            <input type="password" id="openai_api_key" name="openai_api_key" class="form-control" 
                value="<?php echo htmlspecialchars($shakha['openai_api_key'] ?? ''); ?>" 
                placeholder="OpenAI API Key डालें">
            <small style="color: #888; display: block; margin-top: 6px;">
                यह वैकल्पिक है। यदि आप इसे डालते हैं, तो AI परिणामों की तुलना (Cross-check) की जा सकेगी।
            </small>
        </div>

        <div class="form-group" style="margin-top: 10px;">
            <label class="checkbox-container" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" name="use_ai_crosscheck" value="1" <?php echo ($shakha['use_ai_crosscheck'] ?? 0) ? 'checked' : ''; ?> style="width: 20px; height: 20px;">
                <span>AI Cross-check सक्रिय करें (सटीकता के लिए दो AI का उपयोग करें)</span>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 सहेजें</button>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>