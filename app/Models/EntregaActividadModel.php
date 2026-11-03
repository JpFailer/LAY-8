<?php

namespace App\Models;

use CodeIgniter\Model;

class EntregaActividadModel extends Model
{
    protected $table            = 'entregas_actividades';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'actividad_id',
        'estudiante_id',
        'archivo_ruta',
        'archivo_nombre',
        'comentario_alumno',
        'calificacion',
        'feedback_profesor',
        'calificado_por',
        'calificado_en',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Obtiene la entrega realizada por un estudiante para una actividad
     */
    public function obtenerEntregaEstudiante(int $actividadId, int $estudianteId): ?array
    {
        return $this->where('actividad_id', $actividadId)
            ->where('estudiante_id', $estudianteId)
            ->first();
    }

    /**
     * Obtiene todas las entregas de una actividad con los datos del alumno (para el profesor)
     */
    public function obtenerEntregasDeActividad(int $actividadId): array
    {
        return $this->select('entregas_actividades.*, usuarios.primer_nombre, usuarios.segundo_nombre, usuarios.apellidos, usuarios.cedula, usuarios.nombre_usuario, usuarios.correo')
            ->join('usuarios', 'usuarios.id = entregas_actividades.estudiante_id')
            ->where('entregas_actividades.actividad_id', $actividadId)
            ->orderBy('entregas_actividades.created_at', 'ASC')
            ->findAll();
    }
}
