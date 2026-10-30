import camelot
import re
import sys
import os
import json

def normalizar_codigo_carrera(texto_carrera, fallback_carrera=None):
    if fallback_carrera and fallback_carrera in ['ING-INF', 'ING-MAR', 'ING-AMB', 'ADM', 'TUR']:
        return fallback_carrera
    nom = str(texto_carrera).lower()
    if 'inform' in nom or 'inf' in nom:
        return 'ING-INF'
    elif 'mar' in nom or 'naut' in nom:
        return 'ING-MAR'
    elif 'amb' in nom:
        return 'ING-AMB'
    elif 'admin' in nom:
        return 'ADM'
    elif 'tur' in nom:
        return 'TUR'
    return fallback_carrera or 'ING-INF'

def parse_subject_cell(text):
    text = text.replace('\n', ' ').strip()
    text = re.sub(r'\s+', ' ', text)

    aula = 'Por Asignar'
    # Buscar aula al final del bloque
    # Ejemplos: Aula 3, Lab Ing II 21, Lab F I 26, Gimnasio 69, M I 13, Aula CA, 0
    m_aula = re.search(r'(Aula\s*[\w\d]+|Lab\s+.*|Gimnasio\s*\d+|M\s*[IVX]+\s*\d+|\b0\b)$', text, re.IGNORECASE)
    if m_aula:
        aula_raw = m_aula.group(1).strip()
        if aula_raw != '0':
            aula = aula_raw
        text = text[:m_aula.start()].strip()

    # Buscar código de materia al inicio: CAL-114, SSC 503, LOG- 103, INF-102, CIE- 102, etc.
    codigo = 'GEN-000'
    m_cod = re.search(r'^([A-Z]{3})\s*-?\s*(\d{3})', text, re.IGNORECASE)
    if m_cod:
        codigo = f"{m_cod.group(1).upper()}-{m_cod.group(2)}"
        text = text[m_cod.end():].strip()

    # Normalizar guiones y caracteres iniciales
    nombre = text.strip()
    nombre = re.sub(r'^[–—−‐‑‒―\-\:\.]+', '', nombre).strip()

    return codigo, nombre, aula

def parse_header_from_table(t, fallback_carrera=None):
    header_text = ''
    for r in range(min(7, len(t.cells))):
        for c in range(min(3, len(t.cells[r]))):
            txt = t.cells[r][c].text
            if '(1) Carrera' in txt or '(3) Semestre' in txt or 'HORARIO ACAD' in txt:
                header_text = txt
                break
        if header_text:
            break

    carrera = fallback_carrera or 'Ingeniería Marítima'
    periodo = '2026-I'
    semestre = 1
    seccion = 'A'

    if header_text:
        lines = [l.strip() for l in header_text.splitlines() if l.strip()]
        idx_labels = []
        for i, l in enumerate(lines):
            if re.search(r'\(\d\)\s*(Carrera|Per[ií]odo|Semestre|Secci)', l, re.IGNORECASE):
                idx_labels.append(i)

        if idx_labels:
            last_label_idx = max(idx_labels)
            values = lines[last_label_idx + 1:]
            clean_values = []
            for v in values:
                if re.search(r'^(M\s*A\s*Ñ|T\s*A\s*R|N\s*O\s*C|Hora|Lunes|Martes|Miercoles|Jueves|Viernes|Sabado|Domingo)', v, re.IGNORECASE):
                    break
                clean_values.append(v)

            if len(clean_values) >= 1:
                carrera = clean_values[0]
            if len(clean_values) >= 2:
                m_per = re.search(r'(20\d{2}\s*-\s*[IV12]+)', clean_values[1], re.IGNORECASE)
                if m_per:
                    periodo = m_per.group(1).upper().replace(' ', '')
                elif '#REF' in clean_values[1] or '#¡REF!' in clean_values[1]:
                    periodo = '2026-I'
                else:
                    periodo = clean_values[1]
            if len(clean_values) >= 3:
                sem_str = clean_values[2].upper()
                romanos = {'I': 1, 'II': 2, 'III': 3, 'IV': 4, 'V': 5, 'VI': 6, 'VII': 7, 'VIII': 8, 'IX': 9, 'X': 10}
                semestre = romanos.get(sem_str, 1)
            if len(clean_values) >= 4:
                sec_raw = clean_values[3].strip()
                # Preservar menciones tipo A (OP) o B (IM)
                m_mencion = re.search(r'([A-Z]\s*\([A-Z]+\))', sec_raw, re.IGNORECASE)
                if m_mencion:
                    seccion = m_mencion.group(1).upper()
                else:
                    m_letra = re.search(r'\b([A-Z])\b', sec_raw.upper())
                    if m_letra:
                        seccion = m_letra.group(1)
                    else:
                        seccion = sec_raw[:10].strip()

    carrera_cod = normalizar_codigo_carrera(carrera, fallback_carrera)
    return carrera_cod, carrera, periodo, semestre, seccion

def procesar_horario(pdf_path, carrera_forzada=None):
    if not os.path.exists(pdf_path):
        return {"error": f"El archivo {pdf_path} no existe en disco."}

    try:
        tables = camelot.read_pdf(pdf_path, pages='all', flavor='lattice', process_background=True, line_scale=40)
    except Exception as e:
        return {"error": f"Error al leer PDF con Camelot: {str(e)}"}

    if len(tables) == 0:
        return {"error": "No se encontraron tablas de horario en el documento PDF."}

    dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo']
    todos_los_bloques = []

    for t_idx, t in enumerate(tables):
        h = parse_header_from_table(t, fallback_carrera=carrera_forzada)
        if not h:
            continue
        carrera_cod, carrera_nom, periodo, semestre_num, seccion = h

        # 1. Identificar columna de horas dinámicamente
        hour_col = None
        slot_times = {}
        for r in range(len(t.cells)):
            for c in range(min(5, len(t.cells[r]))):
                txt = t.cells[r][c].text.replace('\n', ' ').strip()
                m = re.search(r'(\d{2}:\d{2})\s+(\d{2}:\d{2})', txt)
                if m:
                    if hour_col is None:
                        hour_col = c
                    if c == hour_col:
                        slot_times[r] = (m.group(1) + ":00", m.group(2) + ":00")

        if hour_col is None or not slot_times:
            continue

        # 2. Mapear dinámicamente columnas de días a partir de la columna de horas
        col_map = {}
        for idx_d, d_nom in enumerate(dias):
            target_col = hour_col + 2 + (idx_d * 2)
            if target_col < len(t.cells[0]):
                col_map[target_col] = d_nom

        hour_row_indices = sorted(slot_times.keys())

        # 3. Extraer celdas con fusión vertical (vspan) exacta
        for c_idx, day_name in col_map.items():
            i = 0
            while i < len(hour_row_indices):
                r = hour_row_indices[i]
                if c_idx >= len(t.cells[r]):
                    i += 1
                    continue

                cell = t.cells[r][c_idx]
                start_time = slot_times[r][0]
                end_time = slot_times[r][1]

                block_text_parts = []
                if cell.text.strip():
                    block_text_parts.append(cell.text.strip())

                # Avanzar verticalmente mientras la celda no tenga borde inferior (bottom == False)
                j = i
                while not t.cells[hour_row_indices[j]][c_idx].bottom and (j + 1) < len(hour_row_indices):
                    j += 1
                    next_r = hour_row_indices[j]
                    next_cell = t.cells[next_r][c_idx]
                    end_time = slot_times[next_r][1]
                    if next_cell.text.strip() and next_cell.text.strip() not in block_text_parts:
                        block_text_parts.append(next_cell.text.strip())

                full_text = ' '.join(block_text_parts).strip()
                if full_text:
                    # Ignorar cabeceras de jornada si quedaron en celdas
                    if not re.search(r'^(M\s*A\s*Ñ\s*A\s*N\s*A|T\s*A\s*R\s*D\s*E|N\s*O\s*C\s*H\s*E)$', full_text, re.IGNORECASE):
                        cod_mat, nom_mat, aula = parse_subject_cell(full_text)

                        # Si nombre está vacío pero código existe, buscar nombre
                        if not nom_mat and cod_mat != 'GEN-000':
                            nom_mat = cod_mat

                        todos_los_bloques.append({
                            'carrera': carrera_cod,
                            'carrera_nombre': carrera_nom,
                            'periodo': periodo,
                            'semestre': semestre_num,
                            'seccion': seccion,
                            'dia': day_name,
                            'hora_inicio': start_time,
                            'hora_fin': end_time,
                            'codigo_materia': cod_mat,
                            'nombre_materia': nom_mat,
                            'aula': aula
                        })

                i = j + 1

    return todos_los_bloques

if __name__ == '__main__':
    pdf_path = sys.argv[1] if len(sys.argv) > 1 else 'horario.pdf'
    carrera_param = sys.argv[2] if len(sys.argv) > 2 else None

    # Normalizar ruta
    if not os.path.isabs(pdf_path):
        base_dir = os.path.dirname(os.path.abspath(__file__))
        pdf_path = os.path.join(base_dir, pdf_path)

    resultado = procesar_horario(pdf_path, carrera_param)

    if isinstance(resultado, list):
        # Guardar en archivo JSON para lectura veloz en la carpeta de scripts
        script_dir = os.path.dirname(os.path.abspath(__file__))
        carrera_tag = carrera_param if carrera_param else (resultado[0]['carrera'] if resultado else 'GENERAL')

        cache_path = os.path.join(script_dir, f'horarios_procesados_{carrera_tag}.json')
        with open(cache_path, 'w', encoding='utf-8') as f:
            json.dump(resultado, f, ensure_ascii=False, indent=2)

        # También guardar horario general procesado si es ING-INF
        if carrera_tag == 'ING-INF':
            cache_gen = os.path.join(script_dir, 'horarios_procesados.json')
            with open(cache_gen, 'w', encoding='utf-8') as f:
                json.dump(resultado, f, ensure_ascii=False, indent=2)

        print(json.dumps(resultado, ensure_ascii=False))
    else:
        print(json.dumps(resultado, ensure_ascii=False))
