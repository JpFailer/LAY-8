<?php

namespace App\Services;

use App\Models\ActividadClaseModel;
use App\Models\ClaseProgramadaModel;
use App\Models\EstadoClaseModel;
use App\Models\InscripcionModel;

/**
 * Servicio de dominio para hidratar aulas inscritas, horarios semanales y cronograma de actividades.
 */
class HorarioAulasService
{
    private const DIAS_BASE = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];

    public function obtenerDatosVistaAulas(int $usuarioId, array $bloquesOficiales): array
    {
        $db               = \Config\Database::connect();
        $inscripcionModel = new InscripcionModel();
        $estadoModel      = new EstadoClaseModel();
        $actividadModel   = new ActividadClaseModel();

        // 1. Secciones inscritas oficialmente por el alumno
        $secciones = $inscripcionModel
            ->select('secciones.*, materias.nombre as materia, materias.codigo_materia, materias.unidades_credito, materias.semestre_trayecto, usuarios.primer_nombre as prof_nombre, usuarios.apellidos as prof_apellido')
            ->join('secciones', 'secciones.id = inscripciones.seccion_id')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->join('usuarios', 'usuarios.id = secciones.profesor_id', 'left')
            ->where('inscripciones.estudiante_id', $usuarioId)
            ->orderBy('materias.nombre', 'ASC')
            ->findAll();

        $seccionesIds = !empty($secciones) ? array_map(static fn (array $s): int => (int) $s['id'], $secciones) : [];
        $mapaSecciones = $this->resolverMapaSecciones($db, $secciones, $usuarioId);

        // 2. Exclusiones personales actuales
        $bloquesExcluidos = $usuarioId > 0
            ? $db->table('estudiante_bloques_excluidos')->select('clase_id')->where('estudiante_id', $usuarioId)->get()->getResultArray()
            : [];
        $idsExcluidos = !empty($bloquesExcluidos) ? array_map('intval', array_column($bloquesExcluidos, 'clase_id')) : [];
        $excluidosSet = array_flip($idsExcluidos);

        // 3. Batch loading (estado hoy, clases, actividades)
        $estadosHoyMapa        = [];
        $horariosPorSeccion    = [];
        $actividadesConteoMapa = [];
        $horarioSemanal        = [];
        $totalOficiales        = 0;

        if (!empty($seccionesIds)) {
            $estadosHoyRaw = $estadoModel->whereIn('seccion_id', $seccionesIds)
                ->where('fecha', date('Y-m-d'))
                ->orderBy('id', 'DESC')
                ->findAll();
            foreach ($estadosHoyRaw as $est) {
                $sId = (int) $est['seccion_id'];
                if (!isset($estadosHoyMapa[$sId])) {
                    $estadosHoyMapa[$sId] = $est;
                }
            }

            $actividadesConteoRaw = $db->table('actividades_clase')
                ->select('seccion_id, COUNT(*) as total')
                ->whereIn('seccion_id', $seccionesIds)
                ->groupStart()
                    ->where('es_personal', 0)
                    ->orWhere('creado_por', $usuarioId)
                ->groupEnd()
                ->groupBy('seccion_id')
                ->get()
                ->getResultArray();
            foreach ($actividadesConteoRaw as $ac) {
                $actividadesConteoMapa[(int) $ac['seccion_id']] = (int) $ac['total'];
            }

            $todosBloques = $this->consultarBloquesSecciones($db, $usuarioId, $mapaSecciones);
            foreach ($todosBloques as $bloque) {
                $secInscrita = (int) $bloque['seccion_id_inscrita'];
                $horariosPorSeccion[$secInscrita][] = $bloque;

                $esPersonal = !empty($bloque['estudiante_id']) && (int) $bloque['estudiante_id'] === $usuarioId;
                if (!$esPersonal) {
                    $totalOficiales++;
                }
                if ($esPersonal || !isset($excluidosSet[(int) $bloque['id']])) {
                    $horarioSemanal[] = $bloque;
                }
            }
        }

        foreach ($secciones as &$sec) {
            $secId = (int) $sec['id'];
            $sec['estado_hoy']        = $estadosHoyMapa[$secId] ?? null;
            $sec['horarios']          = $horariosPorSeccion[$secId] ?? [];
            $sec['total_actividades'] = $actividadesConteoMapa[$secId] ?? 0;
        }
        unset($sec);

        // 4. Matriz semanal [Dia] con normalización de acentos, Domingo condicional y rango real HH:MM
        $diasSemana = self::DIAS_BASE;
        $grillaHorarios = array_fill_keys(array_merge(self::DIAS_BASE, ['Domingo']), []);

        foreach ($horarioSemanal as $bloque) {
            $dia = $bloque['dia_canon'] ?? HorarioNormalizer::normalizarDia((string) $bloque['dia_semana']);
            if ($dia !== null && isset($grillaHorarios[$dia])) {
                $inicioFormato = HorarioNormalizer::formatearHoraCorta((string) $bloque['hora_inicio']);
                $finFormato    = HorarioNormalizer::formatearHoraCorta((string) $bloque['hora_fin']);
                $grillaHorarios[$dia][] = [
                    'id'          => (int) $bloque['id'],
                    'seccion_id'  => (int) $bloque['seccion_id'],
                    'materia'     => $bloque['materia'],
                    'codigo'      => $bloque['codigo_materia'],
                    'seccion'     => $bloque['codigo_seccion'],
                    'aula'        => $bloque['aula'],
                    'hora_inicio' => $bloque['hora_inicio_norm'] ?? $bloque['hora_inicio'],
                    'hora_fin'    => $bloque['hora_fin_norm'] ?? $bloque['hora_fin'],
                    'inicio_min'  => (int) ($bloque['inicio_min'] ?? (HorarioNormalizer::aMinutos($bloque['hora_inicio']) ?? 0)),
                    'fin_min'     => (int) ($bloque['fin_min'] ?? (HorarioNormalizer::aMinutos($bloque['hora_fin']) ?? 0)),
                    'intervalo'   => $inicioFormato . ' - ' . $finFormato,
                    'es_personal' => !empty($bloque['estudiante_id']),
                ];
            }
        }

        if (!empty($grillaHorarios['Domingo'])) {
            $diasSemana[] = 'Domingo';
        } else {
            unset($grillaHorarios['Domingo']);
        }

        $turnosHorarios = $this->construirTurnosGrilla($bloquesOficiales, $grillaHorarios, $diasSemana);
        $actividades    = $actividadModel->obtenerParaEstudiante($usuarioId);

        return [
            'secciones'              => $secciones,
            'totalMaterias'          => count($secciones),
            'horarioSemanal'         => $horarioSemanal,
            'grillaHorarios'         => $grillaHorarios,
            'turnosHorarios'         => $turnosHorarios,
            'tieneBloquesExcluidos'  => $totalOficiales > count($horarioSemanal) || !empty($idsExcluidos),
            'totalBloquesOficiales'  => $totalOficiales,
            'actividades'            => $actividades,
            'bloquesOficiales'       => $bloquesOficiales,
            'diasSemana'             => $diasSemana,
        ];
    }

    /**
     * Devuelve todos los IDs de clases oficiales (`estudiante_id IS NULL`) que corresponden
     * a las inscripciones del usuario (incluyendo secciones equivalentes ya sincronizadas).
     *
     * @return list<int>
     */
    public function obtenerIdsBloquesOficialesDeUsuario(int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        $secciones = (new InscripcionModel())->obtenerSeccionesDeEstudiante($usuarioId);
        if (empty($secciones)) {
            return [];
        }

        $mapaSecciones = $this->resolverMapaSecciones($db, $secciones, $usuarioId);
        $todos = $this->consultarBloquesSecciones($db, $usuarioId, $mapaSecciones);

        $ids = [];
        foreach ($todos as $b) {
            if (empty($b['estudiante_id'])) {
                $ids[] = (int) $b['id'];
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Clases del día actual para "Mi Jornada de Hoy" (Home::index), consistentes con /aulas.
     */
    public function obtenerClasesDelDiaEstudiante(int $usuarioId, string $diaSemana, ?string $horaActual = null): array
    {
        if ($usuarioId <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        $secciones = (new InscripcionModel())->obtenerSeccionesDeEstudiante($usuarioId);
        $mapaSecciones = $this->resolverMapaSecciones($db, $secciones, $usuarioId);

        $excluidos = $db->table('estudiante_bloques_excluidos')
            ->select('clase_id')
            ->where('estudiante_id', $usuarioId)
            ->get()
            ->getResultArray();
        $excluidosSet = array_flip(array_map('intval', array_column($excluidos, 'clase_id')));

        $diaObjetivo = HorarioNormalizer::normalizarDia($diaSemana);
        $ahoraMin    = HorarioNormalizer::aMinutos($horaActual ?: date('H:i:s')) ?? -1;

        $todos = $this->consultarBloquesSecciones($db, $usuarioId, $mapaSecciones);
        $resultado = [];

        foreach ($todos as $bloque) {
            $esPersonal = !empty($bloque['estudiante_id']) && (int) $bloque['estudiante_id'] === $usuarioId;
            if (!$esPersonal && isset($excluidosSet[(int) $bloque['id']])) {
                continue;
            }
            if ($diaObjetivo !== null && ($bloque['dia_canon'] ?? null) !== $diaObjetivo) {
                continue;
            }

            $inicioMin = (int) ($bloque['inicio_min'] ?? -1);
            $finMin    = (int) ($bloque['fin_min'] ?? -1);
            $enCurso   = ($ahoraMin >= 0 && $inicioMin >= 0 && $finMin >= $inicioMin)
                ? ($ahoraMin >= $inicioMin && $ahoraMin <= $finMin)
                : false;

            $resultado[] = [
                'id'                  => (int) $bloque['id'],
                'seccion_id'          => (int) $bloque['seccion_id'],
                'aula'                => $bloque['aula'],
                'dia_semana'          => $bloque['dia_canon'] ?? $bloque['dia_semana'],
                'hora_inicio'         => $bloque['hora_inicio_norm'] ?? $bloque['hora_inicio'],
                'hora_fin'            => $bloque['hora_fin_norm'] ?? $bloque['hora_fin'],
                'hora_inicio_formato' => HorarioNormalizer::formatearHoraCorta((string) $bloque['hora_inicio']),
                'hora_fin_formato'    => HorarioNormalizer::formatearHoraCorta((string) $bloque['hora_fin']),
                'materia_nombre'      => $bloque['materia'],
                'codigo_materia'      => $bloque['codigo_materia'],
                'codigo_seccion'      => $bloque['codigo_seccion'],
                'en_curso'            => $enCurso,
            ];
        }

        return $resultado;
    }

    /**
     * Mapea cada sección inscrita hacia sí misma y, si no tiene bloques propios, hacia su sección
     * oficial equivalente (por ej. SVC-A/2026-01 -> A/2026-I en la misma materia y carrera).
     * Tolera códigos de materia con/sin guion (CAL604 vs CAL-604) y variaciones de mención.
     *
     * @return array{todasIds: list<int>, efectivoToInscrito: array<int, int>}
     */
    private function resolverMapaSecciones($db, array $secciones, int $usuarioId = 0): array
    {
        if (empty($secciones)) {
            return ['todasIds' => [], 'efectivoToInscrito' => []];
        }

        $carreraEstudiante = '';
        if ($usuarioId > 0) {
            $uRow = $db->table('usuarios')->select('carrera')->where('id', $usuarioId)->get()->getRowArray();
            $carreraEstudiante = strtoupper(trim((string) ($uRow['carrera'] ?? '')));
        }

        $inscritasIds = [];
        $efectivoToInscrito = [];
        foreach ($secciones as $s) {
            $sId = (int) $s['id'];
            $inscritasIds[] = $sId;
            $efectivoToInscrito[$sId] = $sId;
        }

        $conteoRaw = $db->table('clases_programadas')
            ->select('seccion_id, COUNT(*) as total')
            ->whereIn('seccion_id', $inscritasIds)
            ->where('estudiante_id', null)
            ->groupBy('seccion_id')
            ->get()
            ->getResultArray();
        $conteos = [];
        foreach ($conteoRaw as $r) {
            $conteos[(int) $r['seccion_id']] = (int) $r['total'];
        }

        $sinClases = [];
        $materiasIds = [];
        $codigosVariantes = [];
        foreach ($secciones as $s) {
            if (empty($conteos[(int) $s['id']])) {
                $sinClases[] = $s;
                $materiasIds[] = (int) $s['materia_id'];
                if (!empty($s['codigo_materia'])) {
                    foreach (HorarioNormalizer::variantesCodigoMateria((string) $s['codigo_materia']) as $v) {
                        $codigosVariantes[] = $v;
                    }
                }
            }
        }

        if (!empty($sinClases)) {
            $materiasIds = array_values(array_unique(array_filter($materiasIds)));
            $codigosVariantes = array_values(array_unique(array_filter($codigosVariantes)));

            $builderMats = $db->table('materias');
            $builderMats->groupStart();
            if (!empty($materiasIds)) {
                $builderMats->whereIn('id', $materiasIds);
            }
            if (!empty($codigosVariantes)) {
                $builderMats->orWhereIn('codigo_materia', $codigosVariantes);
            }
            $builderMats->groupEnd();
            $materiasEncontradas = $builderMats->get()->getResultArray();

            $todasMateriasIds = array_map(static fn (array $m): int => (int) $m['id'], $materiasEncontradas);

            if (!empty($todasMateriasIds)) {
                $candidatas = $db->table('secciones')
                    ->select('secciones.*, materias.codigo_materia, materias.nombre as materia_nombre')
                    ->join('materias', 'materias.id = secciones.materia_id')
                    ->whereIn('secciones.materia_id', $todasMateriasIds)
                    ->get()
                    ->getResultArray();

                $candIds = array_map(static fn (array $c): int => (int) $c['id'], $candidatas);
                $candClases = [];
                if (!empty($candIds)) {
                    $rows = $db->table('clases_programadas')
                        ->select('seccion_id, carrera, COUNT(*) as total')
                        ->whereIn('seccion_id', $candIds)
                        ->where('estudiante_id', null)
                        ->groupBy('seccion_id, carrera')
                        ->get()
                        ->getResultArray();
                    foreach ($rows as $r) {
                        $cSecId = (int) $r['seccion_id'];
                        $cCar = strtoupper(trim((string) ($r['carrera'] ?? '')));
                        $candClases[$cSecId][$cCar] = (int) $r['total'];
                    }
                }

                foreach ($sinClases as $s) {
                    $sId = (int) $s['id'];
                    $sMId = (int) $s['materia_id'];
                    $sCanon = HorarioNormalizer::codigoMateriaCanonico((string) ($s['codigo_materia'] ?? ''));
                    $sSecCod = (string) ($s['codigo_seccion'] ?? '');
                    $sCar = strtoupper(trim((string) ($s['carrera'] ?? '')));
                    if ($sCar === '' && $carreraEstudiante !== '') {
                        $sCar = $carreraEstudiante;
                    }
                    $sPer = (string) ($s['periodo_academico'] ?? '');
                    $perFam = HorarioNormalizer::familiaPeriodo($sPer);
                    $tiers = HorarioNormalizer::variantesSeccionTiers($sSecCod);
                    $carMencion = HorarioNormalizer::inferirCarreraPorSeccion($sSecCod);

                    $mejorId = null;
                    $mejorScore = null;

                    foreach ($candidatas as $c) {
                        $cId = (int) $c['id'];
                        if ($cId === $sId || empty($candClases[$cId])) {
                            continue;
                        }

                        $cCanon = HorarioNormalizer::codigoMateriaCanonico((string) ($c['codigo_materia'] ?? ''));
                        if ($cCanon !== $sCanon && (int) $c['materia_id'] !== $sMId) {
                            continue;
                        }

                        $cCod = strtoupper(trim((string) ($c['codigo_seccion'] ?? '')));
                        $cCanonSec = HorarioNormalizer::normalizarSeccionCanonica($cCod);
                        $tier = 0;
                        if (in_array($cCod, $tiers['exactas'], true) || in_array($cCanonSec, $tiers['exactas'], true)) {
                            $tier = 2;
                        } elseif (in_array($cCod, $tiers['base'], true) || in_array($cCanonSec, $tiers['base'], true)) {
                            $tier = 1;
                        }
                        if ($tier === 0) {
                            continue;
                        }

                        $cCar = strtoupper(trim((string) ($c['carrera'] ?? '')));
                        $tieneClasesParaMiCarrera = isset($candClases[$cId][$sCar]) || ($carreraEstudiante !== '' && isset($candClases[$cId][$carreraEstudiante]));

                        $carScore = 1;
                        if ($cCar !== '' && ($cCar === $sCar || ($carreraEstudiante !== '' && $cCar === $carreraEstudiante))) {
                            $carScore = 4;
                        } elseif ($tieneClasesParaMiCarrera) {
                            $carScore = 3;
                        } elseif ($cCar === '') {
                            $carScore = 2;
                        } elseif ($carMencion !== null && $cCar === $carMencion) {
                            $carScore = 2;
                        }

                        $cPerFam = HorarioNormalizer::familiaPeriodo((string) ($c['periodo_academico'] ?? ''));
                        $perScore = ($perFam !== '' && $cPerFam === $perFam) ? 1 : 0;

                        $score = [$tier, $carScore, $perScore, $cId];
                        if ($mejorScore === null || $score > $mejorScore) {
                            $mejorScore = $score;
                            $mejorId = $cId;
                        }
                    }

                    if ($mejorId !== null) {
                        $efectivoToInscrito[$mejorId] = $sId;
                    }
                }
            }
        }

        return [
            'todasIds'           => array_values(array_unique(array_keys($efectivoToInscrito))),
            'efectivoToInscrito' => $efectivoToInscrito,
        ];
    }

    /**
     * Consulta los bloques de `clases_programadas` con filtro inteligente de carrera por sección y orden cronológico.
     */
    private function consultarBloquesSecciones($db, int $usuarioId, array $mapaSecciones): array
    {
        $idsConsultar = $mapaSecciones['todasIds'];
        if (empty($idsConsultar)) {
            return [];
        }

        $carreraUsuario = '';
        if ($usuarioId > 0) {
            $uRow = $db->table('usuarios')->select('carrera')->where('id', $usuarioId)->get()->getRowArray();
            $carreraUsuario = strtoupper(trim((string) ($uRow['carrera'] ?? '')));
        }

        $filas = $db->table('clases_programadas')
            ->select('clases_programadas.*, materias.nombre as materia, materias.codigo_materia, secciones.codigo_seccion, secciones.carrera as seccion_carrera')
            ->join('secciones', 'secciones.id = clases_programadas.seccion_id')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->whereIn('clases_programadas.seccion_id', $idsConsultar)
            ->groupStart()
                ->where('clases_programadas.estudiante_id', null)
                ->orWhere('clases_programadas.estudiante_id', $usuarioId)
            ->groupEnd()
            ->get()
            ->getResultArray();

        // Detectar si alguna sección compartida tiene bloques explícitamente etiquetados para la carrera del usuario
        $seccionesConMiCarrera = [];
        if ($carreraUsuario !== '') {
            foreach ($filas as $f) {
                if (empty($f['estudiante_id']) && strtoupper(trim((string) ($f['carrera'] ?? ''))) === $carreraUsuario) {
                    $seccionesConMiCarrera[(int) $f['seccion_id']] = true;
                }
            }
        }

        $resultado = [];
        $vistos = [];

        foreach ($filas as $b) {
            $id = (int) $b['id'];
            if (isset($vistos[$id])) {
                continue;
            }
            $vistos[$id] = true;

            $esPersonal = !empty($b['estudiante_id']) && (int) $b['estudiante_id'] === $usuarioId;
            if (!$esPersonal) {
                $clsCar = strtoupper(trim((string) ($b['carrera'] ?? '')));
                $secId = (int) $b['seccion_id'];

                // Si esta sección tiene bloques específicos para la carrera del estudiante (p.ej. Lab Física II para ING-INF),
                // descartar los bloques de esa misma sección programados para otras carreras (p.ej. ING-AMB en sábado).
                if (!empty($seccionesConMiCarrera[$secId]) && $clsCar !== '' && $clsCar !== $carreraUsuario) {
                    continue;
                }
            }

            $diaCanon = HorarioNormalizer::normalizarDia((string) ($b['dia_semana'] ?? ''));
            if ($diaCanon === null) {
                continue;
            }

            $inicioMin = HorarioNormalizer::aMinutos((string) ($b['hora_inicio'] ?? ''));
            $finMin    = HorarioNormalizer::aMinutos((string) ($b['hora_fin'] ?? ''));
            if ($inicioMin === null || $finMin === null) {
                continue;
            }

            $b['dia_canon']          = $diaCanon;
            $b['inicio_min']         = $inicioMin;
            $b['fin_min']            = $finMin;
            $b['hora_inicio_norm']   = HorarioNormalizer::normalizarHora((string) $b['hora_inicio']);
            $b['hora_fin_norm']      = HorarioNormalizer::normalizarHora((string) $b['hora_fin']);
            $b['seccion_id_inscrita'] = $mapaSecciones['efectivoToInscrito'][(int) $b['seccion_id']] ?? (int) $b['seccion_id'];

            $resultado[] = $b;
        }

        usort($resultado, static function (array $a, array $b): int {
            $cmpDia = HorarioNormalizer::ordenDia((string) $a['dia_canon']) <=> HorarioNormalizer::ordenDia((string) $b['dia_canon']);
            if ($cmpDia !== 0) {
                return $cmpDia;
            }
            $cmpIni = $a['inicio_min'] <=> $b['inicio_min'];
            if ($cmpIni !== 0) {
                return $cmpIni;
            }
            return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
        });

        return $resultado;
    }

    /**
     * Construye las filas y celdas de la grilla semanal evaluando solapamiento real de minutos.
     * Incluye dinámicamente la franja de mediodía (11:45 - 12:45) o bloques fuera de rango si hay clases allí.
     */
    private function construirTurnosGrilla(array $bloquesOficiales, array $grillaHorarios, array $diasSemana): array
    {
        $turnosBase = $bloquesOficiales;

        // Verificar si alguna clase ocupa la franja de mediodía (11:45 - 12:45)
        $hayMediodia = false;
        foreach ($grillaHorarios as $clasesDia) {
            foreach ($clasesDia as $c) {
                if ($this->claseSolapaIntervalo($c, 705, 765)) {
                    $hayMediodia = true;
                    break 2;
                }
            }
        }
        if ($hayMediodia) {
            $nuevoBase = [];
            foreach ($turnosBase as $turno => $lista) {
                $nuevoBase[$turno] = $lista;
                if ($turno === 'Mañana') {
                    $nuevoBase['Mediodía'] = [
                        ['inicio' => '11:45:00', 'fin' => '12:45:00', 'etiqueta' => '11:45 - 12:45'],
                    ];
                }
            }
            $turnosBase = $nuevoBase;
        }

        $ubicadosIds = [];
        $primeraAparicion = [];
        $turnosRender = [];

        foreach ($turnosBase as $turno => $bloques) {
            $filas = [];
            foreach ($bloques as $b) {
                $sMin = HorarioNormalizer::aMinutos((string) $b['inicio']) ?? 0;
                $fMin = HorarioNormalizer::aMinutos((string) $b['fin']) ?? 0;
                $clave = sprintf('%04d_%04d', $sMin, $fMin);

                $celdas = [];
                foreach ($diasSemana as $dia) {
                    $encontradas = [];
                    foreach ($grillaHorarios[$dia] ?? [] as $c) {
                        if ($this->claseSolapaIntervalo($c, $sMin, $fMin)) {
                            $id = (int) $c['id'];
                            $c['es_continuacion'] = isset($primeraAparicion[$id]);
                            $primeraAparicion[$id] = true;
                            $ubicadosIds[$id] = true;
                            $encontradas[] = $c;
                        }
                    }
                    $celdas[$dia] = $encontradas;
                }

                $filas[] = [
                    'clave'      => $clave,
                    'inicio'     => $b['inicio'],
                    'fin'        => $b['fin'],
                    'etiqueta'   => $b['etiqueta'],
                    'inicio_min' => $sMin,
                    'fin_min'    => $fMin,
                    'celdas'     => $celdas,
                ];
            }
            $turnosRender[$turno] = $filas;
        }

        // Rescatar cualquier clase con horario atípico (< 07:15 o > 22:30) que no haya caído en los bloques estándar
        $rangosExtra = [];
        foreach ($grillaHorarios as $clasesDia) {
            foreach ($clasesDia as $c) {
                $id = (int) $c['id'];
                if (isset($ubicadosIds[$id])) {
                    continue;
                }
                $key = sprintf('%04d_%04d', (int) $c['inicio_min'], (int) $c['fin_min']);
                if (!isset($rangosExtra[$key])) {
                    $rangosExtra[$key] = [
                        'inicio_min' => (int) $c['inicio_min'],
                        'fin_min'    => (int) $c['fin_min'],
                        'etiqueta'   => $c['intervalo'],
                    ];
                }
            }
        }

        if ($rangosExtra !== []) {
            uasort($rangosExtra, static fn (array $a, array $b): int => $a['inicio_min'] <=> $b['inicio_min']);
            $filasExtra = [];
            foreach ($rangosExtra as $clave => $r) {
                $celdas = [];
                foreach ($diasSemana as $dia) {
                    $encontradas = [];
                    foreach ($grillaHorarios[$dia] ?? [] as $c) {
                        if ((int) $c['inicio_min'] === $r['inicio_min'] && (int) $c['fin_min'] === $r['fin_min']) {
                            $c['es_continuacion'] = false;
                            $ubicadosIds[(int) $c['id']] = true;
                            $encontradas[] = $c;
                        }
                    }
                    $celdas[$dia] = $encontradas;
                }
                $filasExtra[] = [
                    'clave'      => 'ext_' . $clave,
                    'inicio'     => HorarioNormalizer::normalizarHora($r['etiqueta']) ?? '',
                    'fin'        => '',
                    'etiqueta'   => $r['etiqueta'],
                    'inicio_min' => $r['inicio_min'],
                    'fin_min'    => $r['fin_min'],
                    'celdas'     => $celdas,
                ];
            }
            $turnosRender['Otros Horarios'] = $filasExtra;
        }

        return $turnosRender;
    }

    private function claseSolapaIntervalo(array $clase, int $slotInicioMin, int $slotFinMin): bool
    {
        $cIni = (int) ($clase['inicio_min'] ?? -1);
        $cFin = (int) ($clase['fin_min'] ?? -1);
        if ($cIni < 0 || $cFin < 0) {
            return false;
        }
        if ($cFin <= $cIni) {
            return $cIni >= $slotInicioMin && $cIni < $slotFinMin;
        }

        return $cIni < $slotFinMin && $cFin > $slotInicioMin;
    }
}
