<?php

namespace App\Services;

use App\Models\ActividadClaseModel;
use App\Models\EstadoClaseModel;
use App\Models\SeccionModel;

/**
 * Servicio de dominio para el Panel Docente (KPIs, secciones paginadas en SQL y evaluaciones).
 */
class DocentePanelService
{
    public const NOMBRES_CARRERAS = [
        'ING-INF' => 'Ing. Informática',
        'ING-MAR' => 'Ing. Marítima',
        'ING-AMB' => 'Ing. Ambiental',
        'ADM'     => 'Administración',
        'TUR'     => 'Turismo',
    ];

    /**
     * Devuelve el paquete completo para el renderizado inicial del Panel Docente.
     */
    public function obtenerDatosPanel(int $usuarioId, string $rol, array $filtros = []): array
    {
        $db = \Config\Database::connect();
        $esAdmin = in_array($rol, ['administrador', 'admin'], true);

        // 1. Paginación de secciones (SQL LIMIT/OFFSET para modo supervisión admin)
        $resultadoSec = $this->obtenerSeccionesPaginadas($usuarioId, $rol, $filtros);

        // 2. Carreras presentes con conteo (para barra de filtros)
        $carBuilder = $db->table('secciones')
            ->select("UPPER(TRIM(COALESCE(secciones.carrera, ''))) AS carrera, COUNT(*) AS total", false)
            ->groupBy("UPPER(TRIM(COALESCE(secciones.carrera, '')))", false);
        if (!$esAdmin) {
            $carBuilder->where('secciones.profesor_id', $usuarioId);
        }
        $carrerasPresentesDoc = [];
        foreach ($carBuilder->get()->getResultArray() as $cr) {
            $cod = (string) ($cr['carrera'] ?? '');
            if ($cod !== '') {
                $carrerasPresentesDoc[$cod] = [
                    'etiqueta' => self::NOMBRES_CARRERAS[$cod] ?? $cod,
                    'total'    => (int) ($cr['total'] ?? 0),
                ];
            }
        }
        ksort($carrerasPresentesDoc);

        // 3. Lista ligera para <select> de nueva tarea
        $selectBuilder = $db->table('secciones')
            ->select('secciones.id, secciones.codigo_seccion, materias.nombre as materia')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->orderBy('materias.nombre', 'ASC');
        if (!$esAdmin) {
            $selectBuilder->where('secciones.profesor_id', $usuarioId);
        }
        $seccionesSelect = $selectBuilder->get()->getResultArray();
        $seccionesIdsGlobal = array_map(static fn ($r) => (int) $r['id'], $seccionesSelect);

        // 4. KPIs agregados en SQL sin hidratar todas las tarjetas en RAM
        $totalSecciones = count($seccionesIdsGlobal);
        $totalAlumnos = 0;
        if (!empty($seccionesIdsGlobal)) {
            if ($esAdmin) {
                $totalAlumnos = (int) $db->table('inscripciones')->countAllResults();
            } else {
                $totalAlumnos = (int) $db->table('inscripciones')
                    ->whereIn('seccion_id', $seccionesIdsGlobal)
                    ->countAllResults();
            }
        }

        // 5. Actividades oficiales + batch de entregas pendientes
        $actividades = [];
        $totalEntregasSinCalificar = 0;
        if (!empty($seccionesIdsGlobal)) {
            $actividadModel = new ActividadClaseModel();
            $actBuilder = $actividadModel
                ->select('actividades_clase.*, materias.nombre as materia, materias.codigo_materia, secciones.codigo_seccion')
                ->join('secciones', 'secciones.id = actividades_clase.seccion_id')
                ->join('materias', 'materias.id = secciones.materia_id')
                ->where('actividades_clase.es_personal', 0)
                ->orderBy('actividades_clase.fecha_entrega', 'DESC');

            if (!$esAdmin) {
                $actBuilder->whereIn('actividades_clase.seccion_id', $seccionesIdsGlobal);
            }
            $actividades = $actBuilder->findAll();

            $actividadesIds = array_column($actividades, 'id');
            $entregasStatsMapa = [];
            if (!empty($actividadesIds)) {
                $entregasRaw = $db->table('entregas_actividades')
                    ->select('actividad_id, COUNT(*) as total_entregas, SUM(CASE WHEN calificacion IS NULL THEN 1 ELSE 0 END) as sin_calificar')
                    ->whereIn('actividad_id', $actividadesIds)
                    ->groupBy('actividad_id')
                    ->get()
                    ->getResultArray();

                foreach ($entregasRaw as $er) {
                    $entregasStatsMapa[(int) $er['actividad_id']] = [
                        'total'         => (int) $er['total_entregas'],
                        'sin_calificar' => (int) $er['sin_calificar'],
                    ];
                }
            }

            foreach ($actividades as &$act) {
                $aId = (int) $act['id'];
                $stats = $entregasStatsMapa[$aId] ?? ['total' => 0, 'sin_calificar' => 0];
                $act['total_entregas'] = $stats['total'];
                $act['sin_calificar']  = $stats['sin_calificar'];
                $totalEntregasSinCalificar += $stats['sin_calificar'];
            }
            unset($act);
        }

        // 6. Solicitudes de inscripción extemporánea pendientes de autorización
        $solicitudExtModel = new \App\Models\SolicitudExtemporaneaModel();
        $solicitudesExtemporaneas = $solicitudExtModel->obtenerPendientesParaDocente($usuarioId, $esAdmin);
        $totalSolicitudesExtemporaneas = count($solicitudesExtemporaneas);

        return [
            'secciones'                     => $resultadoSec['secciones'],
            'seccionesSelect'               => $seccionesSelect,
            'carrerasPresentesDoc'          => $carrerasPresentesDoc,
            'esModoServidor'                => $esAdmin,
            'paginacion'                    => [
                'totalFiltradas' => $resultadoSec['totalFiltradas'],
                'paginaActual'   => $resultadoSec['paginaActual'],
                'totalPaginas'   => $resultadoSec['totalPaginas'],
                'porPagina'      => $resultadoSec['porPagina'],
                'carreraActiva'  => $resultadoSec['carreraActiva'],
                'terminoActivo'  => $resultadoSec['terminoActivo'],
            ],
            'actividades'                   => $actividades,
            'solicitudesExtemporaneas'      => $solicitudesExtemporaneas,
            'totalSolicitudesExtemporaneas' => $totalSolicitudesExtemporaneas,
            'totalSecciones'                => $totalSecciones,
            'totalAlumnos'                  => $totalAlumnos,
            'totalActividades'              => count($actividades),
            'totalEntregasSinCalificar'     => $totalEntregasSinCalificar,
        ];
    }

    /**
     * Obtiene secciones filtradas y paginadas en SQL (para admin), o todas las del profesor.
     */
    public function obtenerSeccionesPaginadas(int $usuarioId, string $rol, array $filtros = []): array
    {
        $db = \Config\Database::connect();
        $esAdmin = in_array($rol, ['administrador', 'admin'], true);

        $carrera = strtoupper(trim((string) ($filtros['carrera'] ?? '')));
        $termino = trim((string) ($filtros['q'] ?? ''));
        $porPagina = (int) ($filtros['por_pagina'] ?? 12);
        if (!in_array($porPagina, [12, 24, 48], true)) {
            $porPagina = 12;
        }
        $pagina = max(1, (int) ($filtros['page'] ?? 1));

        $seccionModel = new SeccionModel();
        $builder = $seccionModel
            ->select('secciones.*, materias.nombre as materia, materias.codigo_materia')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->orderBy('materias.nombre', 'ASC')
            ->orderBy('secciones.codigo_seccion', 'ASC');

        if (!$esAdmin) {
            $builder->where('secciones.profesor_id', $usuarioId);
            $secciones = $builder->findAll();
            $total = count($secciones);
            $seccionesHidratadas = $this->hidratarSecciones($secciones);

            return [
                'secciones'      => $seccionesHidratadas,
                'totalFiltradas' => $total,
                'paginaActual'   => 1,
                'totalPaginas'   => max(1, (int) ceil($total / $porPagina)),
                'porPagina'      => $porPagina,
                'carreraActiva'  => '',
                'terminoActivo'  => '',
            ];
        }

        if ($carrera !== '') {
            $builder->where('UPPER(secciones.carrera)', $carrera);
        }
        if ($termino !== '') {
            $tokens = preg_split('/\s+/u', $termino, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($tokens as $tok) {
                $builder->groupStart()
                    ->like('materias.nombre', $tok)
                    ->orLike('materias.codigo_materia', $tok)
                    ->orLike('secciones.codigo_seccion', $tok)
                    ->orLike('secciones.carrera', $tok)
                    ->groupEnd();
            }
        }

        $totalFiltradas = (int) $builder->countAllResults(false);
        $totalPaginas = max(1, (int) ceil($totalFiltradas / $porPagina));
        $pagina = min($pagina, $totalPaginas);
        $offset = ($pagina - 1) * $porPagina;

        $seccionesPagina = $builder->findAll($porPagina, $offset);
        $seccionesHidratadas = $this->hidratarSecciones($seccionesPagina);

        return [
            'secciones'      => $seccionesHidratadas,
            'totalFiltradas' => $totalFiltradas,
            'paginaActual'   => $pagina,
            'totalPaginas'   => $totalPaginas,
            'porPagina'      => $porPagina,
            'carreraActiva'  => $carrera,
            'terminoActivo'  => $termino,
        ];
    }

    /**
     * Hidrata estado de hoy, conteo de alumnos y horarios ÚNICAMENTE para el lote visible.
     */
    private function hidratarSecciones(array $secciones): array
    {
        if (empty($secciones)) {
            return [];
        }

        $db = \Config\Database::connect();
        $estadoModel = new EstadoClaseModel();
        $ids = array_column($secciones, 'id');

        $estadosHoyMapa = [];
        $estadosRaw = $estadoModel->whereIn('seccion_id', $ids)
            ->where('fecha', date('Y-m-d'))
            ->orderBy('id', 'DESC')
            ->findAll();
        foreach ($estadosRaw as $est) {
            $sId = (int) $est['seccion_id'];
            if (!isset($estadosHoyMapa[$sId])) {
                $estadosHoyMapa[$sId] = $est;
            }
        }

        $alumnosMapa = [];
        $inscRaw = $db->table('inscripciones')
            ->select('seccion_id, COUNT(*) as total')
            ->whereIn('seccion_id', $ids)
            ->groupBy('seccion_id')
            ->get()
            ->getResultArray();
        foreach ($inscRaw as $row) {
            $alumnosMapa[(int) $row['seccion_id']] = (int) $row['total'];
        }

        $horariosMapa = [];
        $horRaw = $db->table('clases_programadas')
            ->whereIn('seccion_id', $ids)
            ->where('estudiante_id', null)
            ->get()
            ->getResultArray();
        foreach ($horRaw as $h) {
            $horariosMapa[(int) $h['seccion_id']][] = $h;
        }

        foreach ($secciones as &$sec) {
            $sId = (int) $sec['id'];
            $sec['estado_hoy']        = $estadosHoyMapa[$sId] ?? null;
            $sec['total_estudiantes'] = $alumnosMapa[$sId] ?? 0;
            $sec['horarios']          = $horariosMapa[$sId] ?? [];
        }
        unset($sec);

        return $secciones;
    }
}
