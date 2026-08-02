import fitz
import os
import json
import time
import sys
from google import genai
from google.genai import types
from google.genai.errors import APIError
from pydantic import BaseModel, Field

# Ensure API key is set
api_key = os.environ.get("GEMINI_API_KEY")
if not api_key:
    print("Error: GEMINI_API_KEY environment variable not set.")
    sys.exit(1)

client = genai.Client(api_key=api_key)
pdf_path = r"C:\Users\mayur\.gemini\antigravity\brain\cf4fbb98-136a-4217-8342-e279af5ab930\.tempmediaStorage\media_cf4fbb98-136a-4217-8342-e279af5ab930_1784884097128.pdf"

class Subhashita(BaseModel):
    sanskrit_text: str = Field(description="The Sanskrit text in Devanagari script.")
    hindi_meaning: str = Field(description="The translated Hindi meaning.")

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
        
        with open(img_path, "rb") as f:
            img_data = f.read()
        
        prompt = (
            "Extract the Subhashitas from this image. "
            "Output the exact Sanskrit text in Devanagari script. "
            "Translate the provided English meaning into Hindi for each Subhashita."
        )
        
        retries = 0
        success = False
        while not success:
            try:
                response = client.models.generate_content(
                    model='gemini-2.5-flash',
                    contents=[
                        types.Part.from_bytes(data=img_data, mime_type='image/png'),
                        prompt,
                    ],
                    config=types.GenerateContentConfig(
                        response_mime_type="application/json",
                        response_schema=list[Subhashita],
                    ),
                )
                
                res_json = json.loads(response.text)
                all_results.extend(res_json)
                success = True
                
            except APIError as e:
                print(f"API Error: {e}", flush=True)
                if hasattr(e, 'code') and e.code == 429:
                    wait_time = min(2 ** retries, 60)
                    print(f"Rate limit hit. Retrying in {wait_time} seconds...", flush=True)
                    time.sleep(wait_time)
                    retries += 1
                else:
                    wait_time = min(2 ** retries, 60)
                    time.sleep(wait_time)
                    retries += 1
            except Exception as e:
                print(f"Error on page {page_num + 1}: {e}", flush=True)
                wait_time = min(2 ** retries, 60)
                time.sleep(wait_time)
                retries += 1
                
            if retries > 10:
                print(f"Max retries reached for page {page_num + 1}. Skipping.", flush=True)
                break
        
        # Clean up image
        if os.path.exists(img_path):
            os.remove(img_path)
        
        # Save chunks
        with open("subhashitas.json", "w", encoding="utf-8") as out:
            json.dump(all_results, out, ensure_ascii=False, indent=2)
        print(f"Saved intermediate results up to page {page_num + 1}. Total: {len(all_results)}", flush=True)
        
        time.sleep(3) # Normal delay
            
    print("Extraction complete. Results saved to subhashitas.json.", flush=True)

if __name__ == "__main__":
    process_pdf()
