import fitz
import json
import os
import time
import re
from google import genai
from google.genai import types

# load env
with open('.env', 'r') as f:
    for line in f:
        if line.startswith('GEMINI_API_KEY='):
            os.environ['GEMINI_API_KEY'] = line.strip().split('=', 1)[1]

client = genai.Client()

pdf_path = r"C:\Users\mayur\OneDrive\Desktop\23_04_56_29_amrutvani.pdf"
try:
    doc = fitz.open(pdf_path)
except Exception as e:
    print(f"Error opening PDF: {e}")
    exit(1)

all_vachans = []

# process in chunks of 5 pages
chunk_size = 5
for i in range(0, len(doc), chunk_size):
    print(f"Processing pages {i+1} to {min(i+chunk_size, len(doc))} of {len(doc)}...")
    text = ""
    for j in range(i, min(i+chunk_size, len(doc))):
        text += doc[j].get_text()
    
    prompt = """
    Extract all 'Amrit Vachans' (quotes/sayings) from the provided Hindi text. 
    Return ONLY a JSON list of strings, where each string is a distinct Amrit Vachan in Hindi. 
    Ensure the text is purely the quote without numbers, bullets, or extra metadata.
    Do NOT use any markdown formatting, only the raw JSON array starting with '[' and ending with ']'.
    If no quotes are found, return [].
    """
    
    max_retries = 3
    for attempt in range(max_retries):
        try:
            response = client.models.generate_content(
                model='gemini-2.5-flash',
                contents=[prompt, text],
                config=types.GenerateContentConfig(
                    response_mime_type="application/json",
                    temperature=0.1
                )
            )
            
            resp_text = response.text
            # sometimes model includes markdown, try to strip it
            match = re.search(r'\[.*\]', resp_text, re.DOTALL)
            if match:
                resp_text = match.group(0)
                
            vachans = json.loads(resp_text)
            if isinstance(vachans, list):
                all_vachans.extend(vachans)
                print(f"  Extracted {len(vachans)} quotes in this chunk.")
                break
            else:
                print("  Response was not a list.")
                break
                
        except json.JSONDecodeError as e:
            print(f"  JSON parse error on attempt {attempt+1}: {e}. Response was: {resp_text[:100]}...")
            if attempt == max_retries - 1:
                print("  Failed after max retries.")
            time.sleep(2)
        except Exception as e:
            error_str = str(e)
            print(f"  API error on attempt {attempt+1}: {error_str[:200]}")
            if "RESOURCE_EXHAUSTED" in error_str:
                print("  Rate limit reached. Sleeping for 60 seconds...")
                time.sleep(60)
            else:
                time.sleep(5)
            if attempt == max_retries - 1:
                print("  Failed after max retries.")

with open('amrit_vachans.json', 'w', encoding='utf-8') as f:
    json.dump(all_vachans, f, ensure_ascii=False, indent=2)

print(f"Extraction complete. Total extracted: {len(all_vachans)}")
