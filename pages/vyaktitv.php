<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();

if (!isAdmin() && !isMukhyashikshak()) {
    header('Location: dashboard.php');
    exit;
}

$success = '';
$error = '';

$pId = $_GET['id'] ?? null;
$name = '';
$title = '';
$description = '';
$display_order = 0;

if ($pId) {
    $stmt = $pdo->prepare("SELECT * FROM personalities WHERE id = ?");
    $stmt->execute([$pId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $name = $existing['name'];
        $title = $existing['title'];
        $description = $existing['description'];
        $display_order = $existing['display_order'];
    } else {
        $pId = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $idToSave = $_POST['personality_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $display_order = (int)($_POST['display_order'] ?? 0);

    // Image Upload Handling
    $imagePath = null;
    if (isset($_FILES['personality_image']) && $_FILES['personality_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['personality_image']['tmp_name'];
        $fileName = $_FILES['personality_image']['name'];
        $fileSize = $_FILES['personality_image']['size'];
        $fileType = $_FILES['personality_image']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedfileExtensions)) {
            $uploadFileDir = '../assets/images/personalities/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            $newFileName = 'p_' . ($idToSave ?: time()) . '.png'; // Store as PNG for consistency
            $dest_path = $uploadFileDir . $newFileName;

            // Resize and Crop logic
            if (resizeAndCropImage($fileTmpPath, $dest_path, 600, 600)) {
                $imagePath = '/assets/images/personalities/' . $newFileName;
            } else {
                $error = "इमेज को रिसाइज करने में विफल।";
            }
        } else {
            $error = "केवल JPG, PNG और WEBP इमेज की अनुमति है।";
        }
    }

    if ($name && $description) {
        try {
            if ($idToSave) {
                if ($imagePath) {
                    $stmt = $pdo->prepare("UPDATE personalities SET name = ?, title = ?, description = ?, display_order = ?, image_path = ? WHERE id = ?");
                    $stmt->execute([$name, $title, $description, $display_order, $imagePath, $idToSave]);
                } else {
                    $stmt = $pdo->prepare("UPDATE personalities SET name = ?, title = ?, description = ?, display_order = ? WHERE id = ?");
                    $stmt->execute([$name, $title, $description, $display_order, $idToSave]);
                }
                $success = "व्यक्तित्व सफलतापूर्वक अपडेट किया गया!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO personalities (name, title, description, display_order, image_path) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $title, $description, $display_order, $imagePath]);
                $success = "व्यक्तित्व सफलतापूर्वक सहेजा गया!";
                $name = ''; $title = ''; $description = ''; $display_order = 0; $pId = null;
            }
        } catch (Exception $e) {
            $error = "त्रुटि: " . $e->getMessage();
        }
    } else {
        $error = "नाम और विवरण आवश्यक हैं।";
    }
}

/**
 * Resize and center crop image
 */
function resizeAndCropImage($source_path, $destination_path, $target_width, $target_height) {
    list($width, $height, $type) = getimagesize($source_path);
    
    switch ($type) {
        case IMAGETYPE_JPEG: $src_img = imagecreatefromjpeg($source_path); break;
        case IMAGETYPE_PNG:  $src_img = imagecreatefrompng($source_path); break;
        case IMAGETYPE_WEBP: $src_img = imagecreatefromwebp($source_path); break;
        default: return false;
    }

    if (!$src_img) return false;

    $src_aspect = $width / $height;
    $target_aspect = $target_width / $target_height;

    if ($src_aspect > $target_aspect) {
        // Source is wider than target
        $temp_height = $height;
        $temp_width = $height * $target_aspect;
        $src_x = ($width - $temp_width) / 2;
        $src_y = 0;
    } else {
        // Source is taller than target
        $temp_width = $width;
        $temp_height = $width / $target_aspect;
        $src_x = 0;
        $src_y = ($height - $temp_height) / 2;
    }

    $dst_img = imagecreatetruecolor($target_width, $target_height);
    
    // Preserve transparency for PNG
    imagealphablending($dst_img, false);
    imagesavealpha($dst_img, true);
    $transparent = imagecolorallocatealpha($dst_img, 255, 255, 255, 127);
    imagefilledrectangle($dst_img, 0, 0, $target_width, $target_height, $transparent);

    imagecopyresampled($dst_img, $src_img, 0, 0, $src_x, $src_y, $target_width, $target_height, $temp_width, $temp_height);

    imagepng($dst_img, $destination_path, 9); // Save as PNG with compression 9
    
    imagedestroy($src_img);
    imagedestroy($dst_img);
    
    return true;
}

// Fetch all personalities
$stmt = $pdo->query("SELECT * FROM personalities ORDER BY display_order ASC, name ASC");
$personalities = $stmt->fetchAll();

$pageTitle = 'व्यक्तित्व प्रबंधन (Personality Management)';
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>🚩 व्यक्तित्व (Vyaktitv)</h1>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

<div style="display: flex; gap: 20px; flex-wrap: wrap;">
    <div style="flex: 2; min-width: 300px;">
        <div class="card">
            <div class="card-header"><?php echo $pId ? 'व्यक्तित्व अपडेट करें' : 'नया व्यक्तित्व जोड़ें'; ?></div>
            <form method="POST" action="vyaktitv.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="personality_id" value="<?php echo htmlspecialchars($pId ?? ''); ?>">
                
                <div class="form-group">
                    <label>नाम (Name) <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($name); ?>">
                </div>

                <div class="form-group">
                    <label>शीर्षक/काल (Title/Period)</label>
                    <input type="text" name="title" class="form-control" placeholder="उदा. 1889–1940" value="<?php echo htmlspecialchars($title); ?>">
                </div>

                <div class="form-group">
                    <label>विवरण (Description) <span class="required">*</span></label>
                    <textarea name="description" class="form-control" rows="10" required><?php echo htmlspecialchars($description); ?></textarea>
                </div>

                <div class="form-group">
                    <label>क्रम (Display Order)</label>
                    <input type="number" name="display_order" class="form-control" value="<?php echo $display_order; ?>">
                </div>

                <div class="form-group">
                    <label>फोटो अपलोड करें (Upload Photo)</label>
                    <input type="file" name="personality_image" class="form-control" accept="image/*">
                    <small style="color: #666;">फोटो को आटोमैटिकली 600x600 साइज में क्रॉप कर दिया जाएगा।</small>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">💾 सहेजें (Save)</button>
                <?php if ($pId): ?><a href="vyaktitv.php" class="btn btn-outline" style="width: 100%; text-align: center; display: block; margin-top: 10px;">➕ नया जोड़ें</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div style="flex: 1; min-width: 250px;">
        <div class="card">
            <div class="card-header">सूची</div>
            <div class="list-group" style="max-height: 600px; overflow-y: auto;">
                <?php foreach ($personalities as $p): ?>
                    <a href="vyaktitv.php?id=<?php echo $p['id']; ?>" class="list-group-item" style="display: block; padding: 10px; border-bottom: 1px solid #eee; text-decoration: none; color: inherit; background: <?php echo ($pId == $p['id']) ? '#FFF3E0' : 'transparent'; ?>">
                        <strong><?php echo htmlspecialchars($p['name']); ?></strong><br>
                        <small style="color: #666;"><?php echo htmlspecialchars($p['title']); ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
