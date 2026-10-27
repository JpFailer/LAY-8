<?php

namespace App\Models;

use CodeIgniter\Model;

class NoticiaLikeModel extends Model
{
    protected $table            = 'noticias_likes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'noticia_id',
        'usuario_id',
        'created_at'
    ];

    protected $useTimestamps = false;

    /**
     * Alterna (toggle) el like de un usuario en una noticia
     */
    public function toggleLike(int $noticiaId, int $usuarioId): array
    {
        $db = \Config\Database::connect();
        
        $existente = $this->where('noticia_id', $noticiaId)
                          ->where('usuario_id', $usuarioId)
                          ->first();

        $noticiaModel = new NoticiaMuroModel();
        $noticia = $noticiaModel->find($noticiaId);

        if (!$noticia) {
            return ['error' => 'La noticia no existe.'];
        }

        $dioLike = false;

        if ($existente) {
            // Quitar Like
            $this->delete($existente['id']);
            $db->query("UPDATE noticias_muro SET total_likes = GREATEST(0, total_likes - 1) WHERE id = ?", [$noticiaId]);
            $dioLike = false;
        } else {
            // Dar Like
            $this->insert([
                'noticia_id' => $noticiaId,
                'usuario_id' => $usuarioId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $db->query("UPDATE noticias_muro SET total_likes = total_likes + 1 WHERE id = ?", [$noticiaId]);
            $dioLike = true;

            // Notificar al autor si no es él mismo
            if ((int)$noticia['autor_id'] !== $usuarioId) {
                $usuarioModel = new UsuarioModel();
                $usuarioQuienDioLike = $usuarioModel->find($usuarioId);
                $nombreLiker = $usuarioQuienDioLike ? trim($usuarioQuienDioLike['primer_nombre'] . ' ' . $usuarioQuienDioLike['apellidos']) : 'Un usuario';

                $notifModel = new NotificacionModel();
                $notifModel->insert([
                    'estudiante_id'  => (int)$noticia['autor_id'],
                    'profesor_id'    => $usuarioId,
                    'seccion_id'     => null,
                    'tipo'           => 'like',
                    'titulo'         => 'Nuevo me gusta en tu publicación',
                    'mensaje'        => "{$nombreLiker} indicó que le gusta tu comunicado: \"{$noticia['titulo']}\".",
                    'modalidad'      => 'presencial',
                    'enlace_accion'  => 'dashboard#noticia-' . $noticiaId,
                    'leido'          => 0,
                    'created_at'     => date('Y-m-d H:i:s')
                ]);
            }
        }

        $totalActual = (int) $db->table('noticias_muro')->select('total_likes')->where('id', $noticiaId)->get()->getRow('total_likes');

        return [
            'success'     => true,
            'liked'       => $dioLike,
            'total_likes' => $totalActual
        ];
    }
}
