import fitz
import json
import time
import os
from openai import OpenAI

api_key = os.environ.get("OPENAI_API_KEY", "")
client = OpenAI(api_key=api_key)

pdf_path = r"C:\Users\mayur\OneDrive\Desktop\23_04_56_29_amrutvani.pdf"
output_file = "amrit_vachans.json"

doc = fitz.open(pdf_path)

prompt_template = """
You are an expert transcriber and Hindi content processor. I will provide you with raw OCR text extracted from a PDF containing Amrit Vachans (quotes/sayings) in Hindi.

Your task:
Extract EACH Amrit Vachan present in the text. 
Some pages might contain multiple vachans or a long vachan spanning across. Extract distinct thoughts/sayings.

Output the result EXACTLY as a JSON array of objects, where each object has a single key "content" containing the extracted Hindi text of the Amrit Vachan.

Example output format:
[
  {
    "content": "जो समाज अपने इतिहास को भूल जाता है, वह कभी इतिहास का निर्माण नहीं कर सकता।"
  },
  {
    "content": "व्यक्ति का चरित्र ही राष्ट्र के चरित्र का निर्माण करता है।"
  }
]

Do not include markdown formatting like ```json or anything else. Only output the raw JSON array.

Here is the raw text to process:
"""

all_vachans = []
if os.path.exists(output_file):
    with open(output_file, 'r', encoding='utf-8') as f:
        all_vachans = json.load(f)
print(f"Loaded {len(all_vachans)} existing amrit vachans.")

pages_per_chunk = 5
text_chunks = []
current_chunk = ""
# Assuming amrutvani.pdf has pages. We'll read all pages.
total_pages = doc.page_count
for i in range(total_pages):
    page = doc.load_page(i)
    current_chunk += page.get_text() + "\n\n"
    if (i + 1) % pages_per_chunk == 0 or i == total_pages - 1:
        text_chunks.append((i, current_chunk))
        current_chunk = ""

# Since we don't have "existing_ids" (no ID in prompt), we will just process all if the file was empty.
# If the file wasn't empty, we should ideally skip processed chunks, but we'll assume we process from scratch or overwrite if we need to.
# Let's just process from scratch since we don't know where it stopped, or append. Let's just overwrite.
all_vachans = []

for end_page, chunk in text_chunks:
    print(f"Processing chunk ending at page {end_page+1}/{total_pages}...", flush=True)
    if not chunk.strip():
        continue
    try:
        response = client.chat.completions.create(
            model="gpt-4o-mini",
            messages=[
                {"role": "system", "content": "You are a helpful assistant that extracts quotes into JSON arrays."},
                {"role": "user", "content": prompt_template + chunk}
            ],
            temperature=0.2
        )
        
        response_text = response.choices[0].message.content.strip()
        if response_text.startswith("```json"):
            response_text = response_text[7:]
        if response_text.startswith("```"):
            response_text = response_text[3:]
        if response_text.endswith("```"):
            response_text = response_text[:-3]
            
        chunk_data = json.loads(response_text.strip())
        all_vachans.extend(chunk_data)
        
        print(f"Extracted {len(chunk_data)} new amrit vachans. Total so far: {len(all_vachans)}.", flush=True)
        with open(output_file, "w", encoding="utf-8") as f:
            json.dump(all_vachans, f, ensure_ascii=False, indent=2)
            
    except Exception as e:
        print(f"Error processing chunk ending at page {end_page+1}: {e}", flush=True)
        time.sleep(2)

print(f"\nDone! Extracted total {len(all_vachans)} amrit vachans.", flush=True)
