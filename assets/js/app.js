// =============================================
// RSS Shakha Management - Client-Side JS
// =============================================

document.addEventListener('DOMContentLoaded', () => {
    // Mobile nav toggle
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
        // Close menu on link click (mobile)
        navMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => navMenu.classList.remove('active'));
        });
    }

    // Auto-dismiss alerts after 4 seconds
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 4000);
    });

    // Confirm delete actions
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            if (!confirm(el.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
});

/**
 * Share snapshot on WhatsApp
 */
async function shareOnWhatsApp(imageUrl, text) {
    // Try native Web Share API first (works on mobile)
    if (navigator.share && navigator.canShare) {
        try {
            const response = await fetch(imageUrl);
            const blob = await response.blob();
            const file = new File([blob], 'shakha_record.jpg', { type: 'image/jpeg' });
            
            if (navigator.canShare({ files: [file] })) {
                await navigator.share({
                    title: 'शाखा दैनिक रिपोर्ट',
                    text: text || 'शाखा दैनिक रिपोर्ट',
                    files: [file]
                });
                return;
            }
        } catch (err) {
            if (err.name !== 'AbortError') {
                console.log('Web Share API failed, falling back...');
            } else {
                return; // User cancelled
            }
        }
    }
    
    // Fallback: Open WhatsApp with text (image must be shared separately)
    const whatsappUrl = 'https://wa.me/?text=' + encodeURIComponent(text || 'शाखा दैनिक रिपोर्ट');
    window.open(whatsappUrl, '_blank');
}

/**
 * Download the snapshot image
 */
function downloadSnapshot(imageUrl, filename) {
    const a = document.createElement('a');
    a.href = imageUrl;
    a.download = filename || 'shakha_record.jpg';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

/**
 * Hindi month names
 */
const HINDI_MONTHS = [
    'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून',
    'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'
];

const HINDI_DAYS = ['रवि', 'सोम', 'मंगल', 'बुध', 'गुरु', 'शुक्र', 'शनि'];

/**
 * Format date in Hindi
 */
function formatDateHindi(dateStr) {
    const d = new Date(dateStr);
    return d.getDate() + ' ' + HINDI_MONTHS[d.getMonth()] + ' ' + d.getFullYear();
}
