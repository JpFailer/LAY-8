<?php

namespace App\Models;

use CodeIgniter\Model;

class NoticiaMuroModel extends Model
{
    protected $table            = 'noticias_muro';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'autor_id',
        'titulo',
        'contenido',
        'categoria',
        'fijado',
        'total_likes',
        'total_comentarios'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Obtiene el listado de noticias con autor y si el usuario actual le dio like
     */
    public function obtenerNoticiasConInteraccion(int $usuarioIdActual = 0, int $limit = 30): array
    {
        $db = \Config\Database::connect();
        
        $sql = "SELECT n.*, 
                       u.primer_nombre, 
                       u.segundo_nombre, 
                       u.apellidos, 
                       u.rol as autor_rol, 
                       u.carrera as autor_carrera, 
                       u.foto_perfil,
                       (SELECT COUNT(*) FROM noticias_likes nl WHERE nl.noticia_id = n.id AND nl.usuario_id = ?) as usuario_dio_like
                FROM noticias_muro n
                JOIN usuarios u ON u.id = n.autor_id
                ORDER BY n.fijado DESC, n.created_at DESC
                LIMIT ?";

        return $db->query($sql, [$usuarioIdActual, $limit])->getResultArray();
    }
}
