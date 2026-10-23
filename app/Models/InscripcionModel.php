<?php

namespace App\Models;

use CodeIgniter\Model;

class InscripcionModel extends Model
{
    protected $table            = 'inscripciones';
    protected $primaryKey       = 'estudiante_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields    = [
        'estudiante_id',
        'seccion_id'
    ];

    // Obtener los IDs de todos los estudiantes inscritos en una sección dada
    public function obtenerEstudiantesDeSeccion(int $seccionId): array
    {
        return $this->select('estudiante_id')
                    ->where('seccion_id', $seccionId)
                    ->findAll();
    }

    // Obtener las secciones en las que un estudiante está inscrito
    public function obtenerSeccionesDeEstudiante(int $estudianteId): array
    {
        return $this->select('secciones.*, materias.nombre as materia, materias.codigo_materia')
                    ->join('secciones', 'secciones.id = inscripciones.seccion_id')
                    ->join('materias', 'materias.id = secciones.materia_id')
                    ->where('inscripciones.estudiante_id', $estudianteId)
                    ->findAll();
    }
}
