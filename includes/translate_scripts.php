<?php
/**
 * Google Translate scripts — include just before </body> on every page
 * Usage: <?php include '../includes/translate_scripts.php'; ?>
 */
?>
<div id="google_translate_element" style="display:none"></div>
<div id="translate-btn" onclick="toggleTranslate()" title="भाषा बदलें / Change Language">
    🌐 <span id="translate-label">English</span>
</div>

<script>
    // Ensure the function is in global scope
    window.googleTranslateElementInit = function() {
        try {
            new google.translate.TranslateElement({
                pageLanguage: 'hi',
                includedLanguages: 'en,mr,gu,pa,bn,te,ta,kn,ml,ur',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
            console.log('Google Translate Initialized');
        } catch (e) {
            console.error('Google Translate Init Error:', e);
        }
    };

    let translated = false;
    let isWaiting = false;

    function toggleTranslate() {
        if (isWaiting) return;
        
        const select = document.querySelector('.goog-te-combo');
        if (select) {
            doToggle(select);
        } else {
            isWaiting = true;
            const btn = document.getElementById('translate-btn');
            const originalLabel = btn.innerHTML;
            btn.innerHTML = '🌐 <span style="font-size:11px">Loading...</span>';
            
            waitForTranslate(0, originalLabel);
        }
    }

    function waitForTranslate(attempts, originalLabel) {
        const select = document.querySelector('.goog-te-combo');
        const btn = document.getElementById('translate-btn');
        
        if (!select) {
            if (attempts < 20) { // Wait up to 5 seconds (20 * 250ms)
                setTimeout(() => waitForTranslate(attempts + 1, originalLabel), 250);
            } else {
                // Fail state: Make it a clickable reload button
                btn.innerHTML = '🌐 <span style="color:#d9534f;font-size:11px;font-weight:bold">Click to Retry</span>';
                btn.onclick = () => window.location.reload();
                isWaiting = false;
            }
            return;
        }
        
        // Success: Reset button and toggle
        btn.innerHTML = originalLabel;
        isWaiting = false;
        doToggle(select);
    }

    function doToggle(select) {
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
<!-- External Script with explicit protocol -->
<script src="https://translate.googleapis.com/translate_a/element.js?cb=googleTranslateElementInit" async defer></script>