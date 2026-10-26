<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table            = 'usuarios';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'primer_nombre',
        'segundo_nombre',
        'apellidos',
        'nombre_usuario',
        'correo',
        'telefono',
        'contrasena',
        'rol',
        'carrera',
        'estado_verificacion',
        'ruta_carnet',
        'fecha_registro',
        'estado_cuenta',
        'motivo_baneo',
        'baneado_en',
        'ultimo_acceso',
        'foto_perfil'
    ];

    /**
     * Obtiene usuarios con filtros para el panel de administración
     *
     * @param array $filtros
     * @param int   $limite
     * @param int   $offset
     * @return array
     */
    public function obtenerUsuariosFiltrados(array $filtros = [], int $limite = 20, int $offset = 0): array
    {
        $builder = $this->select('id, primer_nombre, segundo_nombre, apellidos, nombre_usuario, correo, telefono, rol, carrera, estado_verificacion, ruta_carnet, estado_cuenta, motivo_baneo, baneado_en, fecha_registro, ultimo_acceso, foto_perfil');

        if (!empty($filtros['buscar'])) {
            $termino = trim($filtros['buscar']);
            $builder->groupStart()
                    ->like('nombre_usuario', $termino)
                    ->orLike('correo', $termino)
                    ->orLike('primer_nombre', $termino)
                    ->orLike('apellidos', $termino)
                    ->groupEnd();
        }

        if (!empty($filtros['rol']) && in_array($filtros['rol'], ['estudiante', 'profesor', 'administrador'], true)) {
            $builder->where('rol', $filtros['rol']);
        }

        if (!empty($filtros['estado_cuenta']) && in_array($filtros['estado_cuenta'], ['activo', 'baneado'], true)) {
            $builder->where('estado_cuenta', $filtros['estado_cuenta']);
        }

        if (!empty($filtros['estado_verificacion'])) {
            $builder->where('estado_verificacion', $filtros['estado_verificacion']);
        }

        return $builder->orderBy('id', 'DESC')
                       ->findAll($limite, $offset);
    }

    /**
     * Cuenta total de usuarios según filtros
     *
     * @param array $filtros
     * @return int
     */
    public function contarUsuariosFiltrados(array $filtros = []): int
    {
        $builder = $this->builder();

        if (!empty($filtros['buscar'])) {
            $termino = trim($filtros['buscar']);
            $builder->groupStart()
                    ->like('nombre_usuario', $termino)
                    ->orLike('correo', $termino)
                    ->orLike('primer_nombre', $termino)
                    ->orLike('apellidos', $termino)
                    ->groupEnd();
        }

        if (!empty($filtros['rol']) && in_array($filtros['rol'], ['estudiante', 'profesor', 'administrador'], true)) {
            $builder->where('rol', $filtros['rol']);
        }

        if (!empty($filtros['estado_cuenta']) && in_array($filtros['estado_cuenta'], ['activo', 'baneado'], true)) {
            $builder->where('estado_cuenta', $filtros['estado_cuenta']);
        }

        if (!empty($filtros['estado_verificacion'])) {
            $builder->where('estado_verificacion', $filtros['estado_verificacion']);
        }

        return $builder->countAllResults();
    }
}
