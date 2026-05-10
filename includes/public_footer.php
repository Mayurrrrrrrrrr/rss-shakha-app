    </main>

    <!-- Side Panel -->
    <aside class="side-panel" id="side-panel">
        <div class="side-panel-header">
            <button id="close-side-panel" class="close-btn" aria-label="बंद करें">✕</button>
            <div id="side-panel-title" class="side-panel-title"></div>
        </div>
        <div class="side-panel-inner" id="side-panel-content">
        </div>
    </aside>
    <div class="backdrop" id="backdrop"></div>

    <footer class="home-footer">
        <div class="footer-inner">
            <div class="footer-ornament">
                <span class="ornament-line"></span>
                <span class="ornament-symbol">॥</span>
                <span class="ornament-line"></span>
            </div>
            <p class="footer-text">॥ भारत माता की जय ॥</p>
            <div style="margin-bottom: 15px; font-size: 0.75rem; opacity: 0.6;">
                <a href="/terms.php" style="color: inherit; text-decoration: none;">नियम और शर्तें</a> | 
                <a href="/privacy.php" style="color: inherit; text-decoration: none;">गोपनीयता नीति</a>
            </div>
            <a href="/home.php" class="footer-back">← संघस्थान मुख्य पृष्ठ</a>
        </div>
    </footer>

    <script>
    const hamburger = document.getElementById('nav-hamburger');
    const navLinks = document.getElementById('nav-links');
    if (hamburger) hamburger.addEventListener('click', () => { navLinks.classList.toggle('open'); hamburger.classList.toggle('active'); });

    // Side Panel Logic
    const sidePanel = document.getElementById('side-panel');
    const sidePanelContent = document.getElementById('side-panel-content');
    const sidePanelTitle = document.getElementById('side-panel-title');
    const backdrop = document.getElementById('backdrop');
    const closeBtn = document.getElementById('close-side-panel');

    window.openSidePanel = function(title, contentHtml) {
        if (!sidePanel || !sidePanelContent) return;
        sidePanelTitle.innerHTML = title;
        sidePanelContent.innerHTML = contentHtml;
        sidePanel.classList.add('open');
        backdrop.classList.add('visible');
        document.body.style.overflow = 'hidden';
    };

    window.closeSidePanel = function() {
        if (!sidePanel) return;
        sidePanel.classList.remove('open');
        backdrop.classList.remove('visible');
        document.body.style.overflow = '';
    };

    if (closeBtn) closeBtn.onclick = window.closeSidePanel;
    if (backdrop) backdrop.onclick = window.closeSidePanel;

    // Handle global click for data-attributes (Better than inline onclick)
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-side-title]');
        if (trigger) {
            const title = trigger.getAttribute('data-side-title');
            const content = trigger.getAttribute('data-side-content');
            window.openSidePanel(title, content);
        }
    });

    // Handle Esc key
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') window.closeSidePanel();
    });

    // Deep Linking Support: Auto-open item if hash is present (e.g. #item-123)
    window.addEventListener('load', () => {
        const hash = window.location.hash;
        if (hash && hash.startsWith('#item-')) {
            const targetId = hash.substring(1);
            const targetEl = document.getElementById(targetId);
            if (targetEl) {
                setTimeout(() => {
                    targetEl.click();
                    targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 500);
            }
        }
    });
    </script>
</body>
</html>
