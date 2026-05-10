$filePath = "c:\Users\mayur\.gemini\antigravity\scratch\rss-shakha-app\includes\header.php"
$content = Get-Content -Path $filePath -Raw -Encoding UTF8

# Add panchang link after vyaktitv.php (admin section only, not _view)
$searchStr = "vyaktitv.php"
$newLine = '                                                <li><a href="../pages/panchang_daily.php" class="<?php echo $currentPage === ' + "'" + 'panchang_daily' + "'" + ' ? ' + "'" + 'active' + "'" + ' : ' + "'" + "'" + '; ?>">🕉️ दैनिक पंचांग</a></li>'

# Find first occurrence (admin section)
$lines = Get-Content -Path $filePath -Encoding UTF8
$newContent = @()
$added = $false
foreach ($line in $lines) {
    $newContent += $line
    if (-not $added -and $line -match 'vyaktitv\.php' -and $line -notmatch 'vyaktitv_view') {
        $newContent += $newLine
        $added = $true
    }
}

Set-Content -Path $filePath -Value $newContent -Encoding UTF8
Write-Host "Done! Added: $added"
