import json
import os
import re
from google import genai
from google.genai import types

# load env
with open('.env', 'r') as f:
    for line in f:
        if line.startswith('GEMINI_API_KEY='):
            os.environ['GEMINI_API_KEY'] = line.strip().split('=', 1)[1]

with open('db_amritvachans.json', 'r', encoding='utf-16le') as f:
    raw_data = f.read()

match = re.search(r'\[.*\]', raw_data, re.DOTALL)
if match:
    json_str = match.group(0)
else:
    print("Could not find JSON in output")
    exit()

vachans = json.loads(json_str)

client = genai.Client()

prompt = """
Below is a JSON array containing objects with an 'id' and 'content' field. The 'content' field contains Hindi text.
Many of these texts have an issue where words are incorrectly broken up with spaces (e.g., 'ज्ञा न' instead of 'ज्ञान', 'समा ज' instead of 'समाज', 'कल्या ण' instead of 'कल्याण').
Your task is to fix these unnecessary spaces inside words.
Do NOT remove the spaces between valid separate words. ONLY fix the broken words.
Return the corrected data as a VALID JSON array in the exact same format. Do not include markdown codeblocks or any other text, just the raw JSON array.
"""

# Convert to JSON string
input_json = json.dumps(vachans, ensure_ascii=False)

try:
    response = client.models.generate_content(
        model='gemini-2.5-flash',
        contents=prompt + "\n\n" + input_json,
        config=types.GenerateContentConfig(
            response_mime_type="application/json",
        )
    )
    
    fixed_vachans = json.loads(response.text)
    print("Successfully received fixed JSON!")
    
    sql = "START TRANSACTION;\n"
    for v in fixed_vachans:
        # Simple escape for SQL
        content = v['content'].replace("'", "\\'")
        sql += f"UPDATE amrit_vachan SET content = '{content}' WHERE id = {v['id']};\n"
    sql += "COMMIT;\n"

    with open('fix_amritvachans.sql', 'w', encoding='utf-8') as f:
        f.write(sql)
    
    print("Saved SQL to fix_amritvachans.sql")

except Exception as e:
    print(f"Error: {e}")
