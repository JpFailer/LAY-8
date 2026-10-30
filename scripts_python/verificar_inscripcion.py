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
    # Eliminar estatus de materia si quedó pegado al final
    nombre = re.sub(r'\s+E\s*N\s+CURSO\b.*$', '', nombre_raw, flags=re.IGNORECASE)
    nombre = re.sub(r'\s+(?:APROBADA|INSCRITA|CURSANDO)\b.*$', '', nombre, flags=re.IGNORECASE)

    reglas = [
        (r'M[\ufffd\?é]?\s*ndez', 'Méndez'),
        (r'C[\ufffd\?á]?\s*lculo', 'Cálculo'),
        (r'Num[\ufffd\?é]?\s*rico', 'Numérico'),
        (r'F[\ufffd\?íi]?\s*sica', 'Física'),
        (r'Programaci[\ufffd\?óo]?\s*n', 'Programación'),
        (r'Inform[\ufffd\?áa]?\s*tica', 'Informática'),
        (r'Ingenier[\ufffd\?íi]?\s*a', 'Ingeniería'),
        (r'Administraci[\ufffd\?óo]?\s*n', 'Administración'),
        (r'Segur\s+idad', 'Seguridad'),
        (r'Estad[\ufffd\?íi]?\s*stica', 'Estadística'),
        (r'Electr[\ufffd\?óo]?\s*nica', 'Electrónica'),
        (r'M[\ufffd\?é]?\s*todos', 'Métodos'),
        (r'Q[\ufffd\?ú]?\s*mica', 'Química'),
        (r'Decisi[\ufffd\?óo]?\s*n', 'Decisión'),
        (r'Tecnolog[\ufffd\?íi]?\s*a', 'Tecnología'),
        (r'Reparaci[\ufffd\?óo]?\s*n', 'Reparación'),
        (r'Teor[\ufffd\?íi]?\s*as', 'Teorías'),
        (r'T[\ufffd\?é]?\s*cnicas', 'Técnicas'),
        (r'Ingl[\ufffd\?é]?\s*s', 'Inglés'),
    ]
    for pat, rep in reglas:
        nombre = re.sub(pat, rep, nombre, flags=re.IGNORECASE)
    nombre = re.sub(r'[\ufffd\?]+', '', nombre)
    return re.sub(r'\s+', ' ', nombre).strip()

def limpiar_nombre_persona(nombre_raw):
    if not nombre_raw:
        return ""
    reglas = [
        (r'M[\ufffd\?é]?\s*ndez', 'Méndez'),
        (r'Gonz[\ufffd\?á]?\s*lez', 'González'),
        (r'P[\ufffd\?é]?\s*rez', 'Pérez'),
        (r'S[\ufffd\?á]?\s*nchez', 'Sánchez'),
        (r'Rodr[\ufffd\?í]?\s*guez', 'Rodríguez'),
        (r'Mart[\ufffd\?í]?\s*nez', 'Martínez'),
        (r'Hern[\ufffd\?á]?\s*ndez', 'Hernández'),
        (r'Garc[\ufffd\?í]?\s*a', 'García'),
        (r'L[\ufffd\?ó]?\s*pez', 'López'),
        (r'D[\ufffd\?í]?\s*az', 'Díaz')
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
        "formato_detectado": "DESCONOCIDO",
        "estudiante": None,
        "cedula": None,
        "codigo_estudiante": None,
        "carrera_codigo": None,
        "carrera_nombre": None,
        "ano": None,
        "periodo": None,
        "total_creditos": 0.0,
        "total_creditos_declarado": None,
        "codigo_control": None,
        "control_code_valido": None,
        "codigo_cedula_coincide": None,
        "suma_creditos_coincide": None,
        "sellos_detectados": False,
        "alertas_seguridad": [],
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
    texto_completo = texto_completo.replace('\xa0', ' ')
    texto_norm = normalizar(texto_completo)
    texto_espacios = re.sub(r'[ \t]+', ' ', texto_completo)

    # 2. Identificación de Variante de Formato Oficial UMC
    if "forma 006 ree" in texto_norm or "articulo 90" in texto_norm or "cumplidos los tramites de inscripcion" in texto_norm:
        resultado["formato_detectado"] = "FORMA_006_REE"
    elif "reg-seg-cre-upe-002" in texto_norm or "upe-002" in texto_norm or "planilla_umc.jsp" in texto_norm or "registro de inscripcion" in texto_norm:
        resultado["formato_detectado"] = "PLANILLA_WEB_UPE002"

    # 3. Criterios de Seguridad Institucional UMC
    puntos = 0
    criterios = {
        "maritima": ("maritima" in texto_norm or "caribe" in texto_norm or "umc" in texto_norm),
        "secretaria": ("secretaria" in texto_norm),
        "registro": ("registro" in texto_norm or "regis" in texto_norm or "reg-seg" in texto_norm or "inscripcion" in texto_norm or "prosecucion" in texto_norm),
        "formulario": ("upe" in texto_norm or "forma 006" in texto_norm or "002" in texto_norm),
        "titulo": ("inscripcion" in texto_norm or "tramites" in texto_norm or "estudiantil" in texto_norm),
        "republica": ("republica" in texto_norm or "venezuela" in texto_norm or "bolivariana" in texto_norm)
    }

    for k, ok in criterios.items():
        if ok:
            puntos += 10

    if puntos < 10:
        resultado["motivo_invalidez"] = "El documento suministrado no posee las marcas de agua, membretes ni encabezados institucionales de la Universidad Marítima del Caribe (UMC)."
        return resultado

    # Detección de sellos institucionales
    if "(sello)" in texto_norm or "coordinacion de registro estudiantil" in texto_norm:
        resultado["sellos_detectados"] = True
        puntos += 10

    # 4. Extracción de Datos del Estudiante
    # Estudiante / Nombre Completo
    m_est = re.search(r'Nombre\s*:\s*([^\n\r]+?)(?:\s+Programa\s+de|\s+C[eé\?]+dula|\r|\n|$)', texto_espacios, re.IGNORECASE)
    if not m_est:
        m_est = re.search(r'Bachiller:\s*(.*?)\s+Estudiante\s+Nro\.?:', texto_espacios, re.DOTALL | re.IGNORECASE)
    if not m_est:
        m_est = re.search(r'Bachiller:\s*(.*?)\s+(?:portador|\n)', texto_espacios, re.IGNORECASE)
    if m_est:
        resultado["estudiante"] = limpiar_nombre_persona(m_est.group(1))

    # Cédula de Identidad
    m_ced = re.search(r'C[eé\?]+dula(?:\s+(?:de\s+)?Identidad)?\s*[:\.]?\s*([VvEe]\s*-\s*\d+)', texto_espacios, re.IGNORECASE)
    if not m_ced:
        m_ced = re.search(r'\b([VvEe]\s*-\s*\d{6,9})\b', texto_espacios)
    if m_ced:
        resultado["cedula"] = re.sub(r'\s+', '', m_ced.group(1)).upper()

    # Código de Estudiante
    m_cod = re.search(r'C[oó\?]+digo(?:\s+del)?\s+Estudiante\s*[:\.]?\s*([A-Za-z0-9\-]+)', texto_espacios, re.IGNORECASE)
    if not m_cod:
        m_cod = re.search(r'Estudiante\s+Nro\.?:\s*([A-Za-z0-9\-]+)', texto_espacios, re.IGNORECASE)
    if m_cod:
        resultado["codigo_estudiante"] = m_cod.group(1).strip()

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

    # Total Créditos Declarado en el comprobante
    m_tot = re.search(r'Total\s+Cr[eé\?]+ditos\s*[:\.]?\s*(\d+(?:\.\d+)?)', texto_espacios, re.IGNORECASE)
    if m_tot:
        resultado["total_creditos_declarado"] = float(m_tot.group(1))
        resultado["total_creditos"] = resultado["total_creditos_declarado"]

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

    # 5. Extracción de Materias
    materias_encontradas = []

    # Formato A: Fila completa (Web JSP: Semestre Codigo - Nombre Seccion Creditos)
    # Ej: 06 CAL604 - Cálculo Numérico SVC-A 4.0
    patron_upe002 = re.compile(
        r'^\s*(?:(\d{1,2})\s+)?([A-Za-z0-9]{4,8})\s*[-–]\s*(.+?)\s+([A-Za-z0-9\-]+)\s+(\d+(?:\.\d+)?)\s*$',
        re.MULTILINE
    )
    for m in patron_upe002.finditer(texto_completo):
        semestre = int(m.group(1)) if m.group(1) else 1
        codigo = m.group(2).strip().upper()
        nombre_limpio = limpiar_nombre_materia(m.group(3).strip())
        seccion = m.group(4).strip().upper()
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
                    codigo = m_ind.group(2).strip().upper()
                    nombre_limpio = limpiar_nombre_materia(m_ind.group(3).strip())
                    seccion = m_next.group(1).strip().upper()
                    creditos = float(m_next.group(2))
                    materias_encontradas.append({
                        "semestre": semestre,
                        "codigo": codigo,
                        "nombre": nombre_limpio,
                        "seccion": seccion,
                        "creditos": creditos
                    })

    # Formato C: Histórico / Certificado Oficial (Forma 006 REE)
    # Ej: 2026 - 2026-01 04 SVC-A LAF422 - Laboratorio de Física II EN CURSO
    if not materias_encontradas:
        patron_hist = re.compile(
            r'(\d{4}\s*-\s*\d{4}-\d{2})\s+(\d{1,2})\s+([A-Z0-9\-]+)\s+([A-Z0-9]{4,8})\s*[-–]\s*(.+?)\s+(?:E\s*N\s+CURSO|APROBADA|INSCRITA|REPROBADA|CURSANDO)',
            re.IGNORECASE
        )
        for m in patron_hist.finditer(texto_espacios):
            semestre = int(m.group(2))
            seccion = m.group(3).strip().upper()
            codigo = m.group(4).strip().upper()
            nom_limpio = limpiar_nombre_materia(m.group(5).strip())

            # En la UMC, el último dígito del código de materia representa sus unidades de crédito
            # Ej: LAF422 -> 2 UC, CAL604 -> 4 UC, PRO633 -> 3 UC, SSI502 -> 2 UC
            credito_estimado = 3.0
            if len(codigo) >= 6 and codigo[-1].isdigit():
                dig = int(codigo[-1])
                if 1 <= dig <= 6:
                    credito_estimado = float(dig)

            materias_encontradas.append({
                "semestre": semestre,
                "codigo": codigo,
                "nombre": nom_limpio,
                "seccion": seccion,
                "creditos": credito_estimado
            })

    resultado["materias"] = materias_encontradas

    if resultado["total_creditos"] == 0.0 and materias_encontradas:
        resultado["total_creditos"] = round(sum(m["creditos"] for m in materias_encontradas), 1)

    # 6. FILTROS DE SEGURIDAD Y PREVENCIÓN DE FRAUDE AVANZADOS (UMC)

    # A. Verificación de Código de Estudiante vs Cédula
    if resultado["codigo_estudiante"] and resultado["cedula"]:
        num_cod = re.sub(r'\D', '', resultado["codigo_estudiante"])
        num_ced = re.sub(r'\D', '', resultado["cedula"])
        if num_cod and num_ced:
            if num_cod == num_ced:
                resultado["codigo_cedula_coincide"] = True
                puntos += 15
            else:
                resultado["codigo_cedula_coincide"] = False
                resultado["alertas_seguridad"].append(
                    f"El Código de Estudiante institucional ('{resultado['codigo_estudiante']}') difiere de la Cédula ('{resultado['cedula']}')."
                )

    # B. Verificación de Código de Control Criptográfico UMC (Para Planilla Web JSP)
    # Patrón: 3 dígitos (cola de cédula) + inicial apellido + código institucional UMC (020302311)
    control_codes = re.findall(r'\b(\d{3}[A-Za-z]\d{6,12})\b', texto_completo)
    if control_codes:
        resultado["codigo_control"] = control_codes[0].upper()
        if resultado["cedula"] and resultado["estudiante"]:
            ced_tail = re.sub(r'\D', '', resultado["cedula"])[-3:]
            # Obtener inicial del primer apellido
            primer_apellido = resultado["estudiante"].strip().split()[0]
            apellido_norm = normalizar(primer_apellido)
            apellido_inicial = apellido_norm[0].upper() if apellido_norm else ''

            prefijo_esperado = f"{ced_tail}{apellido_inicial}"
            if resultado["codigo_control"].startswith(prefijo_esperado):
                resultado["control_code_valido"] = True
                puntos += 20
            else:
                resultado["control_code_valido"] = False
                resultado["alertas_seguridad"].append(
                    f"Código de control UMC preventivo: se esperaba prefijo '{prefijo_esperado}', pero se encontró '{resultado['codigo_control'][:4]}'."
                )

    # C. Cuadratura Matemática de Créditos
    if resultado["total_creditos_declarado"] is not None and materias_encontradas:
        suma_calculada = round(sum(m["creditos"] for m in materias_encontradas), 1)
        if abs(suma_calculada - resultado["total_creditos_declarado"]) < 0.1:
            resultado["suma_creditos_coincide"] = True
            puntos += 10
        else:
            resultado["suma_creditos_coincide"] = False
            resultado["alertas_seguridad"].append(
                f"Inconsistencia matemática en créditos: Declarados={resultado['total_creditos_declarado']}, Suma={suma_calculada}."
            )
            # Discrepancia no fatal registrada en alertas_seguridad

    # D. Verificación de Carrera vs Prefijo de Código de Estudiante
    if resultado["codigo_estudiante"] and resultado["carrera_codigo"]:
        prefijos_carrera = {
            'ING-INF': 'INGINF',
            'ING-MAR': 'INGMAR',
            'ING-AMB': 'INGAMB',
            'ADM': 'ADM',
            'TUR': 'TUR'
        }
        prefijo_esperado = prefijos_carrera.get(resultado["carrera_codigo"])
        if prefijo_esperado and not resultado["codigo_estudiante"].upper().startswith(prefijo_esperado):
            resultado["alertas_seguridad"].append(
                f"Discrepancia en código de estudiante: Prefijo '{resultado['codigo_estudiante']}' no corresponde a la carrera {resultado['carrera_codigo']}."
            )

    # 7. Evaluación Final de Validez y Puntuación
    if resultado["carrera_codigo"]:
        puntos += 10
    if resultado["materias"]:
        puntos += 15
    if resultado["estudiante"]:
        puntos += 10

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
