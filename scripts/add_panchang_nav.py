import sys

filepath = r"c:\Users\mayur\.gemini\antigravity\scratch\rss-shakha-app\includes\header.php"

with open(filepath, 'r', encoding='utf-8') as f:
    lines = f.readlines()

new_lines = []
added = False
for line in lines:
    new_lines.append(line)
    if not added and 'vyaktitv.php' in line and 'vyaktitv_view' not in line:
        indent = line[:len(line) - len(line.lstrip())]
        panchang_line = indent + '<li><a href="../pages/panchang_daily.php" class="<?php echo $currentPage === \'panchang_daily\' ? \'active\' : \'\'; ?>">🕉️ दैनिक पंचांग</a></li>\n'
        new_lines.append(panchang_line)
        added = True

with open(filepath, 'w', encoding='utf-8') as f:
    f.writelines(new_lines)

print(f"Done! Added: {added}")
