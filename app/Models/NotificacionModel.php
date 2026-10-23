<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificacionModel extends Model
{
    protected $table            = 'notificaciones';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'estudiante_id',
        'profesor_id',
        'seccion_id',
        'tipo',
        'titulo',
        'mensaje',
        'modalidad',
        'enlace_virtual',
        'leido',
        'created_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    // Row-Level Security: Solo obtiene las notificaciones que pertenezcan al estudiante
    public function obtenerParaEstudiante(int $estudianteId, int $limite = 20)
    {
        return $this->where('estudiante_id', $estudianteId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll($limite);
    }

    public function contarNoLeidas(int $estudianteId): int
    {
        return $this->where('estudiante_id', $estudianteId)
                    ->where('leido', 0)
                    ->countAllResults();
    }

    // Row-Level Security: Solo marca leída si el ID pertenece al estudiante
    public function marcarComoLeida(int $notificacionId, int $estudianteId): bool
    {
        return $this->where('id', $notificacionId)
                    ->where('estudiante_id', $estudianteId)
                    ->set(['leido' => 1])
                    ->update();
    }
}
