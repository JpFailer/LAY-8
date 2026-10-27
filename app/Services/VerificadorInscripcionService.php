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
        'maritima'                       => 'ING-MAR',
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
        $pythonExes = [
            ROOTPATH . 'scripts_python/venv/Scripts/python.exe',
            ROOTPATH . 'scripts_python/venv/bin/python',
            'python',
            'python3'
        ];

        $pythonExecutable = null;
        foreach ($pythonExes as $exe) {
            if (file_exists($exe) || in_array($exe, ['python', 'python3'], true)) {
                $pythonExecutable = $exe;
                break;
            }
        }

        if (!$pythonExecutable) {
            return null;
        }

        $scriptPath = ROOTPATH . 'scripts_python/verificar_inscripcion.py';
        if (!file_exists($scriptPath)) {
            return null;
        }

        $cmd = escapeshellarg($pythonExecutable) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($rutaPdf);
        $output = @shell_exec($cmd);
        if (empty($output)) {
            return null;
        }

        $datos = json_decode(trim($output), true);
        return is_array($datos) ? $datos : null;
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

        $rawPdf = file_get_contents($rutaPdf);
        if ($rawPdf === false || strlen($rawPdf) < 100) {
            return [];
        }

        // Buscar todos los streams binarios del PDF
        preg_match_all('#stream[
]+(.*?)[
]+endstream#s', $rawPdf, $matches);
        if (empty($matches[1])) {
            return [];
        }

        $allLines = [];

        foreach ($matches[1] as $stream) {
            // Descomprimir stream usando zlib o inflate
            $decompressed = @gzuncompress($stream);
            if ($decompressed === false) {
                $decompressed = @gzinflate(substr($stream, 2));
                if ($decompressed === false) {
                    $decompressed = $stream;
                }
            }

            // Procesar streams con bloques de texto (BT ... ET)
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
        $str = preg_replace_callback('/\\([0-7]{1,3})/', function($m) {
            return chr(octdec($m[1]));
        }, $str);

        $str = str_replace(['\\(', '\\)', '\\\\', "\\n", "\\r"], ['(', ')', '\\', '', ''], $str);

        if (!mb_check_encoding($str, 'UTF-8')) {
            $str = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
        }

        return $str;
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
            'es_valido'            => false,
            'motivo_invalidez'     => null,
            'puntuacion_confianza' => 0,
            'estudiante'           => null,
            'cedula'               => null,
            'codigo_estudiante'    => null,
            'carrera_codigo'       => null,
            'carrera_nombre'       => null,
            'ano'                  => null,
            'periodo'              => null,
            'total_creditos'       => 0.0,
            'codigo_control'       => null,
            'materias'             => [],
            'lineas_extraidas'     => 0,
        ];

        $lines = $this->extraerLineasDePdf($rutaPdf);
        $resultado['lineas_extraidas'] = count($lines);

        if (empty($lines)) {
            $resultado['motivo_invalidez'] = 'El archivo no contiene capas de texto legibles o es una imagen escaneada no vectorizada.';
            return $resultado;
        }

        $fullText = implode("\n", $lines);
        $textNorm = $this->normalizarTexto($fullText);

        // Criterios de Seguridad Institucional UMC
        $criterios = [
            'institucion' => (strpos($textNorm, 'maritima del caribe') !== false || strpos($textNorm, 'experimental maritima') !== false),
            'secretaria'  => (strpos($textNorm, 'secretaria general') !== false),
            'registro_est'=> (strpos($textNorm, 'registro estudiantil') !== false || strpos($textNorm, 'reg-seg-cre') !== false),
            'formulario'  => (strpos($textNorm, 'upe-002') !== false || strpos($textNorm, 'forma 006 ree') !== false),
            'titulo'      => (strpos($textNorm, 'registro de inscripcion') !== false || strpos($textNorm, 'tramites de inscripcion') !== false),
            'republica'   => (strpos($textNorm, 'republica bolivariana') !== false),
        ];

        $puntos = 0;
        foreach ($criterios as $check => $cumple) {
            if ($cumple) $puntos += 15;
        }

        if ($puntos < 45) {
            $resultado['motivo_invalidez'] = 'El documento suministrado no posee las marcas de agua, membretes ni encabezados institucionales de la Universidad Marítima del Caribe (UMC).';
            return $resultado;
        }

        // Extracción de datos con patrones flexibles
        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            $lineNorm = $this->normalizarTexto($line);

            if (preg_match('/Bachiller:\s*(.*?)(?:\s+Estudiante\s+Nro\.:|portador|\n|$)/iu', $line, $m)) {
                $resultado['estudiante'] = trim($m[1]);
            }

            if (preg_match('/(?:c[ée]dula|identidad)\s*[:\.]?\s*([VvEe]\s*-\s*\d+)/iu', $line, $m)) {
                $resultado['cedula'] = strtoupper(str_replace(' ', '', $m[1]));
            }

            if (preg_match('/Estudiante\s+Nro\.?:\s*([A-Za-z0-9\-]+)/iu', $line, $m)) {
                $resultado['codigo_estudiante'] = trim($m[1]);
            }

            foreach ($this->mapaCarreras as $frase => $codigoCarrera) {
                if (strpos($lineNorm, $frase) !== false) {
                    $resultado['carrera_codigo'] = $codigoCarrera;
                    $resultado['carrera_nombre'] = $this->nombresCarreras[$codigoCarrera];
                    break;
                }
            }

            if (preg_match('/del\s+a[ñn]o\s*(\d{4})/iu', $line, $m)) {
                $resultado['ano'] = trim($m[1]);
            }
            if (preg_match('/per[íi]odo(?:\s+acad[ée]mico)?\s*[:\.]?\s*(\d{4}-\d{2})/iu', $line, $m)) {
                $resultado['periodo'] = trim($m[1]);
            }

            // Materias en formato fila completa
            if (preg_match('/(\d{4}\s*-\s*\d{4}-\d{2})\s+(\d{1,2})\s+([A-Z0-9\-]+)\s+([A-Z0-9]{4,8})\s*-\s*(.+?)\s+(?:E\s*N\s+CURSO|APROBADA|INSCRITA)/iu', $line, $m)) {
                $resultado['materias'][] = [
                    'semestre' => (int)$m[2],
                    'codigo'   => trim($m[4]),
                    'nombre'   => trim($m[5]),
                    'seccion'  => trim($m[3]),
                    'creditos' => 3.0
                ];
            }
        }

        if (!empty($resultado['carrera_codigo'])) $puntos += 15;
        if (!empty($resultado['materias']))       $puntos += 25;

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
     */
    public function inscribirEstudianteEnMaterias(int $usuarioId, array $materias, ?string $periodo = null): int
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

        // Horarios estándar para distribuir materias iniciales si no tienen horario programado
        $diasSemanales = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
        $bloquesHoras  = [
            ['inicio' => '08:00:00', 'fin' => '09:30:00', 'aula' => 'Aula 56 (Primer piso)'],
            ['inicio' => '09:30:00', 'fin' => '11:00:00', 'aula' => 'Aula 57 (Primer piso)'],
            ['inicio' => '11:00:00', 'fin' => '12:45:00', 'aula' => 'Aula 58 (Primer piso)'],
            ['inicio' => '13:30:00', 'fin' => '15:00:00', 'aula' => 'Laboratorio de Info 1 (Segundo piso)'],
        ];

        foreach ($materias as $index => $mat) {
            $codigoMateria = trim($mat['codigo']);
            $nombreMateria = trim($mat['nombre']);
            $seccionCodigo = !empty($mat['seccion']) ? trim($mat['seccion']) : 'SVC-A';
            $creditos      = (int) ($mat['creditos'] ?? 3);
            $semestre      = (int) ($mat['semestre'] ?? 1);

            // 1. Asegurar existencia de la materia
            $materiaExistente = $materiaModel->where('codigo_materia', $codigoMateria)->first();
            if (!$materiaExistente) {
                $materiaId = $materiaModel->insert([
                    'codigo_materia'    => $codigoMateria,
                    'nombre'            => $nombreMateria,
                    'unidades_credito'  => $creditos,
                    'semestre_trayecto' => $semestre
                ], true);
            } else {
                $materiaId = $materiaExistente['id'];
            }

            if (!$materiaId) continue;

            // 2. Asegurar existencia de la sección para esa materia
            $seccionExistente = $seccionModel->where('materia_id', $materiaId)
                                             ->where('codigo_seccion', $seccionCodigo)
                                             ->first();
            if (!$seccionExistente) {
                $seccionId = $seccionModel->insert([
                    'materia_id'        => $materiaId,
                    'profesor_id'       => null,
                    'codigo_seccion'    => $seccionCodigo,
                    'periodo_academico' => $periodo
                ], true);
            } else {
                $seccionId = $seccionExistente['id'];
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

            // 4. Asegurar que exista al menos una clase programada con aula para ver en mapa
            $tieneClase = $claseModel->where('seccion_id', $seccionId)->first();
            if (!$tieneClase) {
                $bloque = $bloquesHoras[$index % count($bloquesHoras)];
                $dia    = $diasSemanales[$index % count($diasSemanales)];
                $claseModel->insert([
                    'seccion_id'    => $seccionId,
                    'estudiante_id' => null,
                    'aula'          => $bloque['aula'],
                    'dia_semana'    => $dia,
                    'hora_inicio'   => $bloque['inicio'],
                    'hora_fin'      => $bloque['fin']
                ]);
            }
        }

        return $inscripcionesRealizadas;
    }
}
