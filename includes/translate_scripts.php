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
        // Wait up to 3 seconds for widget to be ready
        waitForTranslate(0);
    }

    function waitForTranslate(attempts) {
        const select = document.querySelector('.goog-te-combo');
        if (!select) {
            if (attempts < 15) {
                setTimeout(() => waitForTranslate(attempts + 1), 200);
            } else {
                // Still not ready after 3s — show friendly message, no alert
                const btn = document.getElementById('translate-btn');
                btn.innerHTML = '🌐 <span style="color:red;font-size:11px">Reload page</span>';
            }
            return;
        }
        // Widget is ready — do the translation
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