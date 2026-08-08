import pandas as pd
df = pd.read_excel(r'C:\Users\mayur\OneDrive\Desktop\New folder (2)\Master_Karyakarta_Data_2026_Hindi.xlsx')
cols = df.columns.tolist()
with open('cols.txt', 'w', encoding='utf-8') as f:
    f.write(','.join(cols))
