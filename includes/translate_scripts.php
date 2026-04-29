<?php
/**
 * Google Translate scripts — include just before </body> on every page
 * Usage: <?php include '../includes/translate_scripts.php'; ?>
 */
?>
<div id="google_translate_element"></div>
<div id="translate-btn" onclick="toggleTranslate()" title="भाषा बदलें / Change Language">
    🌐 <span id="translate-label">English</span>
</div>

<script>
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'hi',
            includedLanguages: 'en,mr,gu,pa,bn,te,ta,kn,ml,ur',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false
        }, 'google_translate_element');
    }

    let translated = false;
    function toggleTranslate() {
        const select = document.querySelector('.goog-te-combo');
        if (!select) {
            alert('अनुवाद लोड हो रहा है... कृपया 2 सेकंड बाद पुनः प्रयास करें।');
            return;
        }
        if (!translated) {
            select.value = 'en';
            select.dispatchEvent(new Event('change'));
            document.getElementById('translate-label').textContent = 'हिन्दी';
            translated = true;
        } else {
            select.value = '';
            select.dispatchEvent(new Event('change'));
            document.getElementById('translate-label').textContent = 'English';
            translated = false;
        }
    }
</script>
<script src="https://translate.googleapis.com/translate_a/element.js?cb=googleTranslateElementInit"></script>