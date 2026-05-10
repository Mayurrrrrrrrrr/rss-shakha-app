$files = @("pages/subhashit.php", "pages/subhashit_view.php")

$cssReplacement = @"
    /* ===== PREMIUM SUBHASHIT CAPTURE FRAME ===== */
    .sub-capture-container {
        background: linear-gradient(135deg, #aa771c, #fcf6ba, #aa771c);
        padding: 12px;
        border-radius: 4px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        width: 100%;
        max-width: 550px;
        margin: 0 auto;
        position: relative;
        font-family: 'Noto Serif Devanagari', serif;
    }
    .sub-capture-inner {
        background: #fff9e3;
        background-image: radial-gradient(circle at 50% 10%, #fffdf5 0%, #fff9e3 70%);
        border: 2px solid #5a4408;
        padding: 30px 20px;
        position: relative;
        overflow: hidden;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .corner-svg { position: absolute; width: 80px; height: 80px; fill: #8b6b0d; z-index: 5; pointer-events: none; }
    .tl { top: 0; left: 0; }
    .tr { top: 0; right: 0; transform: scaleX(-1); }
    .bl { bottom: 0; left: 0; transform: scaleY(-1); }
    .br { bottom: 0; right: 0; transform: scale(-1); }
    .flag-wrap { position: absolute; top: 15px; width: 40px; height: 60px; z-index: 10; }
    .flag-l { left: 15px; }
    .flag-r { right: 15px; transform: scaleX(-1); }
    .pole { width: 3px; height: 100%; background: linear-gradient(to right, #444, #888, #333); position: absolute; left: 0; }
    .dhwaj { position: absolute; left: 3px; width: 35px; height: 25px; fill: #ff8c00; transform-origin: left center; }
    
    .om-text { font-size: 55px; color: #cc0000; font-weight: 900; letter-spacing: 2px; margin-bottom: -5px; z-index: 2; line-height: 1; }
    
    .meta-info { display: flex; flex-direction: column; align-items: center; text-align: center; margin: 5px 0 10px; z-index: 2; }
    .meta-date-panchang { font-size: 13px; font-weight: 700; color: #5a4408; line-height: 1.3; }
    .meta-shakha { font-size: 15px; font-weight: 900; color: #880E4F; margin-top: 4px; }
    
    .title-container { display: flex; align-items: center; justify-content: center; gap: 15px; margin: 10px 0; z-index: 2; }
    .wing { width: 35px; height: 35px; fill: #cc0000; }
    .main-title { font-size: clamp(35px, 8vw, 55px); font-weight: 900; color: #002266; margin: 0; text-shadow: 2px 2px 0 #fff, -1px -1px 0 #fff, 1px -1px 0 #fff, -1px 1px 0 #fff, 2px 4px 5px rgba(0,0,0,0.1); }
    .divider-svg { width: 80%; height: 20px; margin: 5px 0; z-index: 2; }
    .shlok-text { text-align: center; font-size: clamp(22px, 5.5vw, 32px); line-height: 1.5; font-weight: 800; color: #000000; margin: 20px 0; z-index: 2; white-space: pre-wrap; word-break: break-word; }
    .arth-box { display: flex; align-items: center; justify-content: center; gap: 15px; width: 95%; margin-top: 20px; z-index: 2; flex-wrap: wrap; }
    .arth-label { background: #610000; color: #ffea00; padding: 6px 20px; border-radius: 20px 4px 20px 4px; font-weight: 900; font-size: 20px; border: 1px solid #910000; }
    .arth-text { font-size: clamp(18px, 4.5vw, 22px); color: #002266; font-weight: 700; line-height: 1.5; text-align: center; flex: 1; min-width: 250px; white-space: pre-wrap; word-break: break-word; }
    
    .shabdarth-box { width: 90%; margin-top: 20px; z-index: 2; display: flex; flex-direction: column; align-items: center; }
    .shabdarth-title { font-size: 16px; font-weight: 800; color: #8b6b0d; margin-bottom: 8px; border-bottom: 1px dashed #8b6b0d; padding-bottom: 2px; }
    .shabdarth-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px; width: 100%; text-align: center; }
    .shabd-row { font-size: 15px; color: #002266; display: flex; justify-content: center; gap: 5px; }
    .shabd-word { font-weight: 900; color: #610000; }
    
    .bottom-ornament { margin-top: 20px; width: 150px; height: 15px; fill: #bf953f; }
"@

$htmlReplacementView = @"
        <div id="capture-area" class="sub-capture-container">
            <div class="sub-capture-inner">
                <svg class="corner-svg tl" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
                <svg class="corner-svg tr" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
                <svg class="corner-svg bl" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
                <svg class="corner-svg br" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>

                <div class="flag-wrap flag-l"><div class="pole"></div><svg class="dhwaj" viewBox="0 0 100 80"><path d="M0,0 L100,25 L20,40 L100,55 L0,80 Z" /></svg></div>
                <div class="flag-wrap flag-r"><div class="pole"></div><svg class="dhwaj" viewBox="0 0 100 80"><path d="M0,0 L100,25 L20,40 L100,55 L0,80 Z" /></svg></div>

                <div class="om-text">॥ ॐ ॥</div>

                <div class="meta-info">
                    <div class="meta-date-panchang">
                        <?php echo formatHindiDateSub(`$latest['subhashit_date']); ?><br>
                        <?php echo htmlspecialchars(`$latest['panchang_text'] ?? ''); ?>
                    </div>
                    <div class="meta-shakha">🚩 <?php echo htmlspecialchars(`$shakhaName); ?> 🚩</div>
                </div>

                <svg class="divider-svg" viewBox="0 0 400 20"><path d="M50,10 L350,10" fill="none" stroke="#bf953f" stroke-width="1.5" /><circle cx="200" cy="10" r="4" fill="#cc0000" /></svg>

                <div class="title-container">
                    <svg class="wing" viewBox="0 0 100 100"><path d="M100,50 Q70,20 40,50 Q70,80 100,50 M80,50 Q60,35 40,50 Q60,65 80,50 M40,50 Q20,40 0,50 Q20,60 40,50" /></svg>
                    <h1 class="main-title">सुभाषित</h1>
                    <svg class="wing" style="transform: scaleX(-1)" viewBox="0 0 100 100"><path d="M100,50 Q70,20 40,50 Q70,80 100,50 M80,50 Q60,35 40,50 Q60,65 80,50 M40,50 Q20,40 0,50 Q20,60 40,50" /></svg>
                </div>

                <svg class="divider-svg" viewBox="0 0 400 20"><path d="M100,10 L300,10" fill="none" stroke="#cc0000" stroke-width="1" /><path d="M200,2 L200,18" stroke="#bf953f" stroke-width="2" /></svg>

                <div id="prev-sanskrit" class="shlok-text"><?php echo nl2br(htmlspecialchars(`$latest['sanskrit_text'])); ?></div>

                <svg class="divider-svg" viewBox="0 0 400 20"><path d="M50,10 L350,10" fill="none" stroke="#bf953f" stroke-width="1.5" stroke-dasharray="5 5" /><circle cx="200" cy="10" r="5" fill="#bf953f" /></svg>

                <?php if (!empty(`$latest['hindi_meaning'])): ?>
                    <div id="prev-hindi-section" class="arth-box">
                        <div class="arth-label">अर्थ :-</div>
                        <div id="prev-hindi" class="arth-text"><?php echo nl2br(htmlspecialchars(`$latest['hindi_meaning'])); ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty(`$shabdarth)): ?>
                    <div id="prev-shabdarth-section" class="shabdarth-box">
                        <div class="shabdarth-title">— शब्दार्थ —</div>
                        <div id="prev-shabdarth" class="shabdarth-grid">
                            <?php foreach (`$shabdarth as `$pair): ?>
                                <div class="shabd-row">
                                    <span class="shabd-word"><?php echo htmlspecialchars(`$pair['shabd']); ?></span>
                                    <span>—</span>
                                    <span style="color:#002266; font-weight:400;"><?php echo htmlspecialchars(`$pair['arth']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <svg class="bottom-ornament" viewBox="0 0 200 20"><path d="M0,10 Q100,0 200,10" fill="none" stroke="currentColor" stroke-width="2" /><circle cx="100" cy="10" r="4" fill="#cc0000" /></svg>
            </div>
        </div>
"@

$htmlReplacementEdit = @"
            <div id="capture-area" class="sub-capture-container">
                <div class="sub-capture-inner">
                    <svg class="corner-svg tl" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
                    <svg class="corner-svg tr" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
                    <svg class="corner-svg bl" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>
                    <svg class="corner-svg br" viewBox="0 0 100 100"><path d="M0,0 Q50,0 50,50 Q0,50 0,0 M10,10 Q40,10 40,40 Q10,40 10,10 M0,20 Q20,20 20,40 M20,0 Q20,20 40,20" /></svg>

                    <div class="flag-wrap flag-l"><div class="pole"></div><svg class="dhwaj" viewBox="0 0 100 80"><path d="M0,0 L100,25 L20,40 L100,55 L0,80 Z" /></svg></div>
                    <div class="flag-wrap flag-r"><div class="pole"></div><svg class="dhwaj" viewBox="0 0 100 80"><path d="M0,0 L100,25 L20,40 L100,55 L0,80 Z" /></svg></div>

                    <div class="om-text">॥ ॐ ॥</div>

                    <div class="meta-info">
                        <div class="meta-date-panchang">
                            <span id="prev-date"></span><br>
                            <span id="prev-panchang"></span>
                        </div>
                        <div class="meta-shakha">🚩 <span id="prev-shakha-name"><?php echo htmlspecialchars(`$shakhaName); ?></span> 🚩</div>
                    </div>

                    <svg class="divider-svg" viewBox="0 0 400 20"><path d="M50,10 L350,10" fill="none" stroke="#bf953f" stroke-width="1.5" /><circle cx="200" cy="10" r="4" fill="#cc0000" /></svg>

                    <div class="title-container">
                        <svg class="wing" viewBox="0 0 100 100"><path d="M100,50 Q70,20 40,50 Q70,80 100,50 M80,50 Q60,35 40,50 Q60,65 80,50 M40,50 Q20,40 0,50 Q20,60 40,50" /></svg>
                        <h1 class="main-title">सुभाषित</h1>
                        <svg class="wing" style="transform: scaleX(-1)" viewBox="0 0 100 100"><path d="M100,50 Q70,20 40,50 Q70,80 100,50 M80,50 Q60,35 40,50 Q60,65 80,50 M40,50 Q20,40 0,50 Q20,60 40,50" /></svg>
                    </div>

                    <svg class="divider-svg" viewBox="0 0 400 20"><path d="M100,10 L300,10" fill="none" stroke="#cc0000" stroke-width="1" /><path d="M200,2 L200,18" stroke="#bf953f" stroke-width="2" /></svg>

                    <div id="prev-sanskrit" class="shlok-text">संस्कृत सुभाषित यहाँ दिखेगा...</div>

                    <svg class="divider-svg" viewBox="0 0 400 20"><path d="M50,10 L350,10" fill="none" stroke="#bf953f" stroke-width="1.5" stroke-dasharray="5 5" /><circle cx="200" cy="10" r="5" fill="#bf953f" /></svg>

                    <div id="prev-hindi-section" class="arth-box" style="display: none;">
                        <div class="arth-label">अर्थ :-</div>
                        <div id="prev-hindi" class="arth-text"></div>
                    </div>

                    <div id="prev-shabdarth-section" class="shabdarth-box" style="display: none;">
                        <div class="shabdarth-title">— शब्दार्थ —</div>
                        <div id="prev-shabdarth" class="shabdarth-grid"></div>
                    </div>

                    <svg class="bottom-ornament" viewBox="0 0 200 20"><path d="M0,10 Q100,0 200,10" fill="none" stroke="currentColor" stroke-width="2" /><circle cx="100" cy="10" r="4" fill="#cc0000" /></svg>
                </div>
            </div>
"@

foreach ($file in $files) {
    $c = Get-Content $file -Raw -Encoding UTF8
    
    # Replace CSS
    $c = $c -replace '(?s)\.sub-capture-container.*?\.sub-footer-band[^{]*\{[^}]*\}\s*', ($cssReplacement + "`n")
    
    # Replace HTML based on file
    if ($file -match 'view') {
        $c = $c -replace '(?s)<div id="capture-area" class="sub-capture-container">.*?<div class="sub-floral-bottom"[^>]*>.*?</div>\s*</div>\s*</div>', $htmlReplacementView
    } else {
        $c = $c -replace '(?s)<div id="capture-area" class="sub-capture-container">.*?<div class="sub-floral-bottom"[^>]*>.*?</div>\s*</div>\s*</div>', $htmlReplacementEdit
    }

    # Remove font auto-sizing JS
    $c = $c -replace '(?s)// Auto-size sanskrit text.*?sanskritEl\.style\.fontSize = fontSize \+ ''px'';', ''

    # Add Google Font
    if ($c -notmatch 'Noto\+Serif\+Devanagari') {
        $c = $c -replace '<style>', "<link href=`"https://fonts.googleapis.com/css2?family=Noto+Serif+Devanagari:wght@400;700;800;900&display=swap`" rel=`"stylesheet`">`n<style>"
    }

    # JS DOM class adjustments
    $c = $c -replace '<div class="sub-shabdarth-row">', '<div class="shabd-row">'
    $c = $c -replace '<span class="sub-shabdarth-word">', '<span class="shabd-word">'
    $c = $c -replace '<span class="sub-shabdarth-meaning">', '<span class="shabd-word" style="color:#002266; font-weight:400;">'
    $c = $c -replace '<span class="sub-shabdarth-dash">—</span>', '—'
    
    # Change html2canvas bg color
    $c = $c -replace "backgroundColor: '#FFF9E6'", "backgroundColor: '#1a1100'"

    [System.IO.File]::WriteAllText((Get-Item $file).FullName, $c, (New-Object System.Text.UTF8Encoding $false))
    Write-Host "Updated $file"
}
