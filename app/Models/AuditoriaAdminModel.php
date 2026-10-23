<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditoriaAdminModel extends Model
{
    protected $table            = 'auditoria_admin';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'admin_id',
        'accion',
        'entidad',
        'entidad_id',
        'detalles',
        'ip',
        'created_at',
    ];

    /**
     * Registra una acción administrativa para auditoría inmutable
     *
     * @param int         $adminId
     * @param string      $accion
     * @param string|null $entidad
     * @param int|null    $entidadId
     * @param array       $detalles
     * @param string|null $ip
     * @return int|bool ID del registro insertado o false
     */
    public function registrar(int $adminId, string $accion, ?string $entidad = null, ?int $entidadId = null, array $detalles = [], ?string $ip = null)
    {
        return $this->insert([
            'admin_id'   => $adminId,
            'accion'     => $accion,
            'entidad'    => $entidad,
            'entidad_id' => $entidadId,
            'detalles'   => !empty($detalles) ? json_encode($detalles, JSON_UNESCAPED_UNICODE) : null,
            'ip'         => $ip ?? service('request')->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Obtiene los últimos registros de auditoría con datos del administrador
     *
     * @param int $limite
     * @return array
     */
    public function ultimosRegistros(int $limite = 100): array
    {
        return $this->select('auditoria_admin.*, usuarios.nombre_usuario as admin_username, usuarios.primer_nombre as admin_nombre, usuarios.apellidos as admin_apellido')
                    ->join('usuarios', 'usuarios.id = auditoria_admin.admin_id', 'left')
                    ->orderBy('auditoria_admin.created_at', 'DESC')
                    ->limit($limite)
                    ->findAll();
    }
}
