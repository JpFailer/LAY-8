<?php

namespace App\Models;

use CodeIgniter\Model;

class AporteBibliotecaModel extends Model
{
    protected $table            = 'aportes_biblioteca';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'usuario_id',
        'titulo',
        'categoria',
        'nombre_original',
        'nombre_guardado',
        'ruta_archivo',
        'tamano_bytes',
        'extension',
        'fecha_subida',
    ];

    /**
     * Obtiene los aportes con información del usuario que los subió
     *
     * @param string|null $categoria
     * @return array
     */
    public function obtenerTodosConUsuario(?string $categoria = null, ?string $busqueda = null)
    {
        $builder = $this->select('aportes_biblioteca.*, usuarios.nombre_usuario, usuarios.nombre_usuario as autor_usuario, usuarios.primer_nombre, usuarios.apellidos, usuarios.correo')
                        ->join('usuarios', 'usuarios.id = aportes_biblioteca.usuario_id', 'left')
                        ->orderBy('aportes_biblioteca.fecha_subida', 'DESC');

        if ($categoria && in_array($categoria, ['guias', 'ejercicios', 'resumenes', 'otros'], true)) {
            $builder->where('aportes_biblioteca.categoria', $categoria);
        }

        if ($busqueda !== null && trim($busqueda) !== '') {
            $term = trim($busqueda);
            $builder->groupStart()
                    ->like('aportes_biblioteca.titulo', $term)
                    ->orLike('aportes_biblioteca.nombre_original', $term)
                    ->orLike('usuarios.primer_nombre', $term)
                    ->orLike('usuarios.apellidos', $term)
                    ->orLike('usuarios.nombre_usuario', $term)
                    ->groupEnd();
        }

        return $builder->findAll();
    }

    /**
     * Calcula el espacio total en bytes consumido por los aportes
     */
    public function espacioTotalBytes(): int
    {
        $resultado = $this->selectSum('tamano_bytes', 'total')->first();
        return (int) ($resultado['total'] ?? 0);
    }
}
