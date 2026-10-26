<?php

namespace App\Models;

use CodeIgniter\Model;

class ActividadClaseModel extends Model
{
    protected $table            = 'actividades_clase';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'seccion_id',
        'creado_por',
        'es_personal',
        'titulo',
        'descripcion',
        'tipo',
        'fecha_entrega',
        'ponderacion',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Obtiene todas las actividades (generales y personales) de un estudiante
     * cruzadas con su estado de completitud (Checklist)
     */
    public function obtenerParaEstudiante(int $estudianteId): array
    {
        $db = \Config\Database::connect();

        // 1. Obtener los IDs de las secciones donde está inscrito el alumno
        $seccionesInscritas = $db->table('inscripciones')
            ->select('seccion_id')
            ->where('estudiante_id', $estudianteId)
            ->get()
            ->getResultArray();

        $seccionesIds = !empty($seccionesInscritas) ? array_column($seccionesInscritas, 'seccion_id') : [];

        $builder = $this->select('actividades_clase.*, materias.nombre as materia_nombre, materias.codigo_materia, secciones.codigo_seccion, IFNULL(actividades_estudiante_estado.completada, 0) as completada, actividades_estudiante_estado.fecha_completada')
            ->join('secciones', 'secciones.id = actividades_clase.seccion_id')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->join('actividades_estudiante_estado', 'actividades_estudiante_estado.actividad_id = actividades_clase.id AND actividades_estudiante_estado.estudiante_id = ' . (int)$estudianteId, 'left')
            ->orderBy('actividades_clase.fecha_entrega', 'ASC');

        if (!empty($seccionesIds)) {
            $builder->groupStart()
                ->whereIn('actividades_clase.seccion_id', $seccionesIds)
                ->orGroupStart()
                    ->where('actividades_clase.es_personal', 1)
                    ->where('actividades_clase.creado_por', $estudianteId)
                ->groupEnd()
            ->groupEnd();
        } else {
            $builder->where('actividades_clase.es_personal', 1)
                    ->where('actividades_clase.creado_por', $estudianteId);
        }

        return $builder->findAll();
    }

    /**
     * Alterna o establece el estado de completada de una actividad para un estudiante
     */
    public function toggleEstadoEstudiante(int $actividadId, int $estudianteId): bool
    {
        $db = \Config\Database::connect();
        $registro = $db->table('actividades_estudiante_estado')
            ->where('actividad_id', $actividadId)
            ->where('estudiante_id', $estudianteId)
            ->get()
            ->getRowArray();

        if ($registro) {
            $nuevoEstado = $registro['completada'] ? 0 : 1;
            return $db->table('actividades_estudiante_estado')
                ->where('id', $registro['id'])
                ->update([
                    'completada'       => $nuevoEstado,
                    'fecha_completada' => $nuevoEstado ? date('Y-m-d H:i:s') : null,
                ]);
        } else {
            return $db->table('actividades_estudiante_estado')->insert([
                'actividad_id'     => $actividadId,
                'estudiante_id'    => $estudianteId,
                'completada'       => 1,
                'fecha_completada' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
