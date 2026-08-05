import pandas as pd
import math

file_path = r'C:\Users\mayur\OneDrive\Desktop\New folder (2)\Master_Karyakarta_Data_2026_Hindi.xlsx'
df = pd.read_excel(file_path)

sql_lines = []
sql_lines.append("ALTER TABLE em_participants ADD COLUMN bhag VARCHAR(100) AFTER category;")
sql_lines.append("DELETE FROM em_participants;")
sql_lines.append("ALTER TABLE em_participants AUTO_INCREMENT = 1;")

insert_stmt = "INSERT INTO em_participants (event_id, phone, name, responsibility, level_type, organization, sangh_shikshan, age_group, city, vasti, email, category, bhag, entry_type) VALUES "
values = []

for index, row in df.iterrows():
    phone = str(row.get('भ्रमणध्वनी', '')).replace('.0', '').strip() if not pd.isna(row.get('भ्रमणध्वनी')) else ''
    name = str(row.get('पूर्ण नाव', '')).strip().replace("'", "\\'") if not pd.isna(row.get('पूर्ण नाव')) else ''
    resp = str(row.get('दायित्व', '')).strip().replace("'", "\\'") if not pd.isna(row.get('दायित्व')) else ''
    lvl = str(row.get('स्तर / प्रकार', '')).strip().replace("'", "\\'") if not pd.isna(row.get('स्तर / प्रकार')) else ''
    org = str(row.get('संघटना', '')).strip().replace("'", "\\'") if not pd.isna(row.get('संघटना')) else ''
    edu = str(row.get('संघ शिक्षण', '')).strip().replace("'", "\\'") if not pd.isna(row.get('संघ शिक्षण')) else ''
    age = str(row.get('वयोगट', '')).strip().replace("'", "\\'") if not pd.isna(row.get('वयोगट')) else ''
    city = str(row.get('निवासी नगर', '')).strip().replace("'", "\\'") if not pd.isna(row.get('निवासी नगर')) else ''
    vasti = str(row.get('निवासी वस्ती', '')).strip().replace("'", "\\'") if not pd.isna(row.get('निवासी वस्ती')) else ''
    email = str(row.get('अणुडाक', '')).strip().replace("'", "\\'") if not pd.isna(row.get('अणुडाक')) else ''
    cat = str(row.get('श्रेणी', '')).strip().replace("'", "\\'") if not pd.isna(row.get('श्रेणी')) else ''
    bhag = str(row.get('भाग', '')).strip().replace("'", "\\'") if not pd.isna(row.get('भाग')) else ''
    
    val = f"(1, '{phone}', '{name}', '{resp}', '{lvl}', '{org}', '{edu}', '{age}', '{city}', '{vasti}', '{email}', '{cat}', '{bhag}', 'pre-registered')"
    values.append(val)

sql_lines.append(insert_stmt + ",\n".join(values) + ";")

with open('bulk_import.sql', 'w', encoding='utf-8') as f:
    f.write("\n".join(sql_lines))

print("bulk_import.sql generated successfully with", len(values), "records.")
