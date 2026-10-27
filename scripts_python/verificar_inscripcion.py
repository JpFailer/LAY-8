import sys
import json
import os
import re

if hasattr(sys.stdout, 'reconfigure'):
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass

def normalizar(texto):
    if not texto:
        return ""
    texto = texto.lower()
    reemplazos = {
        'á': 'a', 'é': 'e', 'í': 'i', 'ó': 'o', 'ú': 'u',
        'à': 'a', 'è': 'e', 'ì': 'i', 'ò': 'o', 'ù': 'u',
        'ä': 'a', 'ë': 'e', 'ï': 'i', 'ö': 'o', 'ü': 'u',
        'ñ': 'n', '\ufffd': '', '?': ''
    }
    for k, v in reemplazos.items():
        texto = texto.replace(k, v)
    return texto

def limpiar_nombre_materia(nombre_raw):
    if not nombre_raw:
        return ""
    reglas = [
        (r'M[\ufffd\?]?ndez', 'Méndez'),
        (r'C[\ufffd\?]?lculo', 'Cálculo'),
        (r'Num[\ufffd\?]?rico', 'Numérico'),
        (r'F[\ufffd\?]?sica', 'Física'),
        (r'Programaci[\ufffd\?]?n', 'Programación'),
        (r'Inform[\ufffd\?]?tica', 'Informática'),
        (r'Ingenier[\ufffd\?]?a', 'Ingeniería'),
        (r'Administraci[\ufffd\?]?n', 'Administración'),
        (r'Segur\s+idad', 'Seguridad'),
        (r'Estad[\ufffd\?]?stica', 'Estadística'),
        (r'Electr[\ufffd\?]?nica', 'Electrónica'),
        (r'M[\ufffd\?]?todos', 'Métodos'),
        (r'Q[\ufffd\?]?mica', 'Química')
    ]
    nombre = nombre_raw
    for pat, rep in reglas:
        nombre = re.sub(pat, rep, nombre, flags=re.IGNORECASE)
    nombre = re.sub(r'[\ufffd\?]+', '', nombre)
    return re.sub(r'\s+', ' ', nombre).strip()

def limpiar_nombre_persona(nombre_raw):
    if not nombre_raw:
        return ""
    reglas = [
        (r'M[\ufffd\?]?ndez', 'Méndez'),
        (r'Gonz[\ufffd\?]?lez', 'González'),
        (r'P[\ufffd\?]?rez', 'Pérez'),
        (r'S[\ufffd\?]?nchez', 'Sánchez'),
        (r'Rodr[\ufffd\?]?guez', 'Rodríguez'),
        (r'Mart[\ufffd\?]?nez', 'Martínez'),
        (r'Hern[\ufffd\?]?ndez', 'Hernández'),
        (r'Garc[\ufffd\?]?a', 'García'),
        (r'L[\ufffd\?]?pez', 'López'),
        (r'D[\ufffd\?]?az', 'Díaz')
    ]
    nombre = nombre_raw
    for pat, rep in reglas:
        nombre = re.sub(pat, rep, nombre, flags=re.IGNORECASE)
    nombre = re.sub(r'[\ufffd\?]+', '', nombre)
    return re.sub(r'\s+', ' ', nombre).strip()

def procesar_pdf(pdf_path):
    resultado = {
        "es_valido": False,
        "motivo_invalidez": None,
        "puntuacion_confianza": 0,
        "estudiante": None,
        "cedula": None,
        "codigo_estudiante": None,
        "carrera_codigo": None,
        "carrera_nombre": None,
        "ano": None,
        "periodo": None,
        "total_creditos": 0.0,
        "codigo_control": None,
        "materias": [],
        "lineas_extraidas": 0
    }

    if not os.path.exists(pdf_path):
        resultado["motivo_invalidez"] = f"El archivo no existe en la ruta: {pdf_path}"
        return resultado

    # 1. Extraer texto usando pypdf o PyPDF2
    texto_completo = ""
    try:
        import pypdf
        reader = pypdf.PdfReader(pdf_path)
        for page in reader.pages:
            t = page.extract_text()
            if t:
                texto_completo += t + "\n"
    except Exception as e_pypdf:
        try:
            import PyPDF2
            with open(pdf_path, 'rb') as f:
                reader = PyPDF2.PdfReader(f)
                for page in reader.pages:
                    t = page.extract_text()
                    if t:
                        texto_completo += t + "\n"
        except Exception as e_pypdf2:
            resultado["motivo_invalidez"] = f"Error al procesar el archivo PDF: {str(e_pypdf)}"
            return resultado

    if not texto_completo.strip():
        resultado["motivo_invalidez"] = "El archivo no contiene capas de texto legibles o es una imagen escaneada no vectorizada."
        return resultado

    lineas = [l.strip() for l in texto_completo.splitlines() if l.strip()]
    resultado["lineas_extraidas"] = len(lineas)
    texto_norm = normalizar(texto_completo)

    # 2. Criterios de Seguridad Institucional UMC
    puntos = 0
    criterios = {
        "maritima": ("maritima del caribe" in texto_norm or "experimental maritima" in texto_norm),
        "secretaria": ("secretaria general" in texto_norm),
        "registro": ("registro estudiantil" in texto_norm or "regisstudent" in texto_norm or "reg-seg-cre" in texto_norm or "registro de inscripcion" in texto_norm),
        "formulario": ("upe-002" in texto_norm or "forma 006 ree" in texto_norm or "upe 002" in texto_norm),
        "titulo": ("registro de inscripcion" in texto_norm or "tramites de inscripcion" in texto_norm or "inscripciones" in texto_norm),
        "republica": ("republica bolivariana" in texto_norm or "venezuela" in texto_norm)
    }

    for k, ok in criterios.items():
        if ok:
            puntos += 15

    if puntos < 45:
        resultado["motivo_invalidez"] = "El documento suministrado no posee las marcas de agua, membretes ni encabezados institucionales de la Universidad Marítima del Caribe (UMC)."
        return resultado

    # 3. Extracción de Datos del Estudiante
    texto_espacios = re.sub(r'[ \t]+', ' ', texto_completo)

    # Estudiante / Nombre Completo
    m_est = re.search(r'Nombre\s*:\s*([^\n\r]+?)(?:\s+Programa\s+de|\s+C[eé\?]+dula|\r|\n|$)', texto_espacios, re.IGNORECASE)
    if not m_est:
        m_est = re.search(r'Bachiller:\s*(.*?)\s+Estudiante\s+Nro\.?:', texto_espacios, re.DOTALL | re.IGNORECASE)
    if not m_est:
        m_est = re.search(r'Bachiller:\s*(.*?)\s+(?:portador|\n)', texto_espacios, re.IGNORECASE)
    if m_est:
        resultado["estudiante"] = limpiar_nombre_persona(m_est.group(1))

    # Código de Estudiante
    m_cod = re.search(r'C[oó\?]+digo(?:\s+del)?\s+Estudiante\s*[:\.]?\s*([A-Za-z0-9\-]+)', texto_espacios, re.IGNORECASE)
    if not m_cod:
        m_cod = re.search(r'Estudiante\s+Nro\.?:\s*([A-Za-z0-9\-]+)', texto_espacios, re.IGNORECASE)
    if m_cod:
        resultado["codigo_estudiante"] = m_cod.group(1).strip()

    # Cédula de Identidad
    m_ced = re.search(r'C[eé\?]+dula(?:\s+(?:de\s+)?Identidad)?\s*[:\.]?\s*([VvEe]\s*-\s*\d+)', texto_espacios, re.IGNORECASE)
    if not m_ced:
        m_ced = re.search(r'\b([VvEe]\s*-\s*\d{6,9})\b', texto_espacios)
    if m_ced:
        resultado["cedula"] = re.sub(r'\s+', '', m_ced.group(1)).upper()

    # Período y Año
    m_ano = re.search(r'A[ñn\?]+o\s*[:\.]?\s*([0-9]{4})', texto_espacios, re.IGNORECASE)
    if not m_ano:
        m_ano = re.search(r'del\s+a[ñn\?]+o\s*([0-9]{4})', texto_espacios, re.IGNORECASE)
    if m_ano:
        resultado["ano"] = m_ano.group(1)

    m_per = re.search(r'Per[íi\?]+odo(?:\s+acad[ée\?]+mico)?\s*[:\.]?\s*([0-9]{4}-[0-9]{2})', texto_espacios, re.IGNORECASE)
    if m_per:
        resultado["periodo"] = m_per.group(1)
        if not resultado["ano"]:
            resultado["ano"] = resultado["periodo"].split("-")[0]
    elif resultado["ano"]:
        resultado["periodo"] = f"{resultado['ano']}-01"

    # Total Créditos
    m_tot = re.search(r'Total\s+Cr[eé\?]+ditos\s*[:\.]?\s*(\d+(?:\.\d+)?)', texto_espacios, re.IGNORECASE)
    if m_tot:
        resultado["total_creditos"] = float(m_tot.group(1))

    # Carrera Universitaria
    mapa_carreras = {
        'ingenieria en informatica': ('ING-INF', 'Ingeniería en Informática'),
        'informatica': ('ING-INF', 'Ingeniería en Informática'),
        'ingenieria maritima': ('ING-MAR', 'Ingeniería Marítima'),
        'maritima': ('ING-MAR', 'Ingeniería Marítima'),
        'ingenieria ambiental': ('ING-AMB', 'Ingeniería Ambiental'),
        'ambiental': ('ING-AMB', 'Ingeniería Ambiental'),
        'licenciatura en administracion': ('ADM', 'Licenciatura en Administración'),
        'administracion': ('ADM', 'Licenciatura en Administración'),
        'licenciatura en turismo': ('TUR', 'Licenciatura en Turismo'),
        'turismo': ('TUR', 'Licenciatura en Turismo')
    }

    m_car = re.search(r'Programa\s+de\s+Formaci[óo\?]+n\s*[:\.]?\s*([^\n,]+)', texto_espacios, re.IGNORECASE)
    if m_car:
        car_texto = normalizar(m_car.group(1))
        for frase, (cod, nom) in mapa_carreras.items():
            if frase in car_texto:
                resultado["carrera_codigo"] = cod
                resultado["carrera_nombre"] = nom
                break

    if not resultado["carrera_codigo"]:
        for frase, (cod, nom) in mapa_carreras.items():
            if frase in texto_norm:
                resultado["carrera_codigo"] = cod
                resultado["carrera_nombre"] = nom
                break

    # 4. Extracción de Materias
    materias_encontradas = []

    # Formato A: Oficial UMC UPE-002 fila completa
    # Ej: 06 CAL604 - Cálculo Numérico SVC-A 4.0
    patron_upe002 = re.compile(
        r'^\s*(?:(\d{1,2})\s+)?([A-Za-z0-9]{4,8})\s*[-–]\s*(.+?)\s+([A-Za-z0-9\-]+)\s+(\d+(?:\.\d+)?)\s*$',
        re.MULTILINE
    )
    for m in patron_upe002.finditer(texto_completo):
        semestre = int(m.group(1)) if m.group(1) else 1
        codigo = m.group(2).strip()
        nombre_limpio = limpiar_nombre_materia(m.group(3).strip())
        seccion = m.group(4).strip()
        creditos = float(m.group(5))

        materias_encontradas.append({
            "semestre": semestre,
            "codigo": codigo,
            "nombre": nombre_limpio,
            "seccion": seccion,
            "creditos": creditos
        })

    # Formato B: Dos líneas (Línea 1: Semestre? Codigo - Nombre / Línea 2: Sección Créditos)
    if not materias_encontradas:
        for idx, line in enumerate(lineas):
            m_ind = re.match(r'^\s*(?:(\d{1,2})\s+)?([A-Za-z0-9]{4,8})\s*[-–]\s*(.+?)\s*$', line)
            if m_ind and idx + 1 < len(lineas):
                m_next = re.match(r'^([A-Za-z0-9\-]+)\s+(\d+(?:\.\d+)?)$', lineas[idx + 1])
                if m_next:
                    semestre = int(m_ind.group(1)) if m_ind.group(1) else 1
                    codigo = m_ind.group(2).strip()
                    nombre_limpio = limpiar_nombre_materia(m_ind.group(3).strip())
                    seccion = m_next.group(1).strip()
                    creditos = float(m_next.group(2))
                    materias_encontradas.append({
                        "semestre": semestre,
                        "codigo": codigo,
                        "nombre": nombre_limpio,
                        "seccion": seccion,
                        "creditos": creditos
                    })

    # Formato C: Histórico con estados (EN CURSO, APROBADA, etc.)
    if not materias_encontradas:
        patron_hist = re.compile(
            r'(\d{4}\s*-\s*\d{4}-\d{2})\s+(\d{1,2})\s+([A-Z0-9\-]+)\s+([A-Z0-9]{4,8})\s*[-–]\s*(.+?)\s+(?:E\s*N\s+CURSO|APROBADA|INSCRITA|REPROBADA|CURSANDO)',
            re.IGNORECASE
        )
        for m in patron_hist.finditer(texto_espacios):
            semestre = int(m.group(2))
            seccion = m.group(3).strip()
            codigo = m.group(4).strip()
            nom_limpio = limpiar_nombre_materia(m.group(5).strip())
            materias_encontradas.append({
                "semestre": semestre,
                "codigo": codigo,
                "nombre": nom_limpio,
                "seccion": seccion,
                "creditos": 3.0
            })

    resultado["materias"] = materias_encontradas

    if resultado["total_creditos"] == 0.0 and materias_encontradas:
        resultado["total_creditos"] = round(sum(m["creditos"] for m in materias_encontradas), 1)

    if resultado["carrera_codigo"]:
        puntos += 15
    if resultado["materias"]:
        puntos += 25
    if resultado["estudiante"]:
        puntos += 15

    resultado["puntuacion_confianza"] = min(100, puntos)

    if not resultado["carrera_codigo"]:
        resultado["motivo_invalidez"] = "No se pudo identificar una carrera oficial de la UMC en el comprobante adjunto."
        return resultado

    if not resultado["materias"]:
        resultado["motivo_invalidez"] = "El comprobante no registra ninguna materia ni sección inscrita para el período académico."
        return resultado

    resultado["es_valido"] = True
    return resultado

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"es_valido": False, "motivo_invalidez": "Ruta de archivo no proporcionada."}, ensure_ascii=False))
        sys.exit(1)

    ruta = sys.argv[1]
    res = procesar_pdf(ruta)
    print(json.dumps(res, ensure_ascii=False, indent=2))
