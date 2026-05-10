document.addEventListener('DOMContentLoaded', () => {
    // 1. Scroll-Reveal Text Interaction
    const revealTexts = document.querySelectorAll('.st-scroll-reveal');

    revealTexts.forEach(textEl => {
        // Split text into words
        const text = textEl.textContent.trim();
        const words = text.split(/\s+/);
        
        // Clear original text
        textEl.innerHTML = '';
        
        // Wrap each word in a span
        words.forEach(word => {
            const span = document.createElement('span');
            span.classList.add('word');
            span.textContent = word + ' ';
            textEl.appendChild(span);
        });

        const wordSpans = textEl.querySelectorAll('span.word');

        // Scroll listener for this specific element
        window.addEventListener('scroll', () => {
            const rect = textEl.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            
            // Calculate how far the element is through the viewport
            // Start revealing when the top of the element enters the bottom 20% of the screen
            const startRevealPos = viewportHeight * 0.8;
            // Finish revealing when the bottom of the element reaches the top 20% of the screen
            const endRevealPos = viewportHeight * 0.2;
            
            const elementCenter = rect.top + (rect.height / 2);

            let progress = 0;
            if (rect.top > startRevealPos) {
                progress = 0;
            } else if (rect.bottom < endRevealPos) {
                progress = 1;
            } else {
                progress = (startRevealPos - rect.top) / (startRevealPos - endRevealPos);
                // Clamp between 0 and 1
                progress = Math.max(0, Math.min(1, progress));
            }

            // Number of words that should be active based on progress
            const wordsToActivate = Math.floor(progress * wordSpans.length);

            wordSpans.forEach((span, index) => {
                if (index < wordsToActivate) {
                    span.classList.add('active');
                } else {
                    span.classList.remove('active');
                }
            });
        });
    });
});
