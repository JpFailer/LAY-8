<?php

namespace App\Services;

use App\Models\MateriaModel;
use App\Models\SeccionModel;
use App\Models\InscripcionModel;

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
     * Extrae texto puro de un archivo PDF utilizando descompresión zlib
     * y tokenización nativa de operadores de texto de PDF.
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
        preg_match_all('#stream[\r\n]+(.*?)[\r\n]+endstream#s', $rawPdf, $matches);
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

            // Solo procesar streams que contengan bloques de texto (BT ... ET)
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
                        // Un espacio de kerning negativo significativo indica separación de palabras
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
     * Decodifica escapes octales (\332 -> Ú, \315 -> Í, etc.) y caracteres especiales
     */
    protected function decodificarCadenaPdf(string $str): string
    {
        $str = preg_replace_callback('/\\\\([0-7]{1,3})/', function($m) {
            return chr(octdec($m[1]));
        }, $str);

        $str = str_replace(['\\(', '\\)', '\\\\', "\\\n", "\\\r"], ['(', ')', '\\', '', ''], $str);

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
     * 
     * @param string      $rutaPdf          Ruta absoluta al archivo en el servidor
     * @param string|null $carreraEsperada  Código opcional de carrera para validar concordancia (ej: 'ING-INF')
     * @return array
     */
    public function verificar(string $rutaPdf, ?string $carreraEsperada = null): array
    {
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

        // ---------------------------------------------------------------------
        // 1. CHECKS DE SEGURIDAD INSTITUCIONAL Y ANTI-FRAUDE
        // ---------------------------------------------------------------------
        $criterios = [
            'institucion' => (strpos($textNorm, 'maritima del caribe') !== false || strpos($textNorm, 'experimental maritima') !== false),
            'secretaria'  => (strpos($textNorm, 'secretaria general') !== false),
            'registro_est'=> (strpos($textNorm, 'registro estudiantil') !== false),
            'formulario'  => (strpos($textNorm, 'upe-002') !== false || strpos($textNorm, 'reg-seg-cre') !== false),
            'titulo'      => (strpos($textNorm, 'registro de inscripcion') !== false),
            'url_campus'  => (strpos($textNorm, 'campus.umc.edu.ve') !== false || strpos($textNorm, 'inscripcionumc') !== false),
        ];

        $puntos = 0;
        foreach ($criterios as $check => $cumple) {
            if ($cumple) $puntos += 15;
        }

        if ($puntos < 45) {
            $resultado['motivo_invalidez'] = 'El documento suministrado no posee las marcas de agua, membretes ni encabezados institucionales de la Universidad Marítima del Caribe (UMC).';
            return $resultado;
        }

        // ---------------------------------------------------------------------
        // 2. EXTRACCIÓN DE DATOS DEL ALUMNO Y CARRERA
        // ---------------------------------------------------------------------
        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            $lineNorm = $this->normalizarTexto($line);

            // Nombre
            if (preg_match('/Nombre:\s*(.+)$/i', $line, $m)) {
                $resultado['estudiante'] = trim($m[1]);
            }

            // Cédula de Identidad
            if (preg_match('/C[ée]dula\s+Identidad:\s*([VvEe]-\d+)/iu', $line, $m)) {
                $resultado['cedula'] = strtoupper(trim($m[1]));
            }

            // Código del Estudiante
            if (preg_match('/C[oó]digo\s+del\s+Estudiante:\s*([A-Z0-9\-]+)/iu', $line, $m)) {
                $resultado['codigo_estudiante'] = trim($m[1]);
            }

            // Carrera Universitaria
            foreach ($this->mapaCarreras as $frase => $codigoCarrera) {
                if (strpos($lineNorm, $frase) !== false) {
                    $resultado['carrera_codigo'] = $codigoCarrera;
                    $resultado['carrera_nombre'] = $this->nombresCarreras[$codigoCarrera];
                    break;
                }
            }

            // Año y Período
            if (preg_match('/A[ñn]o:\s*(\d{4})/iu', $line, $m)) {
                $resultado['ano'] = trim($m[1]);
            }
            if (preg_match('/Per[íi]odo:\s*(\d{4}-\d{2})/iu', $line, $m)) {
                $resultado['periodo'] = trim($m[1]);
            }

            // Extracción de materias (ej: 06 CAL604 - Cálculo Numérico)
            if (preg_match('/^(\d{2})\s+([A-Z0-9]{4,8})\s*-\s*(.+)$/u', $line, $m)) {
                $semestre      = (int)$m[1];
                $codigoMateria = trim($m[2]);
                $nombreMateria = trim($m[3]);
                $seccion       = '';
                $creditos      = 0.0;

                // La línea siguiente usualmente contiene la sección y créditos (ej: SVC-A 4.0)
                if (isset($lines[$i+1]) && preg_match('/^([A-Z0-9\-]+)\s+(\d+(?:\.\d+)?)$/', trim($lines[$i+1]), $secM)) {
                    $seccion  = trim($secM[1]);
                    $creditos = (float)$secM[2];
                }

                $resultado['materias'][] = [
                    'semestre' => $semestre,
                    'codigo'   => $codigoMateria,
                    'nombre'   => $nombreMateria,
                    'seccion'  => $seccion,
                    'creditos' => $creditos
                ];
            }

            // Total créditos
            if (preg_match('/Total\s+Cr[ée]ditos:\s*(\d+(?:\.\d+)?)/iu', $line, $m)) {
                $resultado['total_creditos'] = (float)$m[1];
            }

            // Código de barras / control institucional
            if (preg_match('/^([0-9A-Z]{10,18})$/', $line, $m)) {
                $resultado['codigo_control'] = $m[1];
            }
        }

        // Puntos adicionales de confianza si se detectaron los componentes clave
        if (!empty($resultado['carrera_codigo'])) $puntos += 15;
        if (!empty($resultado['materias']))       $puntos += 20;
        if (!empty($resultado['codigo_control'])) $puntos += 10;

        $resultado['puntuacion_confianza'] = min(100, $puntos);

        // ---------------------------------------------------------------------
        // 3. VALIDACIÓN DE COHERENCIA Y CONCORDANCIA
        // ---------------------------------------------------------------------
        if (empty($resultado['carrera_codigo'])) {
            $resultado['motivo_invalidez'] = 'No se pudo identificar una carrera oficial de la UMC en el comprobante adjunto.';
            return $resultado;
        }

        if (empty($resultado['materias'])) {
            $resultado['motivo_invalidez'] = 'El comprobante no registra ninguna materia ni sección inscrita para el período académico.';
            return $resultado;
        }

        // Si se solicitó verificar una carrera específica (ej. al cambiar de carrera o registrarse)
        if (!empty($carreraEsperada) && $resultado['carrera_codigo'] !== $carreraEsperada) {
            $carreraDoc = $resultado['carrera_nombre'] ?? $resultado['carrera_codigo'];
            $carreraEsp = $this->nombresCarreras[$carreraEsperada] ?? $carreraEsperada;
            $resultado['motivo_invalidez'] = "Inconsistencia de carrera: El comprobante presentado pertenece a '{$carreraDoc}', pero intentas registrar o cambiarte a '{$carreraEsp}'.";
            return $resultado;
        }

        $resultado['es_valido'] = ($puntos >= 75);
        return $resultado;
    }

    /**
     * Inscribe automáticamente al estudiante en las materias y secciones detectadas
     * vinculándolo con las aulas correspondientes en la plataforma.
     */
    public function inscribirEstudianteEnMaterias(int $usuarioId, array $materias, ?string $periodo = null): int
    {
        if ($usuarioId <= 0 || empty($materias)) {
            return 0;
        }

        $db = \Config\Database::connect();
        $materiaModel     = new MateriaModel();
        $seccionModel     = new SeccionModel();
        $inscripcionModel = new InscripcionModel();

        $periodo = !empty($periodo) ? $periodo : date('Y') . '-01';
        $inscripcionesRealizadas = 0;

        foreach ($materias as $mat) {
            $codigoMateria = $mat['codigo'];
            $nombreMateria = $mat['nombre'];
            $seccionCodigo = !empty($mat['seccion']) ? $mat['seccion'] : 'SVC-A';
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
        }

        return $inscripcionesRealizadas;
    }
}
