<?php

namespace App\Models;

use CodeIgniter\Model;

class SolicitudExtemporaneaModel extends Model
{
    protected $table            = 'solicitudes_extemporaneas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'estudiante_id',
        'seccion_id',
        'profesor_id',
        'estado',
        'motivo_rechazo',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Obtiene los IDs de las secciones donde el estudiante tiene una solicitud en estado pendiente.
     */
    public function obtenerIdsSolicitudesPendientesEstudiante(int $estudianteId): array
    {
        $rows = $this->select('seccion_id')
            ->where('estudiante_id', $estudianteId)
            ->where('estado', 'pendiente')
            ->findAll();

        return !empty($rows) ? array_map('intval', array_column($rows, 'seccion_id')) : [];
    }

    /**
     * Filtra las solicitudes visibles para un docente según el titular actual de la sección.
     */
    private function aplicarFiltroDocente($builder, int $profesorId): void
    {
        $builder->groupStart()
            ->where('secciones.profesor_id', $profesorId)
            ->orGroupStart()
                ->where('secciones.profesor_id', null)
                ->where('solicitudes_extemporaneas.profesor_id', $profesorId)
            ->groupEnd()
            ->groupEnd();
    }

    /**
     * Obtiene las solicitudes pendientes para las secciones asignadas al profesor (o todas si es admin).
     */
    public function obtenerPendientesParaDocente(int $profesorId, bool $esAdmin = false): array
    {
        $builder = $this->db->table($this->table)
            ->select('solicitudes_extemporaneas.*, 
                      usuarios.primer_nombre, usuarios.segundo_nombre, usuarios.apellidos, usuarios.cedula, usuarios.carrera as estudiante_carrera, usuarios.correo as estudiante_email,
                      materias.nombre as materia, materias.codigo_materia, materias.semestre_trayecto,
                      secciones.codigo_seccion, secciones.carrera as seccion_carrera')
            ->join('usuarios', 'usuarios.id = solicitudes_extemporaneas.estudiante_id')
            ->join('secciones', 'secciones.id = solicitudes_extemporaneas.seccion_id')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->where('solicitudes_extemporaneas.estado', 'pendiente')
            ->orderBy('solicitudes_extemporaneas.created_at', 'DESC');

        if (!$esAdmin) {
            $this->aplicarFiltroDocente($builder, $profesorId);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Cuenta las solicitudes pendientes para el profesor.
     */
    public function contarPendientesParaDocente(int $profesorId, bool $esAdmin = false): int
    {
        $builder = $this->db->table($this->table)
            ->join('secciones', 'secciones.id = solicitudes_extemporaneas.seccion_id')
            ->where('solicitudes_extemporaneas.estado', 'pendiente');

        if (!$esAdmin) {
            $this->aplicarFiltroDocente($builder, $profesorId);
        }

        return $builder->countAllResults();
    }
}
