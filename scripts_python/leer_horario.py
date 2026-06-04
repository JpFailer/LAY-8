import camelot
import pandas as pd
import numpy as np
import sys # Esta librería nos ayuda a silenciar errores internos que puedan confundir a PHP

# 1. EL ESCANER (Ahora lee TODAS las páginas)
try:
    tablas = camelot.read_pdf('horario.pdf', pages='all', flavor='lattice', process_background=True, line_scale=40)
except Exception as e:
    # Si algo explota (ej. no encuentra el PDF), escupimos un JSON de error para PHP
    print('{"error": "Fallo al leer el PDF"}')
    sys.exit()

# Creamos una lista vacía para ir guardando las tablas ya limpias
lista_tablas_limpias = []

# 2. LA LÍNEA DE ENSAMBLAJE (Bucle)
for tabla in tablas:
    df = tabla.df
    
    # Aplicamos nuestra receta a cada página
    df_recortado = df.iloc[4:, 3:]
    df_limpio = df_recortado.iloc[:, ::2]
    
    # Verificamos que sí quedaron 8 columnas antes de bautizarlas (por seguridad)
    if len(df_limpio.columns) == 8:
        df_limpio.columns = ['Hora', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo']
        df_limpio = df_limpio.replace(r'^\s*$', np.nan, regex=True).dropna(how='all')
        
        # Agregamos la tabla limpia a nuestra caja
        lista_tablas_limpias.append(df_limpio)

# 3. LA FUSIÓN
# Pegamos todas las hojas una debajo de la otra
if len(lista_tablas_limpias) > 0:
    df_final = pd.concat(lista_tablas_limpias, ignore_index=True)
    
    # 4. LA EXPORTACIÓN SILENCIOSA A JSON
    # Orient='records' hace que el JSON sea un arreglo de diccionarios (perfecto para PHP)
    json_datos = df_final.to_json(orient='records', force_ascii=True)
    
    # IMPRESIÓN FINAL (Esto es lo único que atrapará PHP)
    print(json_datos)
else:
    print('{"error": "No se encontraron tablas válidas"}')