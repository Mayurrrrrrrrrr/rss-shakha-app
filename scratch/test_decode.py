import os

def fix_mojibake(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Try to encode as cp1252 and decode as utf-8
    try:
        raw_bytes = content.encode('cp1252')
        decoded = raw_bytes.decode('utf-8')
        print(f"Successfully decoded {file_path} using cp1252!")
        return decoded
    except Exception as e:
        print(f"Failed to decode {file_path} using cp1252: {e}")
        # Try byte by byte fallback / mixed mode if needed
        return None

# Test on monthly_report.php
file_to_test = r"c:\Users\mayur\.gemini\antigravity\scratch\rss-shakha-app\pages\monthly_report.php"
decoded = fix_mojibake(file_to_test)
if decoded:
    print("Preview of first 300 characters:")
    print(decoded[:300])
