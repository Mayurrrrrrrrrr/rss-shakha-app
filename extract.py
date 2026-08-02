import fitz

doc = fitz.open(r'C:\Users\mayur\.gemini\antigravity\brain\cf4fbb98-136a-4217-8342-e279af5ab930\.tempmediaStorage\media_cf4fbb98-136a-4217-8342-e279af5ab930_1784884097128.pdf')
print(f"Total pages: {len(doc)}")
with open('pdf_head.txt', 'w', encoding='utf-8') as f:
    f.write(doc[0].get_text())
    if len(doc) > 6:
        f.write("\n\n--- PAGE 7 ---\n\n")
        f.write(doc[6].get_text())
