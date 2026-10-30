<?php

namespace App\Services;

use App\Models\MateriaModel;
use App\Models\SeccionModel;
use App\Models\InscripcionModel;
use App\Models\ClaseProgramadaModel;
use App\Models\AulasModel;

/**
 * Servicio Inteligente de Verificación de Documentos UMC
 * Escanea, analiza y valida comprobantes de inscripción emitidos por la
 * Universidad Nacional Experimental Marítima del Caribe (UMC).
 * 
 * Previene documentos falsificados / manipulados y extrae automáticamente
 * los datos del estudiante, la carrera certificada y las materias inscritas.
 */
class VerificadorInscripcionService
{
    /**
     * Mapeo de nombres oficiales de carrera UMC a sus códigos estándar
     */
    protected array $mapaCarreras = [
        'ingenieria en informatica'      => 'ING-INF',
        'informatica'                    => 'ING-INF',
        'ingenieria maritima'            => 'ING-MAR',
        'ingenieria ambiental'           => 'ING-AMB',
        'ambiental'                      => 'ING-AMB',
        'licenciatura en administracion' => 'ADM',
        'administracion'                 => 'ADM',
        'licenciatura en turismo'        => 'TUR',
        'turismo'                        => 'TUR',
    ];

    /**
     * Nombres legibles oficiales
     */
    protected array $nombresCarreras = [
        'ING-INF' => 'Ingeniería en Informática',
        'ING-MAR' => 'Ingeniería Marítima',
        'ING-AMB' => 'Ingeniería Ambiental',
        'ADM'     => 'Licenciatura en Administración',
        'TUR'     => 'Licenciatura en Turismo',
    ];

    /**
     * Ejecuta el analizador de alta fidelidad Python con pypdf
     */
    protected function verificarConMotorPython(string $rutaPdf): ?array
    {
        $rootPath = defined('ROOTPATH') ? ROOTPATH : realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR;
        $pythonExes = [
            $rootPath . 'scripts_python/venv/Scripts/python.exe',
            $rootPath . 'scripts_python/venv/bin/python',
            'python',
            'python3'
        ];

        $pythonExecutable = null;
        foreach ($pythonExes as $exe) {
            if (file_exists($exe)) {
                $pythonExecutable = $exe;
                break;
            }
        }

        if (!$pythonExecutable) {
            foreach (['python', 'python3'] as $cmd) {
                $test = @shell_exec($cmd . ' -c "import pypdf; print(\'OK\')" 2>nul');
                if ($test && trim($test) === 'OK') {
                    $pythonExecutable = $cmd;
                    break;
                }
            }
        }

        if (!$pythonExecutable) {
            return null;
        }

        $scriptPath = $rootPath . 'scripts_python/verificar_inscripcion.py';
        if (!file_exists($scriptPath)) {
            return null;
        }

        $cmd = escapeshellarg($pythonExecutable) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($rutaPdf);
        $output = @shell_exec($cmd);
        if (empty($output)) {
            return null;
        }

        $jsonStart = strpos($output, '{');
        $jsonEnd = strrpos($output, '}');
        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
            $jsonStr = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
            $datos = json_decode($jsonStr, true);
            return is_array($datos) ? $datos : null;
        }

        return null;
    }

    /**
     * Extrae texto puro de un archivo PDF utilizando descompresión zlib
     * y tokenización nativa de operadores de texto de PDF (motor PHP de respaldo).
     */
    public function extraerLineasDePdf(string $rutaPdf): array
    {
        if (!file_exists($rutaPdf) || !is_readable($rutaPdf)) {
            return [];
        }

        // 1. Motor primario PHP: Smalot PdfParser (alta fidelidad nativa, sin dependencias externas)
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($rutaPdf);
                $texto = $pdf->getText();
                if (!empty($texto) && strlen(trim($texto)) > 30) {
                    $texto = preg_replace('/[\x{00A0}\x{2000}-\x{200B}]+/u', ' ', $texto);
                    $lineasRaw = preg_split('/[\r\n]+/', $texto);
                    $lineas = [];
                    foreach ($lineasRaw as $l) {
                        $l = trim($l);
                        if ($l !== '') {
                            $lineas[] = $l;
                        }
                    }
                    if (!empty($lineas)) {
                        return $lineas;
                    }
                }
            } catch (\Throwable $e) {
                // Continuar con respaldo manual de streams
            }
        }

        $rawPdf = file_get_contents($rutaPdf);
        if ($rawPdf === false || strlen($rawPdf) < 100) {
            return [];
        }

        // Buscar todos los streams binarios del PDF
        preg_match_all('#stream[\r\n]+(.*?)[\r\n]+endstream#s', $rawPdf, $matches);
        if (empty($matches[1])) {
            return [];
        }

        $allLines = [];

        foreach ($matches[1] as $stream) {
            $decompressed = @gzuncompress($stream);
            if ($decompressed === false) {
                $decompressed = @gzinflate(substr($stream, 2));
                if ($decompressed === false) {
                    $decompressed = $stream;
                }
            }

            if (strpos($decompressed, 'BT') !== false) {
                $lineasStream = $this->tokenizarOperadoresTextoPdf($decompressed);
                $allLines = array_merge($allLines, $lineasStream);
            }
        }

        return $allLines;
    }

    /**
     * Tokenizador de flujo PDF que respeta cadenas entre paréntesis,
     * arreglos TJ de kerning, saltos Td/Tm y secuencias de escape octales.
     */
    protected function tokenizarOperadoresTextoPdf(string $stream): array
    {
        $len = strlen($stream);
        $i = 0;
        $lines = [];
        $currentLine = '';

        while ($i < $len) {
            $ch = $stream[$i];

            // 1. Literal de cadena de texto ( ... )
            if ($ch === '(') {
                $i++;
                $str = '';
                $depth = 1;
                while ($i < $len && $depth > 0) {
                    if ($stream[$i] === '\\') {
                        $str .= $stream[$i] . ($stream[$i+1] ?? '');
                        $i += 2;
                        continue;
                    }
                    if ($stream[$i] === '(') $depth++;
                    elseif ($stream[$i] === ')') $depth--;

                    if ($depth > 0) {
                        $str .= $stream[$i];
                    }
                    $i++;
                }
                $currentLine .= $this->decodificarCadenaPdf($str);
                continue;
            }

            // 2. Arreglo TJ [ (cadena) -kerning (cadena) ]
            if ($ch === '[') {
                $i++;
                $arrStr = '';
                while ($i < $len && $stream[$i] !== ']') {
                    if ($stream[$i] === '(') {
                        $i++;
                        $str = '';
                        $depth = 1;
                        while ($i < $len && $depth > 0) {
                            if ($stream[$i] === '\\') {
                                $str .= $stream[$i] . ($stream[$i+1] ?? '');
                                $i += 2;
                                continue;
                            }
                            if ($stream[$i] === '(') $depth++;
                            elseif ($stream[$i] === ')') $depth--;

                            if ($depth > 0) $str .= $stream[$i];
                            $i++;
                        }
                        $arrStr .= $this->decodificarCadenaPdf($str);
                    } elseif (preg_match('/^-?\d+/', substr($stream, $i), $numM)) {
                        $num = (int)$numM[0];
                        if ($num < -150) {
                            $arrStr .= ' ';
                        }
                        $i += strlen($numM[0]);
                    } else {
                        $i++;
                    }
                }
                $currentLine .= $arrStr;
                if ($i < $len && $stream[$i] === ']') $i++;
                continue;
            }

            // 3. Operadores de posicionamiento y salto de línea (Td, TD, Tm, T*)
            if (substr($stream, $i, 2) === 'Td' || substr($stream, $i, 2) === 'TD' || substr($stream, $i, 2) === 'Tm' || substr($stream, $i, 2) === 'T*') {
                if (trim($currentLine) !== '') {
                    $lines[] = trim($currentLine);
                    $currentLine = '';
                }
                $i += 2;
                continue;
            }

            $i++;
        }

        if (trim($currentLine) !== '') {
            $lines[] = trim($currentLine);
        }

        return $lines;
    }

    /**
     * Decodifica escapes octales y caracteres especiales
     */
    protected function decodificarCadenaPdf(string $str): string
    {
        $str = preg_replace_callback('/\\\\([0-7]{1,3})/', function($m) {
            return chr(octdec($m[1]));
        }, $str);

        $str = str_replace(['\\(', '\\)', '\\\\', "\\n", "\\r"], ['(', ')', '\\', '', ''], $str);

        if (!mb_check_encoding($str, 'UTF-8')) {
            $str = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
        }

        return $str;
    }

    /**
     * Limpia y restaura acentuaciones comunes en materias UMC
     */
    public function limpiarNombreMateria(string $nombre): string
    {
        // Eliminar estatus de materia si quedó pegado al final
        $nombre = preg_replace('/\s+E\s*N\s+CURSO\b.*$/iu', '', $nombre);
        $nombre = preg_replace('/\s+(?:APROBADA|INSCRITA|CURSANDO)\b.*$/iu', '', $nombre);

        $reglas = [
            '/M[\x{fffd}\?é]?\s*ndez/iu'         => 'Méndez',
            '/C[\x{fffd}\?á]?\s*lculo/iu'        => 'Cálculo',
            '/Num[\x{fffd}\?é]?\s*rico/iu'       => 'Numérico',
            '/F[\x{fffd}\?íi]?\s*sica/iu'        => 'Física',
            '/Programaci[\x{fffd}\?óo]?\s*n/iu'  => 'Programación',
            '/Inform[\x{fffd}\?áa]?\s*tica/iu'   => 'Informática',
            '/Ingenier[\x{fffd}\?íi]?\s*a/iu'    => 'Ingeniería',
            '/Administraci[\x{fffd}\?óo]?\s*n/iu'=> 'Administración',
            '/Segur\s+idad/iu'                   => 'Seguridad',
            '/Estad[\x{fffd}\?íi]?\s*stica/iu'   => 'Estadística',
            '/Electr[\x{fffd}\?óo]?\s*nica/iu'   => 'Electrónica',
            '/M[\x{fffd}\?é]?\s*todos/iu'        => 'Métodos',
            '/Q[\x{fffd}\?ú]?\s*mica/iu'         => 'Química',
            '/Decisi[\x{fffd}\?óo]?\s*n/iu'      => 'Decisión',
            '/Tecnolog[\x{fffd}\?íi]?\s*a/iu'    => 'Tecnología',
            '/Reparaci[\x{fffd}\?óo]?\s*n/iu'    => 'Reparación',
            '/Teor[\x{fffd}\?íi]?\s*as/iu'       => 'Teorías',
            '/T[\x{fffd}\?é]?\s*cnicas/iu'       => 'Técnicas',
            '/Ingl[\x{fffd}\?é]?\s*s/iu'         => 'Inglés',
        ];

        foreach ($reglas as $pat => $rep) {
            $nombre = preg_replace($pat, $rep, $nombre);
        }

        $nombre = preg_replace('/[\x{fffd}\?]+/u', '', $nombre);
        return trim(preg_replace('/\s+/', ' ', $nombre));
    }

    /**
     * Limpia nombres de personas restaurando acentos si se corrompieron en el PDF
     */
    public function limpiarNombrePersona(string $nombre): string
    {
        $nombre = preg_replace('/(?:\s*Estudiante\s*Nro.*|\s*portador.*|\s*C[eé]dula.*)$/iu', '', $nombre);
        $reglas = [
            '/M[\x{fffd}\?]?ndez/iu'    => 'Méndez',
            '/Gonz[\x{fffd}\?]?lez/iu'  => 'González',
            '/P[\x{fffd}\?]?rez/iu'     => 'Pérez',
            '/S[\x{fffd}\?]?nchez/iu'   => 'Sánchez',
            '/Rodr[\x{fffd}\?]?guez/iu' => 'Rodríguez',
            '/Mart[\x{fffd}\?]?nez/iu'  => 'Martínez',
            '/Hern[\x{fffd}\?]?ndez/iu' => 'Hernández',
            '/Garc[\x{fffd}\?]?a/iu'    => 'García',
            '/L[\x{fffd}\?]?pez/iu'     => 'López',
            '/D[\x{fffd}\?]?az/iu'      => 'Díaz',
        ];

        foreach ($reglas as $pat => $rep) {
            $nombre = preg_replace($pat, $rep, $nombre);
        }

        $nombre = preg_replace('/[\x{fffd}\?]+/u', '', $nombre);
        return trim(preg_replace('/\s+/', ' ', $nombre));
    }

    /**
     * Normaliza cadenas eliminando acentos y mayúsculas para búsquedas seguras
     */
    protected function normalizarTexto(string $str): string
    {
        $str = mb_strtolower($str, 'UTF-8');
        $remplazos = ['á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ñ'=>'n'];
        return strtr($str, $remplazos);
    }

    /**
     * Analiza y verifica un archivo PDF de inscripción.
     * Retorna un arreglo estructurado con la validez, confianza y datos extraídos.
     */
    public function verificar(string $rutaPdf, ?string $carreraEsperada = null): array
    {
        // 1. Intentar con el motor inteligente Python (pypdf/pdfminer)
        $resultadoPython = $this->verificarConMotorPython($rutaPdf);
        if ($resultadoPython !== null && is_array($resultadoPython) && !empty($resultadoPython['es_valido'])) {
            if (!empty($carreraEsperada) && !empty($resultadoPython['carrera_codigo']) && $resultadoPython['carrera_codigo'] !== $carreraEsperada) {
                $carreraDoc = $resultadoPython['carrera_nombre'] ?? $resultadoPython['carrera_codigo'];
                $carreraEsp = $this->nombresCarreras[$carreraEsperada] ?? $carreraEsperada;
                $resultadoPython['es_valido'] = false;
                $resultadoPython['motivo_invalidez'] = "Inconsistencia de carrera: El comprobante presentado pertenece a '{$carreraDoc}', pero intentas registrar o cambiarte a '{$carreraEsp}'.";
            }
            return $resultadoPython;
        }

        if ($resultadoPython !== null && is_array($resultadoPython) && !empty($resultadoPython['motivo_invalidez']) && ($resultadoPython['lineas_extraidas'] ?? 0) > 0) {
            return $resultadoPython;
        }

        // 2. Respaldo nativo en PHP
        $resultado = [
            'es_valido'                 => false,
            'motivo_invalidez'          => null,
            'puntuacion_confianza'      => 0,
            'formato_detectado'         => 'DESCONOCIDO',
            'estudiante'                => null,
            'cedula'                    => null,
            'codigo_estudiante'         => null,
            'carrera_codigo'            => null,
            'carrera_nombre'            => null,
            'ano'                       => null,
            'periodo'                   => null,
            'total_creditos'            => 0.0,
            'total_creditos_declarado'  => null,
            'codigo_control'            => null,
            'control_code_valido'       => null,
            'codigo_cedula_coincide'    => null,
            'suma_creditos_coincide'    => null,
            'sellos_detectados'         => false,
            'alertas_seguridad'         => [],
            'materias'                  => [],
            'lineas_extraidas'          => 0,
        ];

        $lines = $this->extraerLineasDePdf($rutaPdf);
        $resultado['lineas_extraidas'] = count($lines);

        if (empty($lines)) {
            $resultado['motivo_invalidez'] = 'El archivo no contiene capas de texto legibles o es una imagen escaneada no vectorizada.';
            return $resultado;
        }

        $fullText = preg_replace('/[\x{00A0}\x{2000}-\x{200B}]+/u', ' ', implode("\n", $lines));
        $textNorm = $this->normalizarTexto($fullText);
        $fullTextSpaces = preg_replace('/[ \t]+/', ' ', preg_replace('/[\x{00A0}\x{2000}-\x{200B}]+/u', ' ', implode(' ', $lines)));

        // Identificación de Variante de Formato Oficial UMC
        if (strpos($textNorm, 'forma 006 ree') !== false || strpos($textNorm, 'articulo 90') !== false || strpos($textNorm, 'cumplidos los tramites de inscripcion') !== false) {
            $resultado['formato_detectado'] = 'FORMA_006_REE';
        } elseif (strpos($textNorm, 'reg-seg-cre-upe-002') !== false || strpos($textNorm, 'upe-002') !== false || strpos($textNorm, 'planilla_umc.jsp') !== false || strpos($textNorm, 'registro de inscripcion') !== false) {
            $resultado['formato_detectado'] = 'PLANILLA_WEB_UPE002';
        }

        // Criterios de Seguridad Institucional UMC
        $puntos = 0;
        $criterios = [
            'institucion' => (strpos($textNorm, 'maritima') !== false || strpos($textNorm, 'caribe') !== false || strpos($textNorm, 'umc') !== false),
            'secretaria'  => (strpos($textNorm, 'secretaria') !== false),
            'registro_est'=> (strpos($textNorm, 'registro') !== false || strpos($textNorm, 'regis') !== false || strpos($textNorm, 'reg-seg') !== false || strpos($textNorm, 'inscripcion') !== false || strpos($textNorm, 'prosecucion') !== false),
            'formulario'  => (strpos($textNorm, 'upe') !== false || strpos($textNorm, 'forma 006') !== false || strpos($textNorm, '002') !== false),
            'titulo'      => (strpos($textNorm, 'inscripcion') !== false || strpos($textNorm, 'tramites') !== false || strpos($textNorm, 'estudiantil') !== false),
            'republica'   => (strpos($textNorm, 'republica') !== false || strpos($textNorm, 'venezuela') !== false || strpos($textNorm, 'bolivariana') !== false),
        ];

        foreach ($criterios as $check => $cumple) {
            if ($cumple) $puntos += 10;
        }

        if ($puntos < 10) {
            $resultado['motivo_invalidez'] = 'El documento suministrado no posee membretes ni encabezados institucionales legibles de la Universidad Marítima del Caribe (UMC).';
            return $resultado;
        }

        // Detección de sellos institucionales
        if (strpos($textNorm, '(sello)') !== false || strpos($textNorm, 'coordinacion de registro estudiantil') !== false) {
            $resultado['sellos_detectados'] = true;
            $puntos += 10;
        }

        // Extracción de datos del estudiante con patrones flexibles UMC
        // 1. Nombre completo
        if (preg_match('/Nombre\s*:\s*([^,]+?)(?=\s+Programa\s+de|\s+C[eé]dula|\s+A[ñn]o|$)/iu', $fullTextSpaces, $m)) {
            $resultado['estudiante'] = $this->limpiarNombrePersona(trim($m[1]));
        } elseif (preg_match('/Bachiller:\s*(.*?)(?=\s*Estudiante\s*Nro|portador|\n|$)/iu', $fullTextSpaces, $m)) {
            $resultado['estudiante'] = $this->limpiarNombrePersona(trim($m[1]));
        }

        // 2. Cédula
        if (preg_match('/(?:c[ée]dula(?:\s+(?:de\s+)?identidad)?|identidad)\s*[:\.]?\s*([VvEe]\s*-\s*\d+)/iu', $fullTextSpaces, $m)) {
            $resultado['cedula'] = strtoupper(str_replace(' ', '', $m[1]));
        } elseif (preg_match('/\b([VvEe]\s*-\s*\d{6,9})\b/u', $fullTextSpaces, $m)) {
            $resultado['cedula'] = strtoupper(str_replace(' ', '', $m[1]));
        }

        // 3. Código de estudiante
        if (preg_match('/(?:c[oó]digo(?:\s+del)?\s+estudiante|estudiante\s+nro\.?)\s*[:\.]?\s*([A-Za-z0-9\-]+)/iu', $fullTextSpaces, $m)) {
            $resultado['codigo_estudiante'] = trim($m[1]);
        }

        // 4. Año y período
        if (preg_match('/(?:del\s+a[ñn]o|a[ñn]o)\s*[:\.]?\s*(\d{4})/iu', $fullTextSpaces, $m)) {
            $resultado['ano'] = trim($m[1]);
        }
        if (preg_match('/per[íi]odo(?:\s+acad[ée]mico)?\s*[:\.]?\s*(\d{4}-\d{2})/iu', $fullTextSpaces, $m)) {
            $resultado['periodo'] = trim($m[1]);
        }

        // 5. Total créditos declarado
        if (preg_match('/total\s+cr[eé]ditos\s*[:\.]?\s*(\d+(?:\.\d+)?)/iu', $fullTextSpaces, $m)) {
            $resultado['total_creditos_declarado'] = (float)$m[1];
            $resultado['total_creditos'] = $resultado['total_creditos_declarado'];
        }

        // 6. Programa de Formación / Carrera Universitaria
        if (preg_match('/Programa\s+de\s+Formaci[óo]n\s*[:\.]?\s*([^,]+?)(?=\s+C[eé]dula|\s+A[ñn]o|\s+C[oó]digo|$)/iu', $fullTextSpaces, $m)) {
            $carNorm = $this->normalizarTexto($m[1]);
            foreach ($this->mapaCarreras as $frase => $codigoCarrera) {
                if (strpos($carNorm, $frase) !== false) {
                    $resultado['carrera_codigo'] = $codigoCarrera;
                    $resultado['carrera_nombre'] = $this->nombresCarreras[$codigoCarrera];
                    break;
                }
            }
        }

        if (empty($resultado['carrera_codigo'])) {
            foreach ($this->mapaCarreras as $frase => $codigoCarrera) {
                if (strpos($textNorm, $frase) !== false) {
                    $resultado['carrera_codigo'] = $codigoCarrera;
                    $resultado['carrera_nombre'] = $this->nombresCarreras[$codigoCarrera];
                    break;
                }
            }
        }

        // Extracción de materias (Formato A: Fila completa / Formato B: Dos líneas / Formato C: Histórico)
        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '') continue;

            // Formato A: Fila completa (06 CAL604 - Cálculo Numérico SVC-A 4.0)
            if (preg_match('/^\s*(?:(\d{1,2})\s+)?([A-Za-z0-9]{4,8})\s*[-–]\s*(.+?)\s+([A-Za-z0-9\-]+)\s+(\d+(?:\.\d+)?)\s*$/u', $line, $m)) {
                $resultado['materias'][] = [
                    'semestre' => !empty($m[1]) ? (int)$m[1] : 1,
                    'codigo'   => strtoupper(trim($m[2])),
                    'nombre'   => $this->limpiarNombreMateria(trim($m[3])),
                    'seccion'  => strtoupper(trim($m[4])),
                    'creditos' => (float)$m[5]
                ];
                continue;
            }

            // Formato B: Dos líneas (Línea 1: 06 CAL604 - Cálculo Numérico / Línea 2: SVC-A 4.0)
            if (preg_match('/^\s*(?:(\d{1,2})\s+)?([A-Za-z0-9]{4,8})\s*[-–]\s*(.+?)\s*$/u', $line, $m1)) {
                if ($i + 1 < count($lines)) {
                    $nextLine = trim($lines[$i + 1]);
                    if (preg_match('/^([A-Za-z0-9\-]+)\s+(\d+(?:\.\d+)?)$/u', $nextLine, $m2)) {
                        $resultado['materias'][] = [
                            'semestre' => !empty($m1[1]) ? (int)$m1[1] : 1,
                            'codigo'   => strtoupper(trim($m1[2])),
                            'nombre'   => $this->limpiarNombreMateria(trim($m1[3])),
                            'seccion'  => strtoupper(trim($m2[1])),
                            'creditos' => (float)$m2[2]
                        ];
                        $i++;
                        continue;
                    }
                }
            }

            // Formato C: Histórico con estados (EN CURSO, APROBADA, INSCRITA)
            if (preg_match('/(\d{4}\s*-\s*\d{4}-\d{2})\s+(\d{1,2})\s+([A-Z0-9\-]+)\s+([A-Z0-9]{4,8})\s*[-–]\s*(.+?)\s+(?:E\s*N\s+CURSO|APROBADA|INSCRITA)/iu', $line, $m)) {
                $cod = strtoupper(trim($m[4]));
                $cred = 3.0;
                if (strlen($cod) >= 6 && ctype_digit(substr($cod, -1))) {
                    $d = (int)substr($cod, -1);
                    if ($d >= 1 && $d <= 6) {
                        $cred = (float)$d;
                    }
                }
                $resultado['materias'][] = [
                    'semestre' => (int)$m[2],
                    'codigo'   => $cod,
                    'nombre'   => $this->limpiarNombreMateria(trim($m[5])),
                    'seccion'  => strtoupper(trim($m[3])),
                    'creditos' => $cred
                ];
            }
        }

        if (empty($resultado['ano']) && !empty($resultado['periodo'])) {
            $resultado['ano'] = explode('-', $resultado['periodo'])[0];
        }
        if (empty($resultado['periodo']) && !empty($resultado['ano'])) {
            $resultado['periodo'] = $resultado['ano'] . '-01';
        }
        if ($resultado['total_creditos'] == 0.0 && !empty($resultado['materias'])) {
            $sum = 0.0;
            foreach ($resultado['materias'] as $mat) {
                $sum += (float)($mat['creditos'] ?? 0);
            }
            $resultado['total_creditos'] = round($sum, 1);
        }

        // 7. FILTROS DE SEGURIDAD Y ANTI-FRAUDE UMC

        // A. Verificación de Código de Estudiante vs Cédula
        if (!empty($resultado['codigo_estudiante']) && !empty($resultado['cedula'])) {
            $numCod = preg_replace('/\D/', '', $resultado['codigo_estudiante']);
            $numCed = preg_replace('/\D/', '', $resultado['cedula']);
            if ($numCod && $numCed) {
                if ($numCod === $numCed) {
                    $resultado['codigo_cedula_coincide'] = true;
                    $puntos += 15;
                } else {
                    $resultado['codigo_cedula_coincide'] = false;
                    $resultado['alertas_seguridad'][] = "El Código de Estudiante institucional ('{$resultado['codigo_estudiante']}') difiere de la Cédula ('{$resultado['cedula']}').";
                }
            }
        }

        // B. Verificación de Código de Control Criptográfico UMC (Para Planilla Web JSP)
        if (preg_match_all('/\b(\d{3}[A-Za-z]\d{6,12})\b/', $fullTextSpaces, $mCodes)) {
            $resultado['codigo_control'] = strtoupper($mCodes[1][0]);
            if (!empty($resultado['cedula']) && !empty($resultado['estudiante'])) {
                $cedDigits = preg_replace('/\D/', '', $resultado['cedula']);
                $cedTail = substr($cedDigits, -3);
                $partesNombre = explode(' ', trim($resultado['estudiante']));
                $primerApellido = $partesNombre[0] ?? '';
                $apellidoNorm = $this->normalizarTexto($primerApellido);
                $apellidoInicial = strtoupper(substr($apellidoNorm, 0, 1));
                $prefijoEsperado = $cedTail . $apellidoInicial;

                if (str_starts_with($resultado['codigo_control'], $prefijoEsperado)) {
                    $resultado['control_code_valido'] = true;
                    $puntos += 20;
                } else {
                    $resultado['control_code_valido'] = false;
                    $resultado['alertas_seguridad'][] = "Código de control UMC preventivo: registrado '{$resultado['codigo_control']}' (prefijo esperado '{$prefijoEsperado}').";
                }
            }
        }

        // C. Cuadratura Matemática de Créditos
        if ($resultado['total_creditos_declarado'] !== null && !empty($resultado['materias'])) {
            $sumaCalculada = 0.0;
            foreach ($resultado['materias'] as $mat) {
                $sumaCalculada += (float)($mat['creditos'] ?? 0);
            }
            $sumaCalculada = round($sumaCalculada, 1);
            if (abs($sumaCalculada - $resultado['total_creditos_declarado']) < 0.1) {
                $resultado['suma_creditos_coincide'] = true;
                $puntos += 10;
            } else {
                $resultado['suma_creditos_coincide'] = false;
                $resultado['alertas_seguridad'][] = "Inconsistencia matemática en créditos: Declarados={$resultado['total_creditos_declarado']}, Suma={$sumaCalculada}.";
                // Discrepancia no fatal registrada en alertas_seguridad
            }
        }

        // D. Verificación de Carrera vs Prefijo de Código de Estudiante
        if (!empty($resultado['codigo_estudiante']) && !empty($resultado['carrera_codigo'])) {
            $prefijosCarrera = [
                'ING-INF' => 'INGINF',
                'ING-MAR' => 'INGMAR',
                'ING-AMB' => 'INGAMB',
                'ADM'     => 'ADM',
                'TUR'     => 'TUR'
            ];
            $prefijoEsp = $prefijosCarrera[$resultado['carrera_codigo']] ?? null;
            if ($prefijoEsp && !str_starts_with(strtoupper($resultado['codigo_estudiante']), $prefijoEsp)) {
                $resultado['alertas_seguridad'][] = "Discrepancia en código de estudiante: Prefijo '{$resultado['codigo_estudiante']}' no corresponde a la carrera {$resultado['carrera_codigo']}.";
            }
        }

        // Evaluación final
        if (!empty($resultado['carrera_codigo'])) $puntos += 10;
        if (!empty($resultado['materias']))       $puntos += 15;
        if (!empty($resultado['estudiante']))     $puntos += 10;

        $resultado['puntuacion_confianza'] = min(100, $puntos);

        if (empty($resultado['carrera_codigo'])) {
            $resultado['motivo_invalidez'] = 'No se pudo identificar una carrera oficial de la UMC en el comprobante adjunto.';
            return $resultado;
        }

        if (empty($resultado['materias'])) {
            $resultado['motivo_invalidez'] = 'El comprobante no registra ninguna materia ni sección inscrita para el período académico.';
            return $resultado;
        }

        if (!empty($carreraEsperada) && $resultado['carrera_codigo'] !== $carreraEsperada) {
            $carreraDoc = $resultado['carrera_nombre'] ?? $resultado['carrera_codigo'];
            $carreraEsp = $this->nombresCarreras[$carreraEsperada] ?? $carreraEsperada;
            $resultado['motivo_invalidez'] = "Inconsistencia de carrera: El comprobante presentado pertenece a '{$carreraDoc}', pero intentas registrar o cambiarte a '{$carreraEsp}'.";
            return $resultado;
        }

        $resultado['es_valido'] = true;
        return $resultado;
    }

    /**
     * Inscribe automáticamente al estudiante en las materias y secciones detectadas
     * vinculándolo con las aulas y horarios correspondientes en la plataforma.
     *
     * @param int         $usuarioId
     * @param array       $materias
     * @param string|null $periodo
     * @param bool        $reemplazarPrevias Si es true (por defecto), limpia inscripciones
     *                                       anteriores del alumno para que el nuevo documento
     *                                       sea el estado definitivo de su carga académica.
     * @return int
     */
    public function inscribirEstudianteEnMaterias(int $usuarioId, array $materias, ?string $periodo = null, bool $reemplazarPrevias = true, ?string $carrera = null): int
    {
        if ($usuarioId <= 0 || empty($materias)) {
            return 0;
        }

        $db = \Config\Database::connect();
        $materiaModel     = new MateriaModel();
        $seccionModel     = new SeccionModel();
        $claseModel       = new ClaseProgramadaModel();

        $periodo = !empty($periodo) ? $periodo : date('Y') . '-01';
        $inscripcionesRealizadas = 0;

        // Si se solicita reemplazar previas (comportamiento oficial al actualizar semestre/carga académica):
        // Se limpian las inscripciones anteriores del alumno y sus bloques personales para evitar acumulación
        if ($reemplazarPrevias) {
            $db->table('inscripciones')
               ->where('estudiante_id', $usuarioId)
               ->delete();

            $db->table('clases_programadas')
               ->where('estudiante_id', $usuarioId)
               ->delete();
        }

        // Horarios estándar para distribuir materias iniciales si no tienen horario programado
        $diasSemanales = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
        $bloquesHoras  = [
            ['inicio' => '08:00:00', 'fin' => '09:30:00', 'aula' => 'Aula 56'],
            ['inicio' => '09:30:00', 'fin' => '11:00:00', 'aula' => 'Aula 57'],
            ['inicio' => '11:00:00', 'fin' => '12:45:00', 'aula' => 'Aula 58'],
            ['inicio' => '13:30:00', 'fin' => '15:00:00', 'aula' => 'Laboratorio de Info 1'],
        ];

        foreach ($materias as $index => $mat) {
            $codigoMateria = trim($mat['codigo']);
            $nombreMateria = trim($mat['nombre']);
            $seccionCodigo = !empty($mat['seccion']) ? trim($mat['seccion']) : 'SVC-A';
            $creditos      = (int) ($mat['creditos'] ?? 3);
            $semestre      = (int) ($mat['semestre'] ?? 1);
            $carreraFinal  = $carrera ?? ($mat['carrera'] ?? (session('carrera') ?: 'ING-INF'));

            // 1. Variantes de código (ej. 'CAL-604' y 'CAL604') para sincronizar con el Horario CGD-001
            $codigoConGuion = preg_replace('/^([A-Z]{3})-?(\d{3})$/i', '$1-$2', $codigoMateria);
            $codigoSinGuion = str_replace(['-', ' '], '', $codigoMateria);
            $codigosBuscar   = array_unique([$codigoMateria, $codigoConGuion, $codigoSinGuion]);

            $materiaExistente = $materiaModel->whereIn('codigo_materia', $codigosBuscar)->first();
            if (!$materiaExistente) {
                $materiaId = $materiaModel->insert([
                    'codigo_materia'    => $codigoConGuion,
                    'nombre'            => $nombreMateria,
                    'unidades_credito'  => $creditos,
                    'semestre_trayecto' => $semestre
                ], true);
            } else {
                $materiaId = $materiaExistente['id'];
                if (!empty($nombreMateria) && strlen($nombreMateria) > strlen($materiaExistente['nombre'])) {
                    $materiaModel->update($materiaId, ['nombre' => $nombreMateria]);
                }
            }

            if (!$materiaId) continue;

            // 2. Normalización de Sección y Período para enlazar con las clases oficiales del mapa
            $letraSeccion = $seccionCodigo;
            if (preg_match('/^SVC-([A-Z])(?:-.*)?$/i', $seccionCodigo, $mSec)) {
                $letraSeccion = strtoupper($mSec[1]);
            }
            $seccionesBuscar = array_unique([$seccionCodigo, $letraSeccion]);

            $periodosBuscar = [$periodo];
            if (preg_match('/^(\d{4})-01$/', $periodo, $mPer)) {
                $periodosBuscar[] = $mPer[1] . '-I';
                $periodosBuscar[] = $mPer[1] . '-1';
            } elseif (preg_match('/^(\d{4})-02$/', $periodo, $mPer)) {
                $periodosBuscar[] = $mPer[1] . '-II';
                $periodosBuscar[] = $mPer[1] . '-2';
            } elseif (preg_match('/^(\d{4})-I$/i', $periodo, $mPer)) {
                $periodosBuscar[] = $mPer[1] . '-01';
            }

            $seccionExistente = $seccionModel->where('materia_id', $materiaId)
                                             ->whereIn('codigo_seccion', $seccionesBuscar)
                                             ->whereIn('periodo_academico', $periodosBuscar)
                                             ->first();

            if (!$seccionExistente) {
                $seccionExistente = $seccionModel->where('materia_id', $materiaId)
                                                 ->whereIn('codigo_seccion', $seccionesBuscar)
                                                 ->first();
            }

            if (!$seccionExistente) {
                $seccionId = $seccionModel->insert([
                    'materia_id'        => $materiaId,
                    'profesor_id'       => null,
                    'codigo_seccion'    => $seccionCodigo,
                    'periodo_academico' => $periodo,
                    'carrera'           => $carreraFinal
                ], true);
            } else {
                $seccionId = $seccionExistente['id'];
                if (empty($seccionExistente['carrera']) && !empty($carreraFinal)) {
                    $seccionModel->update($seccionId, ['carrera' => $carreraFinal]);
                }
            }

            if (!$seccionId) continue;

            // 3. Inscribir al estudiante si no estaba inscrito previamente
            $yaInscrito = $db->table('inscripciones')
                             ->where('estudiante_id', $usuarioId)
                             ->where('seccion_id', $seccionId)
                             ->countAllResults();

            if ($yaInscrito === 0) {
                $db->table('inscripciones')->insert([
                    'estudiante_id' => $usuarioId,
                    'seccion_id'    => $seccionId
                ]);
                $inscripcionesRealizadas++;
            }

            // 4. Solo si NO existe ninguna clase programada para esta sección ni con aula asignada
            $tieneClase = $claseModel->where('seccion_id', $seccionId)->first();
            if (!$tieneClase) {
                $bloque = $bloquesHoras[$index % count($bloquesHoras)];
                $dia    = $diasSemanales[$index % count($diasSemanales)];
                $claseModel->insert([
                    'seccion_id'    => $seccionId,
                    'estudiante_id' => null,
                    'aula'          => $bloque['aula'],
                    'carrera'       => $carreraFinal,
                    'dia_semana'    => $dia,
                    'hora_inicio'   => $bloque['inicio'],
                    'hora_fin'      => $bloque['fin']
                ]);
            }
        }

        return $inscripcionesRealizadas;
    }
}
