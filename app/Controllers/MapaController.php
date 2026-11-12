<?php
namespace App\Controllers;

use App\Models\MateriaModel;
use App\Models\SeccionModel;
use App\Models\ClaseProgramadaModel;
use App\Models\AulasModel;
use App\Services\HorarioSyncService;

class MapaController extends BaseController {

    // 1. CARGA AUTOMÁTICA DE HORARIOS A LA BASE DE DATOS
    public function importar_horarios_automaticamente(bool $limpiarPrevias = false, ?string $carrera = null): int {
        return (new HorarioSyncService())->importarHorariosAutomaticamente($limpiarPrevias, $carrera);
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
    public static function calcularEstadoAula(array $aula, $clasesModel, string $dia_actual, string $hora_actual, string $hora_mas_15_min, ?array $clasesDelAula = null): array {
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

        // =========================================================================
        // RESOLUCIÓN EN RAM ANTIDAYANAS (SI SE PASA EL ARRAY BATCH PRE-CARGADO)
        // Cero queries individuales por cada aula: 60 FPS y cero colapso de MariaDB
        // =========================================================================
        if ($clasesDelAula !== null) {
            $clase_activa = null;
            $clase_proxima = null;

            foreach ($clasesDelAula as $c) {
                if ($c['hora_inicio'] <= $hora_actual && $c['hora_fin'] >= $hora_actual) {
                    $clase_activa = $c;
                    break;
                }
                if ($c['hora_inicio'] > $hora_actual && $c['hora_inicio'] <= $hora_mas_15_min) {
                    if ($clase_proxima === null) {
                        $clase_proxima = $c;
                    }
                }
            }

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

        // 2. Clase activa en este momento (Fallback para llamadas aisladas)
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
        
        // A) AUTO-CARGA DE AULAS: Si la tabla de aulas está vacía o faltan aulas canónicas, se llena automáticamente
        $lista_aulas = $aulasModel->findAll();
        $tieneAula22 = $aulasModel->where('alias_pdf', 'Aula 22')->first();
        if (empty($lista_aulas) || !$tieneAula22) {
            (new HorarioSyncService())->importarPosicionesAulasJson();
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

        // Batch antidayanas: Traemos todas las clases del día en 1 sola consulta SQL agrupada
        $clasesDelDia = $clasesModel
            ->select('clases_programadas.*, materias.nombre as materia, materias.codigo_materia, secciones.codigo_seccion')
            ->join('secciones', 'secciones.id = clases_programadas.seccion_id')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->where('clases_programadas.dia_semana', $dia_actual)
            ->orderBy('clases_programadas.hora_inicio', 'ASC')
            ->findAll();

        $clasesPorAula = [];
        foreach ($clasesDelDia as $c) {
            $clasesPorAula[$c['aula']][] = $c;
        }

        // Calculamos el estado actual y logo de carrera de cada aula en memoria RAM
        foreach ($lista_aulas as &$aula) {
            $alias = $aula['alias_pdf'];
            $detalles = self::calcularEstadoAula($aula, $clasesModel, $dia_actual, $hora_actual, $hora_mas_15_min, $clasesPorAula[$alias] ?? []);
            $aula = array_merge($aula, $detalles);
        }
        unset($aula);

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
        $tieneAula22 = $aulasModel->where('alias_pdf', 'Aula 22')->first();
        if (empty($lista_aulas) || !$tieneAula22) {
            (new HorarioSyncService())->importarPosicionesAulasJson();
            $lista_aulas = $aulasModel->findAll();
        }

        if ($clasesModel->countAllResults() === 0) {
            $this->importar_horarios_automaticamente();
        }

        // Batch antidayanas para API en vivo: 1 sola consulta SQL para todo el campus
        $clasesDelDia = $clasesModel
            ->select('clases_programadas.*, materias.nombre as materia, materias.codigo_materia, secciones.codigo_seccion')
            ->join('secciones', 'secciones.id = clases_programadas.seccion_id')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->where('clases_programadas.dia_semana', $dia_actual)
            ->orderBy('clases_programadas.hora_inicio', 'ASC')
            ->findAll();

        $clasesPorAula = [];
        foreach ($clasesDelDia as $c) {
            $clasesPorAula[$c['aula']][] = $c;
        }

        $estadoMapa = [];
        foreach ($lista_aulas as $aula) {
            $alias = $aula['alias_pdf'];
            $estadoMapa[$alias] = self::calcularEstadoAula($aula, $clasesModel, $dia_actual, $hora_actual, $hora_mas_15_min, $clasesPorAula[$alias] ?? []);
        }

        return $this->response->setJSON($estadoMapa);
    }
}
