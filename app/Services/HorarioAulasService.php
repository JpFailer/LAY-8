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
    public function obtenerDatosVistaAulas(int $usuarioId, array $bloquesOficiales): array
    {
        $db               = \Config\Database::connect();
        $inscripcionModel = new InscripcionModel();
        $claseModel       = new ClaseProgramadaModel();
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

        $seccionesIds = !empty($secciones) ? array_column($secciones, 'id') : [];

        // 2. Batch loading (O(1) en memoria)
        $estadosHoyMapa = [];
        $horariosPorSeccion = [];
        $actividadesConteoMapa = [];

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

            $horariosRaw = $claseModel
                ->whereIn('seccion_id', $seccionesIds)
                ->groupStart()
                    ->where('estudiante_id', null)
                    ->orWhere('estudiante_id', $usuarioId)
                ->groupEnd()
                ->orderBy('hora_inicio', 'ASC')
                ->findAll();
            foreach ($horariosRaw as $h) {
                $horariosPorSeccion[(int) $h['seccion_id']][] = $h;
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
        }

        foreach ($secciones as &$sec) {
            $secId = (int) $sec['id'];
            $sec['estado_hoy']        = $estadosHoyMapa[$secId] ?? null;
            $sec['horarios']          = $horariosPorSeccion[$secId] ?? [];
            $sec['total_actividades'] = $actividadesConteoMapa[$secId] ?? 0;
        }
        unset($sec);

        // 3. Horario Semanal completo (excluyendo bloques descartados personalmente)
        $horarioSemanal = [];
        if (!empty($seccionesIds)) {
            $bloquesExcluidos = $db->table('estudiante_bloques_excluidos')
                ->select('clase_id')
                ->where('estudiante_id', $usuarioId)
                ->get()
                ->getResultArray();
            $idsExcluidos = !empty($bloquesExcluidos) ? array_column($bloquesExcluidos, 'clase_id') : [];

            $builderClases = $claseModel
                ->select('clases_programadas.*, materias.nombre as materia, materias.codigo_materia, secciones.codigo_seccion')
                ->join('secciones', 'secciones.id = clases_programadas.seccion_id')
                ->join('materias', 'materias.id = secciones.materia_id')
                ->whereIn('clases_programadas.seccion_id', $seccionesIds)
                ->groupStart()
                    ->where('clases_programadas.estudiante_id', null)
                    ->orWhere('clases_programadas.estudiante_id', $usuarioId)
                ->groupEnd();

            if (!empty($idsExcluidos)) {
                $builderClases->whereNotIn('clases_programadas.id', $idsExcluidos);
            }

            $horarioSemanal = $builderClases->findAll();
        }

        // 4. Matriz semanal [Dia]
        $diasSemana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
        $grillaHorarios = array_fill_keys($diasSemana, []);

        foreach ($horarioSemanal as $bloque) {
            $dia = $bloque['dia_semana'];
            if (isset($grillaHorarios[$dia])) {
                $inicioFormato = date('H:i', strtotime($bloque['hora_inicio']));
                $finFormato    = date('H:i', strtotime($bloque['hora_fin']));
                $grillaHorarios[$dia][] = [
                    'id'          => $bloque['id'],
                    'seccion_id'  => $bloque['seccion_id'],
                    'materia'     => $bloque['materia'],
                    'codigo'      => $bloque['codigo_materia'],
                    'seccion'     => $bloque['codigo_seccion'],
                    'aula'        => $bloque['aula'],
                    'hora_inicio' => $bloque['hora_inicio'],
                    'hora_fin'    => $bloque['hora_fin'],
                    'intervalo'   => $inicioFormato . ' - ' . $finFormato,
                    'es_personal' => true,
                ];
            }
        }

        $actividades = $actividadModel->obtenerParaEstudiante($usuarioId);

        return [
            'secciones'        => $secciones,
            'totalMaterias'    => count($secciones),
            'horarioSemanal'   => $horarioSemanal,
            'grillaHorarios'   => $grillaHorarios,
            'actividades'      => $actividades,
            'bloquesOficiales' => $bloquesOficiales,
            'diasSemana'       => $diasSemana,
        ];
    }
}
