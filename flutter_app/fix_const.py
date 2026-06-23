import os
import re

directory = r"c:\Users\mayur\.gemini\antigravity\scratch\rss-shakha-app\flutter_app\lib\features"

for root, dirs, files in os.walk(directory):
    for file in files:
        if file.endswith('.dart'):
            filepath = os.path.join(root, file)
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # Find and replace const Text(..., Theme.of(context)...
            # Actually, just search for 'const ' before anything that contains Theme.of(context) on the same line.
            lines = content.split('\n')
            changed = False
            for i, line in enumerate(lines):
                if 'Theme.of(context)' in line and 'const ' in line:
                    # Strip 'const '
                    # Specifically: const Text(, const Icon(, const TextStyle(, const Padding(
                    lines[i] = re.sub(r'\bconst\s+(Text|Icon|TextStyle|Padding|Row|Column|Container|Center|Align|SizedBox|Expanded)\b', r'\1', line)
                    changed = True
                
                # Fix the botched replace in monthly_report_screen: TextStyle(fontSize: TextStyle(fontSize: 20
                if 'TextStyle(fontSize: TextStyle(fontSize: 20' in line:
                    lines[i] = line.replace('TextStyle(fontSize: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.onSurface), fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.onSurface)', 'TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.onSurface)')
                    changed = True

            if changed:
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write('\n'.join(lines))
