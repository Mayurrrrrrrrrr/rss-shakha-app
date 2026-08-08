import pandas as pd
import math

df = pd.read_excel(r'C:\Users\mayur\OneDrive\Desktop\New folder (2)\Master_Karyakarta_Data_2026_Hindi.xlsx')
df = df.fillna('')

sql_values = []
for idx, row in df.iterrows():
    name = str(row.get("पूर्ण नाव", "")).replace("'", "\\'")
    phone = str(row.get("भ्रमणध्वनी", "")).replace(".0", "").strip()
    city = str(row.get("निवासी नगर", "")).replace("'", "\\'")
    age = str(row.get("वयोगट", "")).replace("'", "\\'")
    
    # Simple validation
    if not name: continue
    
    sql_values.append(f"('{name}', '{phone}', '{city}', '{age}', 1)")

sql = "INSERT INTO em_participants (name, phone, city, category, event_id) VALUES " + ", ".join(sql_values) + ";"
with open('import_participants.sql', 'w', encoding='utf-8') as f:
    f.write(sql)

print(f"Generated SQL for {len(sql_values)} participants.")
