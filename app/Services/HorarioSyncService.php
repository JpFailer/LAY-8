<?php

namespace App\Services;

use App\Models\AulasModel;
use App\Models\ClaseProgramadaModel;
use App\Models\MateriaModel;
use App\Models\SeccionModel;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Servicio de dominio para sincronización de posiciones de aulas y horarios académicos (JSON/PDF).
 */
class HorarioSyncService
{
    public const CARRERAS_VALIDAS = ['ING-INF', 'ING-MAR', 'ING-AMB', 'ADM', 'TUR'];

    /**
     * Sincroniza la tabla `aulas` a partir del catálogo en `public/posiciones_aulas.json`.
     */
    public function importarPosicionesAulasJson(): array
    {
        $rutaJson = ROOTPATH . 'public/posiciones_aulas.json';
        if (!file_exists($rutaJson)) {
            return ['ok' => false, 'importadas' => 0, 'actualizadas' => 0, 'error' => 'No se encontró el archivo de posiciones en ' . $rutaJson];
        }

        $aulas = json_decode((string) file_get_contents($rutaJson), true);
        if (!is_array($aulas)) {
            return ['ok' => false, 'importadas' => 0, 'actualizadas' => 0, 'error' => 'No se pudo decodificar el archivo JSON de posiciones.'];
        }

        $aulasModel = new AulasModel();
        $importadas = 0;
        $actualizadas = 0;

        foreach ($aulas as $aula) {
            $nombreJson = (string) ($aula['aula'] ?? '');
            if ($nombreJson === '') {
                continue;
            }
            $partes = explode('(', $nombreJson);
            $aliasPdf = trim($partes[0]);

            $datos = [
                'nombre_json' => $nombreJson,
                'alias_pdf'   => $aliasPdf,
                'coord_x'     => $aula['coord_x'] ?? 0,
                'coord_y'     => $aula['coord_y'] ?? 0,
            ];

            $existente = $aulasModel->where('nombre_json', $nombreJson)->first();
            if ($existente) {
                $aulasModel->update($existente['id'], $datos);
                $actualizadas++;
            } else {
                $aulasModel->insert($datos);
                $importadas++;
            }
        }

        return [
            'ok'           => true,
            'importadas'   => $importadas,
            'actualizadas' => $actualizadas,
            'error'        => null,
        ];
    }

    /**
     * Sincroniza horarios detectando si el archivo subido es JSON (óptimo/producción) o PDF (solo entorno local con Python).
     */
    public function sincronizarDesdeArchivo(UploadedFile $archivo, string $carreraInput): array
    {
        $carrera = in_array($carreraInput, self::CARRERAS_VALIDAS, true) ? $carreraInput : 'ING-INF';

        if (!$archivo->isValid() || $archivo->hasMoved()) {
            return ['ok' => false, 'error' => 'Por favor selecciona un archivo de horario (.json o .pdf) válido.'];
        }

        $ext = strtolower($archivo->getClientExtension() ?: pathinfo($archivo->getClientName(), PATHINFO_EXTENSION));
        if ($ext === 'json') {
            return $this->sincronizarDesdeJson($archivo, $carrera);
        }

        if ($ext === 'pdf' || $archivo->getClientMimeType() === 'application/pdf') {
            $carpetaScripts = ROOTPATH . 'scripts_python';
            $permitePdf = (defined('ENVIRONMENT') && ENVIRONMENT === 'development')
                || (bool) env('ALLOW_SERVER_PDF_PYTHON', false);

            if (!$permitePdf || $this->detectarPythonCamelot($carpetaScripts) === null) {
                return [
                    'ok'    => false,
                    'error' => 'El servidor no procesa archivos PDF directamente. Genera el .json en tu PC con scripts_python/generar_horarios.bat y sube el archivo .json aquí.',
                ];
            }

            return $this->sincronizarDesdePdf($archivo, $carrera);
        }

        return ['ok' => false, 'error' => 'Formato no permitido. Sube el archivo .json generado para la carrera (o .pdf en entorno local).'];
    }

    /**
     * Valida un archivo .json generado localmente con procesar_horario_cgd001.py e importa en lote a MySQL (< 150 ms).
     */
    public function sincronizarDesdeJson(UploadedFile $archivo, string $carreraInput): array
    {
        $carrera = in_array($carreraInput, self::CARRERAS_VALIDAS, true) ? $carreraInput : 'ING-INF';

        $rutaTemp = $archivo->getTempName();
        $contenido = is_file($rutaTemp) ? (string) file_get_contents($rutaTemp) : '';
        $decodificado = json_decode($contenido, true);

        if (!is_array($decodificado)) {
            return ['ok' => false, 'error' => 'El archivo JSON no tiene un formato válido o está corrupto.'];
        }

        $bloquesValidos = $this->filtrarBloquesValidos($decodificado);
        if ($bloquesValidos === []) {
            return ['ok' => false, 'error' => 'El archivo JSON no contiene bloques horarios válidos (codigo_materia, dia, hora_inicio, hora_fin). No se modificó la base de datos.'];
        }

        $carpetaScripts = ROOTPATH . 'scripts_python';
        if (!is_dir($carpetaScripts)) {
            mkdir($carpetaScripts, 0775, true);
        }

        $jsonNormalizado = json_encode($bloquesValidos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $rutaDestinoJson = $carpetaScripts . DIRECTORY_SEPARATOR . "horarios_procesados_{$carrera}.json";
        file_put_contents($rutaDestinoJson, $jsonNormalizado);

        if ($carrera === 'ING-INF') {
            @file_put_contents($carpetaScripts . DIRECTORY_SEPARATOR . 'horarios_procesados.json', $jsonNormalizado);
        }

        $clasesCargadas = $this->insertarBloquesEstructurados($bloquesValidos, $carrera, true);

        return [
            'ok'              => true,
            'modo'            => 'json',
            'carrera'         => $carrera,
            'clases_cargadas' => $clasesCargadas,
            'mensaje'         => "¡Horario de {$carrera} sincronizado desde JSON! Se cargaron {$clasesCargadas} bloques de clases en el mapa.",
            'error'           => null,
        ];
    }

    /**
     * Procesa un PDF oficial de horario por carrera con el extractor Python CGD-001 y re-sincroniza clases.
     */
    public function sincronizarDesdePdf(UploadedFile $archivo, string $carreraInput): array
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $carrera = in_array($carreraInput, self::CARRERAS_VALIDAS, true) ? $carreraInput : 'ING-INF';

        if (!$archivo->isValid() || $archivo->hasMoved()) {
            return ['ok' => false, 'error' => 'Por favor selecciona un archivo PDF de horario oficial válido.'];
        }

        if ($archivo->getClientMimeType() !== 'application/pdf') {
            return ['ok' => false, 'error' => 'El formato del archivo debe ser exclusivamente PDF.'];
        }

        $carpetaScripts = ROOTPATH . 'scripts_python';
        $carpetaHorarios = $carpetaScripts . DIRECTORY_SEPARATOR . 'horarios';
        if (!is_dir($carpetaHorarios)) {
            mkdir($carpetaHorarios, 0777, true);
        }

        $nombreDestino = "horario_{$carrera}.pdf";
        $archivo->move($carpetaHorarios, $nombreDestino, true);
        $rutaCompletaPdf = $carpetaHorarios . DIRECTORY_SEPARATOR . $nombreDestino;

        if ($carrera === 'ING-INF') {
            @copy($rutaCompletaPdf, $carpetaScripts . DIRECTORY_SEPARATOR . 'horario.pdf');
        }

        $rutaCacheJson = $carpetaScripts . DIRECTORY_SEPARATOR . "horarios_procesados_{$carrera}.json";
        if (file_exists($rutaCacheJson)) {
            @unlink($rutaCacheJson);
        }
        if ($carrera === 'ING-INF') {
            $rutaCacheGen = $carpetaScripts . DIRECTORY_SEPARATOR . 'horarios_procesados.json';
            if (file_exists($rutaCacheGen)) {
                @unlink($rutaCacheGen);
            }
        }

        $pythonExec = $this->resolverEjecutablePython($carpetaScripts);
        $comando = 'cd "' . $carpetaScripts . '" && ' . $pythonExec . ' procesar_horario_cgd001.py "' . $rutaCompletaPdf . '" ' . $carrera . ' 2>&1';
        shell_exec($comando);

        $clasesCargadas = 0;
        try {
            $clasesCargadas = $this->importarHorariosAutomaticamente(true, $carrera);
        } catch (\Throwable $e) {
            log_message('error', 'Error al sincronizar clases de ' . $carrera . ': ' . $e->getMessage());
        }

        return [
            'ok'              => true,
            'modo'            => 'pdf',
            'carrera'         => $carrera,
            'clases_cargadas' => $clasesCargadas,
            'mensaje'         => "¡Horario de {$carrera} actualizado con éxito! Se sincronizaron {$clasesCargadas} bloques de clases con horas exactas en el mapa.",
            'error'           => null,
        ];
    }

    /**
     * Carga horarios procesados en caché JSON (o mediante script Python si no hay JSON aún) en `clases_programadas`.
     */
    public function importarHorariosAutomaticamente(bool $limpiarPrevias = false, ?string $carrera = null): int
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        $carpetaScripts = ROOTPATH . 'scripts_python';

        if ($carrera) {
            $jsonDecodificado = null;
            $rutaCacheJson = $carpetaScripts . DIRECTORY_SEPARATOR . "horarios_procesados_{$carrera}.json";
            if (file_exists($rutaCacheJson)) {
                $jsonDecodificado = json_decode((string) file_get_contents($rutaCacheJson), true);
            }

            if (empty($jsonDecodificado)) {
                $rutaPdf = $carpetaScripts . DIRECTORY_SEPARATOR . 'horarios' . DIRECTORY_SEPARATOR . "horario_{$carrera}.pdf";
                if (!file_exists($rutaPdf) && $carrera === 'ING-INF') {
                    $rutaPdf = $carpetaScripts . DIRECTORY_SEPARATOR . 'horario.pdf';
                }

                if (file_exists($rutaPdf)) {
                    $pythonExec = $this->resolverEjecutablePython($carpetaScripts);
                    $comando = 'cd "' . $carpetaScripts . '" && ' . $pythonExec . ' procesar_horario_cgd001.py "' . $rutaPdf . '" ' . $carrera . ' 2>&1';
                    $jsonCrudo = shell_exec($comando);

                    if ($jsonCrudo) {
                        $inicio = strpos($jsonCrudo, '[');
                        $fin = strrpos($jsonCrudo, ']');
                        if ($inicio !== false && $fin !== false) {
                            $jsonDecodificado = json_decode(substr($jsonCrudo, $inicio, $fin - $inicio + 1), true);
                        }
                    }
                }
            }

            if (!empty($jsonDecodificado) && is_array($jsonDecodificado)) {
                return $this->insertarBloquesEstructurados($jsonDecodificado, $carrera, $limpiarPrevias);
            }

            return 0;
        }

        $totalInsertadas = 0;
        $algunaProcesada = false;

        foreach (self::CARRERAS_VALIDAS as $codCarrera) {
            $rutaJson = $carpetaScripts . DIRECTORY_SEPARATOR . "horarios_procesados_{$codCarrera}.json";
            if (file_exists($rutaJson)) {
                $bloques = json_decode((string) file_get_contents($rutaJson), true);
                if (!empty($bloques) && is_array($bloques)) {
                    $totalInsertadas += $this->insertarBloquesEstructurados($bloques, $codCarrera, $limpiarPrevias && !$algunaProcesada);
                    $algunaProcesada = true;
                }
            }
        }

        if (!$algunaProcesada) {
            $rutaCacheGeneral = $carpetaScripts . DIRECTORY_SEPARATOR . 'horarios_procesados.json';
            if (file_exists($rutaCacheGeneral)) {
                $datos = json_decode((string) file_get_contents($rutaCacheGeneral), true);
                if (!empty($datos) && is_array($datos)) {
                    if (isset($datos[0]['codigo_materia'])) {
                        return $this->insertarBloquesEstructurados($datos, 'ING-INF', $limpiarPrevias);
                    }
                    if ($limpiarPrevias) {
                        \Config\Database::connect()->table('clases_programadas')->where('estudiante_id', null)->delete();
                    }
                    return $this->insertarBloquesLegacy($datos);
                }
            }
        }

        return $totalInsertadas;
    }

    /**
     * Filtra un arreglo de bloques JSON conservando solo registros válidos para inserción.
     */
    public function filtrarBloquesValidos(array $bloques): array
    {
        $validos = [];
        foreach ($bloques as $b) {
            if (!is_array($b)) {
                continue;
            }
            $codMateria = trim((string) ($b['codigo_materia'] ?? ''));
            $diaCanon   = HorarioNormalizer::normalizarDia((string) ($b['dia'] ?? ''));
            $horaInicio = HorarioNormalizer::normalizarHora((string) ($b['hora_inicio'] ?? ''));
            $horaFin    = HorarioNormalizer::normalizarHora((string) ($b['hora_fin'] ?? ''));
            if ($codMateria === '' || $codMateria === 'HUERFANO' || $diaCanon === null || $horaInicio === null || $horaFin === null) {
                continue;
            }
            $b['dia']         = $diaCanon;
            $b['hora_inicio'] = $horaInicio;
            $b['hora_fin']    = $horaFin;
            $validos[] = $b;
        }
        return $validos;
    }

    private function resolverEjecutablePython(string $carpetaScripts): string
    {
        $venvWin = $carpetaScripts . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe';
        if (file_exists($venvWin)) {
            return '"' . $venvWin . '"';
        }
        $venvNix = $carpetaScripts . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python';
        if (file_exists($venvNix)) {
            return escapeshellarg($venvNix);
        }
        return DIRECTORY_SEPARATOR === '\\' ? 'python' : 'python3';
    }

    private function detectarPythonCamelot(string $carpetaScripts): ?string
    {
        if (!function_exists('shell_exec')) {
            return null;
        }
        $candidatos = [
            $carpetaScripts . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe',
            $carpetaScripts . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python',
        ];
        $redireccionNul = DIRECTORY_SEPARATOR === '\\' ? '2>nul' : '2>/dev/null';
        foreach ($candidatos as $exe) {
            if (file_exists($exe)) {
                $ok = @shell_exec(escapeshellarg($exe) . ' -c "import camelot; print(\'OK\')" ' . $redireccionNul);
                if (trim((string) $ok) === 'OK') {
                    return $exe;
                }
            }
        }
        foreach (['python', 'python3'] as $cmd) {
            $ok = @shell_exec($cmd . ' -c "import camelot; print(\'OK\')" ' . $redireccionNul);
            if (trim((string) $ok) === 'OK') {
                return $cmd;
            }
        }
        return null;
    }

    /**
     * Inserta bloques horarios en `clases_programadas` de forma atómica (transacción) y por lotes
     * precargando materias y secciones en memoria RAM para evitar N+1 consultas.
     */
    public function insertarBloquesEstructurados(array $bloques, ?string $carrera = null, bool $limpiarPrevias = false): int
    {
        $bloquesValidos = $this->filtrarBloquesValidos($bloques);
        if ($bloquesValidos === []) {
            return 0;
        }

        $db = \Config\Database::connect();
        $materiaModel = new MateriaModel();
        $seccionModel = new SeccionModel();

        $materiasCache = [];
        foreach ($materiaModel->select('id, codigo_materia, nombre')->findAll() as $m) {
            $codRaw   = strtoupper(trim((string) $m['codigo_materia']));
            $codCanon = HorarioNormalizer::codigoMateriaCanonico($codRaw);
            $materiasCache[$codRaw] = $m;
            if (!isset($materiasCache[$codCanon])) {
                $materiasCache[$codCanon] = $m;
            }
        }

        // Clave compuesta con carrera para evitar colisiones entre carreras con mismo código/sección (ej. CAL-114 Secc. A)
        $seccionesCache = [];
        foreach ($seccionModel->select('id, materia_id, codigo_seccion, periodo_academico, carrera')->findAll() as $s) {
            $secRaw   = strtoupper(trim((string) $s['codigo_seccion']));
            $secCanon = HorarioNormalizer::normalizarSeccionCanonica($secRaw);
            $perFam   = HorarioNormalizer::familiaPeriodo((string) $s['periodo_academico']);
            $carSec   = strtoupper(trim((string) ($s['carrera'] ?? '')));
            $clave    = (int) $s['materia_id'] . '|' . $secCanon . '|' . $perFam . '|' . ($carSec !== '' ? $carSec : 'SIN_CARRERA');

            // Preferir filas cuyo codigo_seccion ya coincide con la forma canónica (A vs SVC-A)
            if (!isset($seccionesCache[$clave]) || $secRaw === $secCanon) {
                $seccionesCache[$clave] = $s;
            }
        }

        $loteClases = [];
        $db->transStart();

        if ($limpiarPrevias) {
            $delBuilder = $db->table('clases_programadas')->where('estudiante_id', null);
            if ($carrera) {
                $delBuilder->where('carrera', $carrera);
            }
            $delBuilder->delete();
        }

        foreach ($bloquesValidos as $b) {
            $codMateria    = HorarioNormalizer::codigoMateriaCanonico((string) $b['codigo_materia']);
            $carreraBloque = strtoupper(trim((string) ($b['carrera'] ?? ($carrera ?: 'ING-INF'))));
            $nomMateria    = trim((string) ($b['nombre_materia'] ?? ''));
            $semestre      = (int) ($b['semestre'] ?? 1);
            $seccionCod    = trim((string) ($b['seccion'] ?? 'A'));
            $seccionCanon  = HorarioNormalizer::normalizarSeccionCanonica($seccionCod);
            $periodo       = trim((string) ($b['periodo'] ?? '2026-I'));
            $periodoFam    = HorarioNormalizer::familiaPeriodo($periodo);
            $aula          = trim((string) ($b['aula'] ?? 'Por Asignar'));
            $dia           = trim((string) $b['dia']);
            $horaInicio    = trim((string) $b['hora_inicio']);
            $horaFin       = trim((string) $b['hora_fin']);

            if (!isset($materiasCache[$codMateria])) {
                $materiaId = (int) $materiaModel->insert([
                    'codigo_materia'    => $codMateria,
                    'nombre'            => $nomMateria ?: 'Materia Desconocida',
                    'unidades_credito'  => 0,
                    'semestre_trayecto' => $semestre,
                ]);
                $materiasCache[$codMateria] = [
                    'id'             => $materiaId,
                    'codigo_materia' => $codMateria,
                    'nombre'         => $nomMateria ?: 'Materia Desconocida',
                ];
            } else {
                $materiaId = (int) $materiasCache[$codMateria]['id'];
                if ($nomMateria !== '' && strlen($nomMateria) > strlen((string) $materiasCache[$codMateria]['nombre'])) {
                    $materiaModel->update($materiaId, ['nombre' => $nomMateria]);
                    $materiasCache[$codMateria]['nombre'] = $nomMateria;
                }
            }

            $claveSeccion = $materiaId . '|' . $seccionCanon . '|' . $periodoFam . '|' . $carreraBloque;
            $claveLegacy  = $materiaId . '|' . $seccionCanon . '|' . $periodoFam . '|SIN_CARRERA';

            if (!isset($seccionesCache[$claveSeccion]) && isset($seccionesCache[$claveLegacy])) {
                $seccionesCache[$claveSeccion] = $seccionesCache[$claveLegacy];
                unset($seccionesCache[$claveLegacy]);
            }

            if (!isset($seccionesCache[$claveSeccion])) {
                $seccionId = (int) $seccionModel->insert([
                    'materia_id'        => $materiaId,
                    'codigo_seccion'    => mb_substr($seccionCod, 0, 10, 'UTF-8'),
                    'periodo_academico' => mb_substr($periodo, 0, 20, 'UTF-8'),
                    'carrera'           => $carreraBloque,
                    'profesor_id'       => null,
                ]);
                $seccionesCache[$claveSeccion] = [
                    'id'                => $seccionId,
                    'materia_id'        => $materiaId,
                    'codigo_seccion'    => $seccionCod,
                    'periodo_academico' => $periodo,
                    'carrera'           => $carreraBloque,
                ];
            } else {
                $seccionId = (int) $seccionesCache[$claveSeccion]['id'];
                $datosUpd  = [];
                if (empty($seccionesCache[$claveSeccion]['carrera'])) {
                    $datosUpd['carrera'] = $carreraBloque;
                    $seccionesCache[$claveSeccion]['carrera'] = $carreraBloque;
                }
                if (str_starts_with(strtoupper((string) $seccionesCache[$claveSeccion]['codigo_seccion']), 'SVC-') && $seccionCod !== '') {
                    $datosUpd['codigo_seccion']    = mb_substr($seccionCod, 0, 10, 'UTF-8');
                    $datosUpd['periodo_academico'] = mb_substr($periodo, 0, 20, 'UTF-8');
                    $seccionesCache[$claveSeccion]['codigo_seccion']    = $seccionCod;
                    $seccionesCache[$claveSeccion]['periodo_academico'] = $periodo;
                }
                if ($datosUpd !== []) {
                    $seccionModel->update($seccionId, $datosUpd);
                }
            }

            $loteClases[] = [
                'seccion_id'  => $seccionId,
                'aula'        => $aula,
                'carrera'     => $carreraBloque,
                'dia_semana'  => $dia,
                'hora_inicio' => $horaInicio,
                'hora_fin'    => $horaFin,
            ];
        }

        if ($loteClases !== []) {
            $db->table('clases_programadas')->insertBatch($loteClases, null, 250);
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new \RuntimeException('No se pudo completar la sincronización transaccional de horarios.');
        }

        return count($loteClases);
    }

    private function insertarBloquesLegacy(array $jsonDecodificado): int
    {
        $materiaModel = new MateriaModel();
        $seccionModel = new SeccionModel();
        $claseModel   = new ClaseProgramadaModel();

        $diasSemana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];
        $clasesInsertadas = 0;
        $memoriaDia = [];
        $horaInicioGlobal = '';
        $horaFinGlobal    = '';

        foreach ($jsonDecodificado as $fila) {
            if (!empty($fila['Hora'])) {
                $horasSeparadas = explode("\n", (string) $fila['Hora']);
                $horaInicioGlobal = trim($horasSeparadas[0] ?? '');
                $horaFinGlobal    = trim($horasSeparadas[1] ?? '');
            } elseif ($horaInicioGlobal === '') {
                continue;
            }

            foreach ($diasSemana as $dia) {
                if (empty($fila[$dia])) {
                    continue;
                }

                $lineasLimpias = array_values(array_filter(array_map('trim', explode("\n", (string) $fila[$dia]))));
                $lineasUtiles = [];
                foreach ($lineasLimpias as $linea) {
                    $linea = (string) preg_replace('/[–—−‐‑‒―]/u', '-', $linea);
                    if (preg_match('/^(T|A|R|D|E|N|C|H|0)$/i', $linea) || preg_match('/^SECCI[OÓ]N/i', $linea)) {
                        continue;
                    }
                    $lineasUtiles[] = $linea;
                }

                if ($lineasUtiles === []) {
                    continue;
                }

                $bloquesMaterias = [];
                $indice = -1;

                foreach ($lineasUtiles as $linea) {
                    $aulaAdjunta = '';
                    if (preg_match('/^(.*?)\s+(Aula\s*\d+|Lab\s+(?:F|Ing|Ma).*|Gimnasio\s*\d+|M\s*[IVX]*\s*\d+)$/i', $linea, $matchAula)) {
                        $aulaAdjunta = trim($matchAula[2]);
                        $linea = trim($matchAula[1]);
                    }

                    if ($linea === '' && $aulaAdjunta !== '') {
                        if ($indice >= 0) {
                            $bloquesMaterias[$indice]['aula'] = $aulaAdjunta;
                        }
                        continue;
                    }

                    if (preg_match('/^([A-Z]{3})\s*-?\s*(\d{3})$/i', $linea, $matches)) {
                        $indice++;
                        $bloquesMaterias[$indice] = [
                            'codigo'       => strtoupper($matches[1] . '-' . $matches[2]),
                            'semestre'     => (int) substr($matches[2], 0, 1),
                            'nombre_parts' => [],
                            'aula'         => $aulaAdjunta ?: 'Por Asignar',
                        ];
                    } elseif (preg_match('/^([A-Z]{3})\s*-?\s*(\d{3})\s+(.+)$/i', $linea, $matches)) {
                        $indice++;
                        $bloquesMaterias[$indice] = [
                            'codigo'       => strtoupper($matches[1] . '-' . $matches[2]),
                            'semestre'     => (int) substr($matches[2], 0, 1),
                            'nombre_parts' => [$matches[3]],
                            'aula'         => $aulaAdjunta ?: 'Por Asignar',
                        ];
                    } else {
                        if ($indice === -1) {
                            $indice++;
                            $bloquesMaterias[$indice] = [
                                'codigo'       => 'HUERFANO',
                                'semestre'     => 0,
                                'nombre_parts' => [],
                                'aula'         => 'Por Asignar',
                            ];
                        }

                        if ($aulaAdjunta !== '') {
                            $bloquesMaterias[$indice]['aula'] = $aulaAdjunta;
                        }

                        if (preg_match('/^(Aula\s*\d+|Lab\s+(?:F|Ing|Ma).*|Gimnasio\s*\d+|M\s*[IVX]*\s*\d+|^\d+$|^0$)/i', $linea)) {
                            $bloquesMaterias[$indice]['aula'] = $linea;
                        } else {
                            $bloquesMaterias[$indice]['nombre_parts'][] = $linea;
                        }
                    }
                }

                foreach ($bloquesMaterias as $bloque) {
                    $nombreMateria = empty($bloque['nombre_parts']) ? '' : implode(' ', $bloque['nombre_parts']);
                    $aulaClase     = $bloque['aula'];
                    $codigoMateria = $bloque['codigo'];
                    $semestre      = $bloque['semestre'];

                    if ($codigoMateria === 'HUERFANO') {
                        if (isset($memoriaDia[$dia])) {
                            $idMateriaVieja = $memoriaDia[$dia]['materia_id'];
                            $materiaDb = $materiaModel->find($idMateriaVieja);

                            if ($nombreMateria !== '' && $materiaDb) {
                                $nombreActual = (string) $materiaDb['nombre'];
                                similar_text(strtolower(trim($nombreActual)), strtolower(trim($nombreMateria)), $similitud);
                                if ($similitud < 75 && stripos($nombreActual, $nombreMateria) === false) {
                                    $nuevoNombre = $nombreActual === 'Materia Desconocida'
                                        ? $nombreMateria
                                        : trim($nombreActual . ' ' . $nombreMateria);
                                    $materiaModel->update($idMateriaVieja, ['nombre' => $nuevoNombre]);
                                }
                            }

                            if ($aulaClase !== 'Por Asignar') {
                                $claseModel->update($memoriaDia[$dia]['clase_id'], [
                                    'aula'     => $aulaClase,
                                    'hora_fin' => $horaFinGlobal,
                                ]);
                            }
                        }
                        continue;
                    }

                    $materiaDb = $materiaModel->where('codigo_materia', $codigoMateria)->first();
                    if (!$materiaDb) {
                        $materiaId = $materiaModel->insert([
                            'codigo_materia'    => $codigoMateria,
                            'nombre'            => $nombreMateria ?: 'Materia Desconocida',
                            'unidades_credito'  => 0,
                            'semestre_trayecto' => $semestre,
                        ]);
                    } else {
                        $materiaId = $materiaDb['id'];
                        if ($nombreMateria !== '' && strlen($nombreMateria) > strlen((string) $materiaDb['nombre'])) {
                            $materiaModel->update($materiaId, ['nombre' => $nombreMateria]);
                        }
                    }

                    $codigoSeccionRaw = isset($fila['Seccion']) ? trim((string) $fila['Seccion']) : '';
                    $codigoSeccion    = $codigoSeccionRaw !== '' ? mb_substr($codigoSeccionRaw, 0, 10, 'UTF-8') : 'N/D';
                    $periodoRaw       = isset($fila['Periodo']) ? trim((string) $fila['Periodo']) : '';
                    $periodoAcademico = $periodoRaw !== '' ? mb_substr($periodoRaw, 0, 20, 'UTF-8') : 'N/D';

                    $seccionDb = $seccionModel->where('materia_id', $materiaId)
                        ->where('codigo_seccion', $codigoSeccion)
                        ->where('periodo_academico', $periodoAcademico)
                        ->first();

                    if (!$seccionDb) {
                        $seccionId = $seccionModel->insert([
                            'materia_id'        => $materiaId,
                            'codigo_seccion'    => $codigoSeccion,
                            'periodo_academico' => $periodoAcademico,
                            'profesor_id'       => null,
                        ]);
                    } else {
                        $seccionId = $seccionDb['id'];
                    }

                    $claseId = $claseModel->insert([
                        'seccion_id'  => $seccionId,
                        'aula'        => $aulaClase,
                        'dia_semana'  => $dia,
                        'hora_inicio' => $horaInicioGlobal,
                        'hora_fin'    => $horaFinGlobal,
                    ]);

                    $memoriaDia[$dia] = ['materia_id' => $materiaId, 'clase_id' => $claseId];
                    $clasesInsertadas++;
                }
            }
        }

        return $clasesInsertadas;
    }
}
