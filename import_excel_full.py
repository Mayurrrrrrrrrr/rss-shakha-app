import pandas as pd

df = pd.read_excel(r'C:\Users\mayur\OneDrive\Desktop\New folder (2)\Master_Karyakarta_Data_2026_Hindi.xlsx')
df = df.fillna('')

sql_values = []
for idx, row in df.iterrows():
    name = str(row.get("पूर्ण नाव", "")).replace("'", "\\'")
    phone = str(row.get("भ्रमणध्वनी", "")).replace(".0", "").strip()
    city = str(row.get("निवासी नगर", "")).replace("'", "\\'")
    vasti = str(row.get("निवासी वस्ती", "")).replace("'", "\\'")
    email = str(row.get("अणुडाक", "")).replace("'", "\\'")
    responsibility = str(row.get("दायित्व", "")).replace("'", "\\'")
    level_type = str(row.get("स्तर / प्रकार", "")).replace("'", "\\'")
    organization = str(row.get("संघटना", "")).replace("'", "\\'")
    sangh_shikshan = str(row.get("संघ शिक्षण", "")).replace("'", "\\'")
    age_group = str(row.get("वयोगट", "")).replace("'", "\\'")
    category = str(row.get("श्रेणी", "")).replace("'", "\\'")
    bhag = str(row.get("भाग", "")).replace("'", "\\'")
    
    if not name: continue
    
    sql_values.append(f"('{name}', '{phone}', '{city}', '{vasti}', '{email}', '{responsibility}', '{level_type}', '{organization}', '{sangh_shikshan}', '{age_group}', '{category}', '{bhag}', 1)")

sql = "DELETE FROM em_participants WHERE event_id = 1;\n"
sql += "INSERT INTO em_participants (name, phone, city, vasti, email, responsibility, level_type, organization, sangh_shikshan, age_group, category, bhag, event_id) VALUES " + ", ".join(sql_values) + ";"

with open('import_participants_full.sql', 'w', encoding='utf-8') as f:
    f.write(sql)
