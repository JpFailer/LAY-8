<?php

namespace App\Models;

use CodeIgniter\Model;

class EstadoClaseModel extends Model
{
    protected $table            = 'estados_clase_profesor';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'profesor_id',
        'seccion_id',
        'modalidad',
        'enlace_virtual',
        'estado_rapido',
        'mensaje_adicional',
        'fecha',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Obtener estado activo de hoy para una sección específica
    public function obtenerEstadoHoy(int $seccionId)
    {
        return $this->where('seccion_id', $seccionId)
                    ->where('fecha', date('Y-m-d'))
                    ->orderBy('id', 'DESC')
                    ->first();
    }
}
