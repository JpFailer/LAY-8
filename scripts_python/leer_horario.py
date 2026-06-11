import camelot
import pandas as pd
import numpy as np
import sys
import re
import PyPDF2

pdf_path = 'horario.pdf'

try:
    tablas = camelot.read_pdf(pdf_path, pages='all', flavor='lattice', process_background=True, line_scale=40)
    pdf_file = open(pdf_path, 'rb')
    lector_pdf = PyPDF2.PdfReader(pdf_file)
except Exception as e:
    print('[{"error": "Fallo al leer el PDF"}]')
    sys.exit()

# Leemos el PDF entero rápido. Apenas encuentre el período en la primera página, 
# lo guarda en la memoria global y lo usa para todas las demás páginas automáticamente.
periodo_global = "Desconocido"
for page in lector_pdf.pages:
    texto = page.extract_text()
    match = re.search(r'acad[é]mico[^\d]*([\d]{4}[-\s]*[IV12]+)', texto, re.IGNORECASE)
    if match:
        periodo_global = match.group(1).replace(" ", "-")
        break

lista_tablas_limpias = []

for tabla in tablas:
    df = tabla.df
    num_pagina = tabla.page - 1
    texto_pagina = lector_pdf.pages[num_pagina].extract_text()
    
    seccion_encontrada = "Desconocida"
    
    # Buscamos dónde dice "Sección" o "Secci" en la hoja
    match_secci = re.search(r'Secci[óo]n?[^\w]*', texto_pagina, re.IGNORECASE)
    
    if match_secci:
        # Recortamos un pedacito de texto justo después de la palabra
        inicio = match_secci.end()
        recorte = texto_pagina[inicio:inicio+50]
        
        # Buscamos TODAS las letras sueltas (palabras de 1 solo caracter)
        letras_sueltas = re.findall(r'\b[A-Z]\b', recorte)
        
        # Filtramos: Si la letra es A, B, C, D, E, F o G, es la sección.
        # Esto automáticamente ignora las 'I' o 'X' de los semestres romanos.
        for letra in letras_sueltas:
            if letra in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']:
                seccion_encontrada = letra
                break

    # 3. RECORTAMOS Y LIMPIAMOS LA CUADRÍCULA DE HORAS
    df_recortado = df.iloc[4:, 3:]
    df_limpio = df_recortado.iloc[:, ::2].copy() 
    
    if len(df_limpio.columns) == 8:
        df_limpio.columns = ['Hora', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo']
        df_limpio = df_limpio.replace(r'^\s*$', np.nan, regex=True).dropna(how='all')
        
        # INYECCIÓN DIRECTA Y PERFECTA
        df_limpio['Periodo'] = periodo_global
        df_limpio['Seccion'] = seccion_encontrada
        
        lista_tablas_limpias.append(df_limpio)

pdf_file.close()

# 4. LA FUSIÓN Y EXPORTACIÓN
if len(lista_tablas_limpias) > 0:
    df_final = pd.concat(lista_tablas_limpias, ignore_index=True)
    json_datos = df_final.to_json(orient='records', force_ascii=True)
    print(json_datos)
else:
    print('[{"error": "No se encontraron tablas válidas"}]')