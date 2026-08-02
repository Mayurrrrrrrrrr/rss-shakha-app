import fitz
import json
import time
import os
import sys
from google import genai
from google.genai import types

api_key = os.environ.get("GEMINI_API_KEY", "AIzaSyBnkk28ukbVfPXHBWNTm8Ot05u3acElY4I")
client = genai.Client(api_key=api_key)

pdf_path = r"C:\Users\mayur\OneDrive\Desktop\Subhashita.pdf"
output_file = "extracted_subhashitas.json"

doc = fitz.open(pdf_path)

prompt_template = """
You are an expert Sanskrit to Hindi translator. I will provide you with raw OCR text extracted from a PDF containing Subhashitas. 
The text contains Sanskrit shlokas and their English translations/explanations.

Your task:
Extract EACH Subhashita present in the text. For EACH one, provide:
1. "id": The number of the subhashita (as an integer). It is usually indicated in the text (e.g., "१ ." or "1.").
2. "sanskrit": The original Sanskrit shloka exactly as it appears in Devanagari.
3. "hindi": Translate the meaning of the shloka into pure, professional Hindi. Do NOT just translate the English explanation, but provide a highly accurate Hindi meaning of the Sanskrit verses.
4. "shabdarth": Extract 3 to 6 main Sanskrit words from the shloka and provide their Hindi meanings as a list of objects with "word" and "meaning".

Output the result EXACTLY as a JSON array of objects. Do not include markdown formatting like ```json or anything else. Only output the raw JSON array.

Example output format:
[
  {
    "id": 1,
    "sanskrit": "अग्निः शेषं ऋणः शेषं शत्रुः शेषं तथैव च । पुनः पुनः प्रवर्धेत तस्मात् शेषं न कारयेत् ॥",
    "hindi": "आग, कर्ज़, और शत्रु — यदि ये थोड़ा सा भी शेष (बाकी) रह जाएं, तो वे बार-बार बढ़ते रहते हैं। इसलिए इन्हें पूरी तरह से समाप्त कर देना चाहिए।",
    "shabdarth": [
      {"word": "अग्निः", "meaning": "आग"},
      {"word": "ऋणः", "meaning": "कर्ज़"},
      {"word": "शत्रुः", "meaning": "दुश्मन"},
      {"word": "प्रवर्धेत", "meaning": "बढ़ता है"}
    ]
  }
]

Here is the raw text to process:
"""

all_subhashitas = []
if os.path.exists(output_file):
    with open(output_file, 'r', encoding='utf-8') as f:
        all_subhashitas = json.load(f)
print(f"Loaded {len(all_subhashitas)} existing subhashitas.")
existing_ids = set(s['id'] for s in all_subhashitas if 'id' in s)

pages_per_chunk = 5
text_chunks = []
current_chunk = ""
for i in range(6, 77):
    page = doc.load_page(i)
    current_chunk += page.get_text() + "\n\n"
    if (i - 5) % pages_per_chunk == 0 or i == 76:
        text_chunks.append((i, current_chunk))
        current_chunk = ""

for end_page, chunk in text_chunks:
    print(f"Processing chunk ending at page {end_page+1}...", flush=True)
    try:
        response = client.models.generate_content(
            model='gemini-2.5-flash',
            contents=prompt_template + chunk,
            config=types.GenerateContentConfig(temperature=0.2)
        )
        
        response_text = response.text.strip()
        if response_text.startswith("```json"):
            response_text = response_text[7:]
        if response_text.startswith("```"):
            response_text = response_text[3:]
        if response_text.endswith("```"):
            response_text = response_text[:-3]
            
        chunk_data = json.loads(response_text.strip())
        
        added = 0
        for item in chunk_data:
            if 'id' in item and item['id'] not in existing_ids:
                all_subhashitas.append(item)
                existing_ids.add(item['id'])
                added += 1
                
        print(f"Extracted {added} new subhashitas. Total so far: {len(all_subhashitas)}.", flush=True)
        with open(output_file, "w", encoding="utf-8") as f:
            json.dump(all_subhashitas, f, ensure_ascii=False, indent=2)
            
    except Exception as e:
        print(f"Error processing chunk ending at page {end_page+1}: {e}", flush=True)
        time.sleep(2)
        
    time.sleep(1)

print(f"\nDone! Extracted total {len(all_subhashitas)} subhashitas.", flush=True)
