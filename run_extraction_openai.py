import fitz
import os
import json
import time
import sys
import base64
from openai import OpenAI
from pydantic import BaseModel, Field

# Ensure API key is set
api_key = os.environ.get("OPENAI_API_KEY")
if not api_key:
    print("Error: OPENAI_API_KEY environment variable not set.")
    sys.exit(1)

client = OpenAI(api_key=api_key)
pdf_path = r"C:\Users\mayur\.gemini\antigravity\brain\cf4fbb98-136a-4217-8342-e279af5ab930\.tempmediaStorage\media_cf4fbb98-136a-4217-8342-e279af5ab930_1784884097128.pdf"

class Subhashita(BaseModel):
    sanskrit_text: str
    hindi_meaning: str

class SubhashitaList(BaseModel):
    items: list[Subhashita]

def encode_image(image_path):
    with open(image_path, "rb") as image_file:
        return base64.b64encode(image_file.read()).decode('utf-8')

def process_pdf():
    doc = fitz.open(pdf_path)
    all_results = []
    
    start_page = 6
    end_page = 76
    
    # Check if we have partial results
    if os.path.exists("subhashitas.json"):
        try:
            with open("subhashitas.json", "r", encoding="utf-8") as f:
                all_results = json.load(f)
            # Estimate start page from number of extracted subhashitas (roughly 5 per page)
            start_page = 6 + (len(all_results) // 5)
            print(f"Loaded {len(all_results)} existing results. Resuming from page {start_page + 1}")
        except Exception as e:
            print(f"Error loading existing JSON: {e}")
            all_results = []

    for page_num in range(start_page, end_page + 1):
        print(f"Processing page {page_num + 1}/{end_page + 1}...", flush=True)
        
        page = doc.load_page(page_num)
        pix = page.get_pixmap(dpi=150)
        img_path = f"page_{page_num + 1}.png"
        pix.save(img_path)
        
        base64_image = encode_image(img_path)
        
        prompt = (
            "Extract the Subhashitas from this image. "
            "Output the exact Sanskrit text in Devanagari script. "
            "Translate the provided English meaning into Hindi for each Subhashita."
        )
        
        retries = 0
        success = False
        while not success:
            try:
                response = client.beta.chat.completions.parse(
                    model="gpt-4o",
                    messages=[
                        {
                            "role": "user",
                            "content": [
                                {"type": "text", "text": prompt},
                                {
                                    "type": "image_url",
                                    "image_url": {
                                        "url": f"data:image/png;base64,{base64_image}"
                                    }
                                }
                            ]
                        }
                    ],
                    response_format=SubhashitaList
                )
                
                res_json = response.choices[0].message.parsed
                for item in res_json.items:
                    all_results.append({"sanskrit_text": item.sanskrit_text, "hindi_meaning": item.hindi_meaning})
                success = True
                
            except Exception as e:
                print(f"Error on page {page_num + 1}: {e}", flush=True)
                wait_time = min(2 ** retries, 60)
                time.sleep(wait_time)
                retries += 1
                if retries > 5:
                    print(f"Max retries reached for page {page_num + 1}. Skipping.", flush=True)
                    break
        
        # Clean up image
        if os.path.exists(img_path):
            os.remove(img_path)
        
        # Save chunks
        with open("subhashitas.json", "w", encoding="utf-8") as out:
            json.dump(all_results, out, ensure_ascii=False, indent=2)
        print(f"Saved intermediate results up to page {page_num + 1}. Total: {len(all_results)}", flush=True)
        
        time.sleep(1) # Normal delay
            
    print("Extraction complete. Results saved to subhashitas.json.", flush=True)

if __name__ == "__main__":
    process_pdf()
