<?php
namespace App\Controllers;

use App\Models\MateriaModel;
use App\Models\SeccionModel;
use App\Models\ClaseProgramadaModel;
use App\Models\AulasModel;

class MapaController extends BaseController {

    // 1. CARGA AUTOMÁTICA DE HORARIOS A LA BASE DE DATOS
    public function importar_horarios_automaticamente(bool $limpiarPrevias = false, ?string $carrera = null): int {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        $carpeta_scripts = ROOTPATH . 'scripts_python';
        $json_decodificado = null;

        // Si se especificó una carrera concreta (ING-INF, ING-MAR, etc.)
        if ($carrera) {
            $rutaCacheJson = $carpeta_scripts . DIRECTORY_SEPARATOR . "horarios_procesados_{$carrera}.json";
            if (file_exists($rutaCacheJson)) {
                $contenido = file_get_contents($rutaCacheJson);
                $json_decodificado = json_decode($contenido, true);
            }

            // Si no existe el JSON procesado en disco, ejecutar el script Python CGD-001
            if (empty($json_decodificado)) {
                $rutaPdf = $carpeta_scripts . DIRECTORY_SEPARATOR . 'horarios' . DIRECTORY_SEPARATOR . "horario_{$carrera}.pdf";
                if (!file_exists($rutaPdf) && $carrera === 'ING-INF') {
                    $rutaPdf = $carpeta_scripts . DIRECTORY_SEPARATOR . 'horario.pdf';
                }

                if (file_exists($rutaPdf)) {
                    $pythonExec = file_exists($carpeta_scripts . DIRECTORY_SEPARATOR . 'venv/Scripts/python.exe')
                        ? '"venv\\Scripts\\python.exe"'
                        : "python";
                    $comando = 'cd "' . $carpeta_scripts . '" && ' . $pythonExec . ' procesar_horario_cgd001.py "' . $rutaPdf . '" ' . $carrera . ' 2>&1';
                    $json_crudo = shell_exec($comando);

                    if ($json_crudo) {
                        $inicio = strpos($json_crudo, '[');
                        $fin = strrpos($json_crudo, ']');
                        if ($inicio !== false && $fin !== false) {
                            $json_limpio = substr($json_crudo, $inicio, $fin - $inicio + 1);
                            $json_decodificado = json_decode($json_limpio, true);
                        }
                    }
                }
            }

            if (!empty($json_decodificado) && is_array($json_decodificado)) {
                return $this->insertar_bloques_estructurados($json_decodificado, $carrera, $limpiarPrevias);
            }

            return 0;
        }

        // Si no se especificó carrera, sincronizar todas las carreras que tengan JSON o PDF
        $carreras = ['ING-INF', 'ING-MAR', 'ING-AMB', 'ADM', 'TUR'];
        $totalInsertadas = 0;
        $algunaProcesada = false;

        foreach ($carreras as $codCarrera) {
            $rutaJson = $carpeta_scripts . DIRECTORY_SEPARATOR . "horarios_procesados_{$codCarrera}.json";
            if (file_exists($rutaJson)) {
                $bloques = json_decode(file_get_contents($rutaJson), true);
                if (!empty($bloques) && is_array($bloques)) {
                    $totalInsertadas += $this->insertar_bloques_estructurados($bloques, $codCarrera, $limpiarPrevias && !$algunaProcesada);
                    $algunaProcesada = true;
                }
            }
        }

        // Si no había ningún JSON por carrera, probar el JSON general de respaldo
        if (!$algunaProcesada) {
            $rutaCacheGeneral = $carpeta_scripts . DIRECTORY_SEPARATOR . 'horarios_procesados.json';
            if (file_exists($rutaCacheGeneral)) {
                $datos = json_decode(file_get_contents($rutaCacheGeneral), true);
                if (!empty($datos) && is_array($datos)) {
                    if (isset($datos[0]['codigo_materia'])) {
                        return $this->insertar_bloques_estructurados($datos, 'ING-INF', $limpiarPrevias);
                    } else {
                        if ($limpiarPrevias) {
                            \Config\Database::connect()->table('clases_programadas')->emptyTable();
                        }
                        return $this->insertar_bloques_horarios($datos);
                    }
                }
            }
        }

        return $totalInsertadas;
    }

    // Inserción directa y atómica de bloques estructurados con horas exactas
    private function insertar_bloques_estructurados(array $bloques, ?string $carrera = null, bool $limpiarPrevias = false): int {
        $db = \Config\Database::connect();
        $materiaModel = new MateriaModel();
        $seccionModel = new SeccionModel();
        $claseModel   = new ClaseProgramadaModel();

        if ($limpiarPrevias) {
            if ($carrera) {
                $db->table('clases_programadas')->where('carrera', $carrera)->delete();
            } else {
                $db->table('clases_programadas')->emptyTable();
            }
        }

        $clases_insertadas = 0;

        foreach ($bloques as $b) {
            $codMateria = trim($b['codigo_materia'] ?? '');
            if (empty($codMateria) || $codMateria === 'HUERFANO') continue;

            $carreraBloque = $b['carrera'] ?? ($carrera ?: 'ING-INF');
            $nomMateria    = trim($b['nombre_materia'] ?? '');
            $semestre      = (int)($b['semestre'] ?? 1);
            $seccionCod    = trim($b['seccion'] ?? 'A');
            $periodo       = trim($b['periodo'] ?? '2026-I');
            $aula          = trim($b['aula'] ?? 'Por Asignar');
            $dia           = trim($b['dia'] ?? '');
            $horaInicio    = trim($b['hora_inicio'] ?? '');
            $horaFin       = trim($b['hora_fin'] ?? '');

            if (empty($dia) || empty($horaInicio) || empty($horaFin)) continue;

            // 1. Materia
            $materia_db = $materiaModel->where('codigo_materia', $codMateria)->first();
            if (!$materia_db) {
                $materia_id = $materiaModel->insert([
                    'codigo_materia'    => $codMateria,
                    'nombre'            => $nomMateria ?: 'Materia Desconocida',
                    'unidades_credito'  => 0,
                    'semestre_trayecto' => $semestre
                ]);
            } else {
                $materia_id = $materia_db['id'];
                if (!empty($nomMateria) && strlen($nomMateria) > strlen($materia_db['nombre'])) {
                    $materiaModel->update($materia_id, ['nombre' => $nomMateria]);
                }
            }

            // 2. Sección
            $seccion_db = $seccionModel->where('materia_id', $materia_id)
                                       ->where('codigo_seccion', $seccionCod)
                                       ->where('periodo_academico', $periodo)
                                       ->first();

            if (!$seccion_db) {
                $seccion_id = $seccionModel->insert([
                    'materia_id'        => $materia_id,
                    'codigo_seccion'    => $seccionCod,
                    'periodo_academico' => $periodo,
                    'carrera'           => $carreraBloque,
                    'profesor_id'       => null
                ]);
            } else {
                $seccion_id = $seccion_db['id'];
                if (empty($seccion_db['carrera']) || $seccion_db['carrera'] !== $carreraBloque) {
                    $seccionModel->update($seccion_id, ['carrera' => $carreraBloque]);
                }
            }

            // 3. Clase Programada con inicio y fin exactos
            $claseModel->insert([
                'seccion_id'  => $seccion_id,
                'aula'        => $aula,
                'carrera'     => $carreraBloque,
                'dia_semana'  => $dia,
                'hora_inicio' => $horaInicio,
                'hora_fin'    => $horaFin
            ]);

            $clases_insertadas++;
        }

        return $clases_insertadas;
    }

    // Ruta de prueba manual para forzar la lectura por Python (/htest)
    public function python_horarios(){
        $carpeta_scripts = ROOTPATH . 'scripts_python';
        $pythonExec = file_exists($carpeta_scripts . DIRECTORY_SEPARATOR . "venv\\Scripts\\python.exe") 
            ? "\"venv\\Scripts\\python.exe\"" 
            : "python";
        $comando = 'cd "' . $carpeta_scripts . '" && ' . $pythonExec . ' leer_horario.py 2>&1';
        
        $json_crudo = shell_exec($comando);

        $inicio = strpos($json_crudo, '[');
        $fin = strrpos($json_crudo, ']');

        if ($inicio !== false && $fin !== false) {
            $json_limpio = substr($json_crudo, $inicio, $fin - $inicio + 1);
            $json_decodificado = json_decode($json_limpio, true);

            if ($json_decodificado === null) {
                return "Sigue fallando. Motivo: " . json_last_error_msg();
            }

            $clases_insertadas = $this->insertar_bloques_horarios($json_decodificado);
            return "¡Proceso Completado con éxito! Se procesaron e insertaron un total de {$clases_insertadas} bloques de clases en el mapa.";
        }

        return "Respuesta de Python: " . $json_crudo;
    }

    // Método de respaldo para insertar materias, secciones y clases desde JSON legacy
    private function insertar_bloques_horarios(array $json_decodificado): int {
        $materiaModel = new MateriaModel();
        $seccionModel = new SeccionModel();
        $claseModel   = new ClaseProgramadaModel();

        $dias_semana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];
        $clases_insertadas = 0;
        $memoria_dia = [];

        $hora_inicio_global = '';
        $hora_fin_global    = '';

        foreach ($json_decodificado as $fila) {
            if (!empty($fila['Hora'])) {
                $horas_separadas = explode("\n", $fila['Hora']);
                $hora_inicio_global = trim($horas_separadas[0] ?? ''); 
                $hora_fin_global    = trim($horas_separadas[1] ?? '');
            } else {
                if (empty($hora_inicio_global)) continue;
            }

            $hora_inicio = $hora_inicio_global;
            $hora_fin    = $hora_fin_global;

            foreach ($dias_semana as $dia) {
                if (!empty($fila[$dia])) {
                    $lineas = explode("\n", $fila[$dia]);
                    $lineas_limpias = array_values(array_filter(array_map('trim', $lineas)));
                    
                    $lineas_utiles = [];
                    foreach ($lineas_limpias as $linea) {
                        $linea = preg_replace('/[–—−‐‑‒―]/u', '-', $linea);
                        if (preg_match('/^(T|A|R|D|E|N|C|H|0)$/i', $linea)) continue;
                        if (preg_match('/^SECCI[OÓ]N/i', $linea)) continue;
                        $lineas_utiles[] = $linea;
                    }

                    if (count($lineas_utiles) === 0) continue; 

                    $bloques_materias = [];
                    $indice = -1;

                    foreach ($lineas_utiles as $linea) {
                        $aula_adjunta = '';
                        if (preg_match('/^(.*?)\s+(Aula\s*\d+|Lab\s+(?:F|Ing|Ma).*|Gimnasio\s*\d+|M\s*[IVX]*\s*\d+)$/i', $linea, $match_aula)) {
                            $aula_adjunta = trim($match_aula[2]);
                            $linea = trim($match_aula[1]); 
                        }

                        if (empty($linea) && !empty($aula_adjunta)) {
                            if ($indice >= 0) $bloques_materias[$indice]['aula'] = $aula_adjunta;
                            continue;
                        }

                        if (preg_match('/^([A-Z]{3})\s*-?\s*(\d{3})$/i', $linea, $matches)) {
                            $indice++;
                            $bloques_materias[$indice] = [
                                'codigo' => strtoupper($matches[1] . '-' . $matches[2]),
                                'semestre' => (int) substr($matches[2], 0, 1),
                                'nombre_parts' => [],
                                'aula' => $aula_adjunta ?: 'Por Asignar'
                            ];
                        }
                        elseif (preg_match('/^([A-Z]{3})\s*-?\s*(\d{3})\s+(.+)$/i', $linea, $matches)) {
                            $indice++;
                            $bloques_materias[$indice] = [
                                'codigo' => strtoupper($matches[1] . '-' . $matches[2]),
                                'semestre' => (int) substr($matches[2], 0, 1),
                                'nombre_parts' => [$matches[3]],
                                'aula' => $aula_adjunta ?: 'Por Asignar'
                            ];
                        }
                        else {
                            if ($indice === -1) {
                                $indice++;
                                $bloques_materias[$indice] = [
                                    'codigo' => 'HUERFANO',
                                    'semestre' => 0,
                                    'nombre_parts' => [],
                                    'aula' => 'Por Asignar'
                                ];
                            }

                            if ($aula_adjunta) {
                                $bloques_materias[$indice]['aula'] = $aula_adjunta;
                            }

                            if (preg_match('/^(Aula\s*\d+|Lab\s+(?:F|Ing|Ma).*|Gimnasio\s*\d+|M\s*[IVX]*\s*\d+|^\d+$|^0$)/i', $linea)) {
                                $bloques_materias[$indice]['aula'] = $linea;
                            } else {
                                $bloques_materias[$indice]['nombre_parts'][] = $linea;
                            }
                        }
                    }

                    foreach ($bloques_materias as $bloque) {
                        $nombre_materia = empty($bloque['nombre_parts']) ? '' : implode(" ", $bloque['nombre_parts']);
                        $aula_clase     = $bloque['aula'];
                        $codigo_materia = $bloque['codigo'];
                        $semestre       = $bloque['semestre'];

                        if ($codigo_materia === 'HUERFANO') {
                            if (isset($memoria_dia[$dia])) {
                                $id_materia_vieja = $memoria_dia[$dia]['materia_id'];
                                $materia_db = $materiaModel->find($id_materia_vieja);
                                
                                if (!empty($nombre_materia) && $materia_db) {
                                    $nombre_actual = $materia_db['nombre'];
                                    similar_text(strtolower(trim($nombre_actual)), strtolower(trim($nombre_materia)), $similitud);
                                    
                                    if ($similitud < 75 && stripos($nombre_actual, $nombre_materia) === false) {
                                        if ($nombre_actual === 'Materia Desconocida') {
                                            $nuevo_nombre = $nombre_materia;
                                        } else {
                                            $nuevo_nombre = trim($nombre_actual . ' ' . $nombre_materia);
                                        }
                                        $materiaModel->update($id_materia_vieja, ['nombre' => $nuevo_nombre]);
                                    }
                                }

                                if ($aula_clase !== 'Por Asignar') {
                                    $claseModel->update($memoria_dia[$dia]['clase_id'], [
                                        'aula'     => $aula_clase,
                                        'hora_fin' => $hora_fin 
                                    ]);
                                }
                            }
                            continue; 
                        }

                        $materia_db = $materiaModel->where('codigo_materia', $codigo_materia)->first();
                        
                        if (!$materia_db) {
                            $materia_id = $materiaModel->insert([
                                'codigo_materia'    => $codigo_materia,
                                'nombre'            => $nombre_materia ?: 'Materia Desconocida',
                                'unidades_credito'  => 0, 
                                'semestre_trayecto' => $semestre 
                            ]);
                        } else {
                            $materia_id = $materia_db['id'];
                            if (!empty($nombre_materia) && strlen($nombre_materia) > strlen($materia_db['nombre'])) {
                                $materiaModel->update($materia_id, ['nombre' => $nombre_materia]);
                            }
                        }

                        $codigo_seccion    = isset($fila['Seccion']) ? trim($fila['Seccion']) : 'Desconocida';
                        $periodo_academico = isset($fila['Periodo']) ? trim($fila['Periodo']) : 'Desconocido';

                        $seccion_db = $seccionModel->where('materia_id', $materia_id)
                                                   ->where('codigo_seccion', $codigo_seccion)
                                                   ->where('periodo_academico', $periodo_academico)
                                                   ->first();

                        if (!$seccion_db) {
                            $seccion_id = $seccionModel->insert([
                                'materia_id'        => $materia_id,
                                'codigo_seccion'    => $codigo_seccion,
                                'periodo_academico' => $periodo_academico,
                                'profesor_id'       => null     
                            ]);
                        } else {
                            $seccion_id = $seccion_db['id'];
                        }
                        
                        $clase_id = $claseModel->insert([
                            'seccion_id'  => $seccion_id,
                            'aula'        => $aula_clase,
                            'dia_semana'  => $dia,
                            'hora_inicio' => $hora_inicio,
                            'hora_fin'    => $hora_fin
                        ]);

                        $memoria_dia[$dia] = [
                            'materia_id' => $materia_id,
                            'clase_id'   => $clase_id
                        ];

                        $clases_insertadas++;
                    }
                }
            }
        }

        return $clases_insertadas;
    }

    // Helper: Información y logo de cada carrera institucional UMC
    public static function obtenerInfoCarrera(?string $codigo): array {
        $carreras = [
            'ING-INF' => ['nombre' => 'Ingeniería en Informática', 'icono' => 'bx-code-alt', 'color' => '#2563eb'],
            'ING-MAR' => ['nombre' => 'Ingeniería Marítima', 'icono' => 'bx-anchor', 'color' => '#0284c7'],
            'ING-AMB' => ['nombre' => 'Ingeniería Ambiental', 'icono' => 'bx-leaf', 'color' => '#10b981'],
            'ADM'     => ['nombre' => 'Licenciatura en Administración', 'icono' => 'bx-briefcase-alt-2', 'color' => '#f59e0b'],
            'TUR'     => ['nombre' => 'Licenciatura en Turismo', 'icono' => 'bx-compass', 'color' => '#ec4899'],
        ];

        $cod = strtoupper(trim((string)$codigo));
        if (isset($carreras[$cod])) {
            return $carreras[$cod];
        }

        if (str_contains($cod, 'INF')) return $carreras['ING-INF'];
        if (str_contains($cod, 'MAR') || str_contains($cod, 'NAUT')) return $carreras['ING-MAR'];
        if (str_contains($cod, 'AMB')) return $carreras['ING-AMB'];
        if (str_contains($cod, 'ADM')) return $carreras['ADM'];
        if (str_contains($cod, 'TUR')) return $carreras['TUR'];

        return ['nombre' => 'Carrera UMC', 'icono' => 'bx-book-open', 'color' => '#64748b'];
    }

    // Helper: Calcula el estado dinámico del aula (ocupada con logo de carrera en rojo, libre en verde, etc.)
    public static function calcularEstadoAula(array $aula, $clasesModel, string $dia_actual, string $hora_actual, string $hora_mas_15_min): array {
        $alias = $aula['alias_pdf'];

        // 1. Fuera de servicio o mantenimiento
        $condicion = $aula['condicion'] ?? '';
        $estadoDb  = $aula['estado'] ?? '';
        if ($estadoDb === 'mantenimiento' || $estadoDb === 'inhabilitada' || $condicion === 'fuera_de_servicio') {
            return [
                'estado'         => 'fuera_de_servicio',
                'color'          => 'azul',
                'carrera'        => null,
                'carrera_nombre' => null,
                'icono'          => 'bx-wrench',
                'texto_estado'   => 'Fuera de servicio',
                'materia'        => null,
                'seccion'        => null,
                'intervalo'      => null,
                'esta_ocupado'   => false,
            ];
        }

        // 2. Clase activa en este momento
        $clase_activa = $clasesModel
            ->select('clases_programadas.*, materias.nombre as materia, materias.codigo_materia, secciones.codigo_seccion')
            ->join('secciones', 'secciones.id = clases_programadas.seccion_id')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->where('clases_programadas.aula', $alias)
            ->where('clases_programadas.dia_semana', $dia_actual)
            ->where('clases_programadas.hora_inicio <=', $hora_actual)
            ->where('clases_programadas.hora_fin >=', $hora_actual)
            ->first();

        if ($clase_activa) {
            $codCarrera = $clase_activa['carrera'] ?: 'ING-INF';
            $info = self::obtenerInfoCarrera($codCarrera);
            return [
                'estado'         => 'ocupada',
                'color'          => 'roja',
                'carrera'        => $codCarrera,
                'carrera_nombre' => $info['nombre'],
                'icono'          => $info['icono'],
                'texto_estado'   => 'En clase (' . $info['nombre'] . ')',
                'materia'        => $clase_activa['materia'],
                'seccion'        => $clase_activa['codigo_seccion'],
                'intervalo'      => substr($clase_activa['hora_inicio'], 0, 5) . ' - ' . substr($clase_activa['hora_fin'], 0, 5),
                'esta_ocupado'   => true,
            ];
        }

        // 3. Próxima clase hoy dentro de 15 minutos
        $clase_proxima = $clasesModel
            ->select('clases_programadas.*, materias.nombre as materia, materias.codigo_materia, secciones.codigo_seccion')
            ->join('secciones', 'secciones.id = clases_programadas.seccion_id')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->where('clases_programadas.aula', $alias)
            ->where('clases_programadas.dia_semana', $dia_actual)
            ->where('clases_programadas.hora_inicio >', $hora_actual)
            ->where('clases_programadas.hora_inicio <=', $hora_mas_15_min)
            ->orderBy('clases_programadas.hora_inicio', 'ASC')
            ->first();

        if ($clase_proxima) {
            $codCarrera = $clase_proxima['carrera'] ?: 'ING-INF';
            $info = self::obtenerInfoCarrera($codCarrera);
            return [
                'estado'         => 'proxima',
                'color'          => 'amarilla',
                'carrera'        => $codCarrera,
                'carrera_nombre' => $info['nombre'],
                'icono'          => $info['icono'],
                'texto_estado'   => 'Próxima clase (' . $info['nombre'] . ')',
                'materia'        => $clase_proxima['materia'],
                'seccion'        => $clase_proxima['codigo_seccion'],
                'intervalo'      => substr($clase_proxima['hora_inicio'], 0, 5) . ' - ' . substr($clase_proxima['hora_fin'], 0, 5),
                'esta_ocupado'   => false,
            ];
        }

        // 4. Aula libre / disponible
        return [
            'estado'         => 'libre',
            'color'          => 'verde',
            'carrera'        => null,
            'carrera_nombre' => null,
            'icono'          => 'bx-check',
            'texto_estado'   => 'Disponible',
            'materia'        => null,
            'seccion'        => null,
            'intervalo'      => null,
            'esta_ocupado'   => false,
        ];
    }

    // 2. VISTA PRINCIPAL DEL MAPA (CARGA INICIAL AUTOMÁTICA)
    public function mostrar_mapa() {
        $aulasModel = new AulasModel();
        $clasesModel = new ClaseProgramadaModel();
        
        // A) AUTO-CARGA DE AULAS: Si la tabla de aulas está vacía, se llena automáticamente
        $lista_aulas = $aulasModel->findAll();
        if (empty($lista_aulas)) {
            (new AulasController())->importar_json();
            $lista_aulas = $aulasModel->findAll();
        }

        // B) AUTO-CARGA DE HORARIOS: Si la tabla de clases está vacía, se llena automáticamente
        if ($clasesModel->countAllResults() === 0) {
            $this->importar_horarios_automaticamente();
        }
        
        // Sincronización horaria y de idioma
        date_default_timezone_set('America/Caracas');
        $hora_actual = date('H:i:s');
        $hora_mas_15_min = date('H:i:s', strtotime('+15 minutes'));
        
        $dias_ingles_a_espanol = [
            'Monday'    => 'Lunes', 
            'Tuesday'   => 'Martes', 
            'Wednesday' => 'Miercoles', 
            'Thursday'  => 'Jueves', 
            'Friday'    => 'Viernes', 
            'Saturday'  => 'Sabado', 
            'Sunday'    => 'Domingo'
        ];
        $dia_actual = $dias_ingles_a_espanol[date('l')];

        // Calculamos el estado actual y logo de carrera de cada aula
        foreach ($lista_aulas as &$aula) {
            $detalles = self::calcularEstadoAula($aula, $clasesModel, $dia_actual, $hora_actual, $hora_mas_15_min);
            $aula = array_merge($aula, $detalles);
        }

        return view('mapa', ['aulas' => $lista_aulas]);
    }

    // 3. INFORMACIÓN DETALLADA DE UN AULA AL HACER CLIC
    public function info_aula($nombre_aula) {
        $clasesModel = new ClaseProgramadaModel();
        
        if ($clasesModel->countAllResults() === 0) {
            $this->importar_horarios_automaticamente();
        }

        $horarios = $clasesModel
            ->select("clases_programadas.*, materias.nombre as materia, materias.codigo_materia, secciones.codigo_seccion, secciones.profesor_id")
            ->join("secciones", "secciones.id = clases_programadas.seccion_id")
            ->join("materias", "materias.id = secciones.materia_id")
            ->where("aula", $nombre_aula)
            ->orderBy("dia_semana", "ASC")
            ->orderBy("hora_inicio", "ASC")
            ->findAll();

        date_default_timezone_set("America/Caracas");
        $horaActual = date("H:i:s");
        $diasInglesAEspanol = [
            "Monday" => "Lunes", "Tuesday" => "Martes", "Wednesday" => "Miercoles",
            "Thursday" => "Jueves", "Friday" => "Viernes", "Saturday" => "Sabado", "Sunday" => "Domingo"
        ];
        $diaActual = $diasInglesAEspanol[date("l")];

        $porDia = [];
        foreach ($horarios as $bloque) {
            $dia = $bloque["dia_semana"];
            $porDia[$dia][] = $bloque;
        }

        $ordenDias = ["Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "Sabado", "Domingo"];
        $horariosFormateados = [];
        $claseActivaAhora  = null;
        $proximaClaseHoy   = null;

        foreach ($ordenDias as $dia) {
            if (empty($porDia[$dia])) continue;

            $bloquesDia = $porDia[$dia];
            $sesionesDia = [];
            $sesionActual = null;

            foreach ($bloquesDia as $b) {
                if ($sesionActual === null) {
                    $infoCarrera = self::obtenerInfoCarrera($b["carrera"] ?? null);
                    $sesionActual = [
                        "materia"        => $b["materia"],
                        "codigo_materia" => $b["codigo_materia"],
                        "seccion"        => $b["codigo_seccion"],
                        "seccion_id"     => $b["seccion_id"],
                        "carrera"        => $b["carrera"] ?? 'ING-INF',
                        "carrera_nombre" => $infoCarrera['nombre'],
                        "carrera_icono"  => $infoCarrera['icono'],
                        "dia_semana"     => $dia,
                        "hora_inicio"    => $b["hora_inicio"],
                        "hora_fin"       => $b["hora_fin"],
                    ];
                } else {
                    if ($sesionActual["materia"] === $b["materia"] &&
                        $sesionActual["seccion"] === $b["codigo_seccion"] &&
                        $sesionActual["hora_fin"] === $b["hora_inicio"]) {
                        $sesionActual["hora_fin"] = $b["hora_fin"];
                    } else {
                        $sesionesDia[] = $sesionActual;
                        $infoCarrera = self::obtenerInfoCarrera($b["carrera"] ?? null);
                        $sesionActual = [
                            "materia"        => $b["materia"],
                            "codigo_materia" => $b["codigo_materia"],
                            "seccion"        => $b["codigo_seccion"],
                            "seccion_id"     => $b["seccion_id"],
                            "carrera"        => $b["carrera"] ?? 'ING-INF',
                            "carrera_nombre" => $infoCarrera['nombre'],
                            "carrera_icono"  => $infoCarrera['icono'],
                            "dia_semana"     => $dia,
                            "hora_inicio"    => $b["hora_inicio"],
                            "hora_fin"       => $b["hora_fin"],
                        ];
                    }
                }
            }
            if ($sesionActual !== null) {
                $sesionesDia[] = $sesionActual;
            }

            foreach ($sesionesDia as &$sesion) {
                $inicioTimestamp = strtotime($sesion["hora_inicio"]);
                $finTimestamp    = strtotime($sesion["hora_fin"]);
                $duracionMinutos = max(1, round(($finTimestamp - $inicioTimestamp) / 60));

                $horas = floor($duracionMinutos / 60);
                $minutos = $duracionMinutos % 60;
                $duracionTexto = ($horas > 0 ? "{$horas}h " : "") . ($minutos > 0 ? "{$minutos}m" : "");

                $horaInicioCorta = substr($sesion["hora_inicio"], 0, 5);
                $horaFinCorta    = substr($sesion["hora_fin"], 0, 5);
                $sesion["intervalo"] = "{$horaInicioCorta} - {$horaFinCorta}";
                $sesion["duracion_minutos"] = $duracionMinutos;
                $sesion["duracion_texto"]   = $duracionTexto;

                $esHoy = ($dia === $diaActual);
                $esActiva = ($esHoy && $horaActual >= $sesion["hora_inicio"] && $horaActual <= $sesion["hora_fin"]);
                $sesion["es_activa_ahora"] = $esActiva;

                if ($esActiva) {
                    $minutosRestantes = max(0, round((strtotime($sesion["hora_fin"]) - strtotime($horaActual)) / 60));
                    $minutosPasados   = max(0, round((strtotime($horaActual) - strtotime($sesion["hora_inicio"])) / 60));
                    $progreso = min(100, max(0, round(($minutosPasados / $duracionMinutos) * 100)));

                    $claseActivaAhora = [
                        "materia"            => $sesion["materia"],
                        "seccion"            => $sesion["seccion"],
                        "carrera"            => $sesion["carrera"],
                        "carrera_nombre"     => $sesion["carrera_nombre"],
                        "carrera_icono"      => $sesion["carrera_icono"],
                        "intervalo"          => $sesion["intervalo"],
                        "duracion"           => $duracionTexto,
                        "minutos_restantes"  => $minutosRestantes,
                        "porcentaje_avance"  => $progreso,
                    ];
                }

                if ($esHoy && $sesion["hora_inicio"] > $horaActual && $proximaClaseHoy === null) {
                    $minutosParaInicio = max(0, round((strtotime($sesion["hora_inicio"]) - strtotime($horaActual)) / 60));
                    $proximaClaseHoy = [
                        "materia"            => $sesion["materia"],
                        "seccion"            => $sesion["seccion"],
                        "carrera"            => $sesion["carrera"],
                        "carrera_nombre"     => $sesion["carrera_nombre"],
                        "carrera_icono"      => $sesion["carrera_icono"],
                        "intervalo"          => $sesion["intervalo"],
                        "inicia_en_minutos"  => $minutosParaInicio,
                    ];
                }
            }

            $horariosFormateados[$dia] = $sesionesDia;
        }

        return $this->response->setJSON([
            "aula"              => $nombre_aula,
            "hora_servidor"     => $horaActual,
            "dia_servidor"      => $diaActual,
            "hay_clase_activa"  => ($claseActivaAhora !== null),
            "clase_activa"      => $claseActivaAhora,
            "proxima_clase_hoy" => $proximaClaseHoy,
            "horarios_por_dia"  => $horariosFormateados,
            "total_bloques"     => count($horarios)
        ]);
    }

    // 4. API PARA ACTUALIZAR ESTADO DE AULAS EN TIEMPO REAL
    public function estado_aulas_en_vivo() {
        date_default_timezone_set('America/Caracas');
        $hora_actual = date('H:i:s');
        $hora_mas_15_min = date('H:i:s', strtotime('+15 minutes'));
        
        $dias_ingles_a_espanol = [
            'Monday'    => 'Lunes', 
            'Tuesday'   => 'Martes', 
            'Wednesday' => 'Miercoles', 
            'Thursday'  => 'Jueves', 
            'Friday'    => 'Viernes', 
            'Saturday'  => 'Sabado', 
            'Sunday'    => 'Domingo'
        ];
        $dia_actual = $dias_ingles_a_espanol[date('l')];

        $aulasModel = new AulasModel();
        $clasesModel = new ClaseProgramadaModel();
        
        $lista_aulas = $aulasModel->findAll();
        if (empty($lista_aulas)) {
            (new AulasController())->importar_json();
            $lista_aulas = $aulasModel->findAll();
        }

        if ($clasesModel->countAllResults() === 0) {
            $this->importar_horarios_automaticamente();
        }

        $estadoMapa = [];
        foreach ($lista_aulas as $aula) {
            $alias = $aula['alias_pdf'];
            $estadoMapa[$alias] = self::calcularEstadoAula($aula, $clasesModel, $dia_actual, $hora_actual, $hora_mas_15_min);
        }

        return $this->response->setJSON($estadoMapa);
    }
}
