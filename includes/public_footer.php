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

    function openSidePanel(title, contentHtml) {
        sidePanelTitle.innerHTML = title;
        sidePanelContent.innerHTML = contentHtml;
        sidePanel.classList.add('open');
        backdrop.classList.add('visible');
        document.body.style.overflow = 'hidden';
    }

    function closeSidePanel() {
        sidePanel.classList.remove('open');
        backdrop.classList.remove('visible');
        document.body.style.overflow = '';
    }

    if (closeBtn) closeBtn.onclick = closeSidePanel;
    if (backdrop) backdrop.onclick = closeSidePanel;

    // Handle Esc key
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeSidePanel();
    });
    </script>
</body>
</html>
