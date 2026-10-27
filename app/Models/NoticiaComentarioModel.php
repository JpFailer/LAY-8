<?php

namespace App\Models;

use CodeIgniter\Model;

class NoticiaComentarioModel extends Model
{
    protected $table            = 'noticias_comentarios';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'noticia_id',
        'usuario_id',
        'parent_id',
        'contenido'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Obtiene los comentarios de una noticia organizados en árbol jerárquico (estilo Reddit)
     */
    public function obtenerHilosPorNoticia(int $noticiaId): array
    {
        $db = \Config\Database::connect();
        
        $sql = "SELECT c.*, 
                       u.primer_nombre, 
                       u.segundo_nombre, 
                       u.apellidos, 
                       u.rol, 
                       u.carrera, 
                       u.foto_perfil
                FROM noticias_comentarios c
                JOIN usuarios u ON u.id = c.usuario_id
                WHERE c.noticia_id = ?
                ORDER BY c.created_at ASC";

        $todos = $db->query($sql, [$noticiaId])->getResultArray();

        // Organizar en estructura de hilos
        $mapa = [];
        $raices = [];

        foreach ($todos as $c) {
            $c['respuestas'] = [];
            $mapa[$c['id']] = $c;
        }

        foreach ($mapa as $id => &$comentario) {
            if (!empty($comentario['parent_id']) && isset($mapa[$comentario['parent_id']])) {
                $mapa[$comentario['parent_id']]['respuestas'][] = &$comentario;
            } else {
                $raices[] = &$comentario;
            }
        }
        unset($comentario);

        return $raices;
    }

    /**
     * Agrega un nuevo comentario o respuesta y despacha las notificaciones
     */
    public function agregarComentario(int $noticiaId, int $usuarioId, string $contenido, ?int $parentId = null): array
    {
        $contenido = trim($contenido);
        if (empty($contenido)) {
            return ['error' => 'El comentario no puede estar vacío.'];
        }

        $noticiaModel = new NoticiaMuroModel();
        $noticia = $noticiaModel->find($noticiaId);
        if (!$noticia) {
            return ['error' => 'La noticia no existe.'];
        }

        $comentarioId = $this->insert([
            'noticia_id' => $noticiaId,
            'usuario_id' => $usuarioId,
            'parent_id'  => ($parentId && $parentId > 0) ? $parentId : null,
            'contenido'  => $contenido,
        ], true);

        if (!$comentarioId) {
            return ['error' => 'No se pudo guardar el comentario.'];
        }

        // Actualizar contador en la noticia
        $db = \Config\Database::connect();
        $db->query("UPDATE noticias_muro SET total_comentarios = total_comentarios + 1 WHERE id = ?", [$noticiaId]);

        // Datos del comentarista
        $usuarioModel = new UsuarioModel();
        $comentarista = $usuarioModel->find($usuarioId);
        $nombreComentarista = $comentarista ? trim($comentarista['primer_nombre'] . ' ' . $comentarista['apellidos']) : 'Un usuario';

        $notifModel = new NotificacionModel();

        // Notificación 1: Si es una respuesta a otro comentario (Reddit-style reply)
        if ($parentId && $parentId > 0) {
            $comentarioPadre = $this->find($parentId);
            if ($comentarioPadre && (int)$comentarioPadre['usuario_id'] !== $usuarioId) {
                $notifModel->insert([
                    'estudiante_id'  => (int)$comentarioPadre['usuario_id'],
                    'profesor_id'    => $usuarioId,
                    'seccion_id'     => null,
                    'tipo'           => 'respuesta',
                    'titulo'         => 'Nueva respuesta a tu comentario',
                    'mensaje'        => "{$nombreComentarista} respondió a tu comentario en: \"{$noticia['titulo']}\".",
                    'modalidad'      => 'presencial',
                    'enlace_accion'  => 'dashboard#noticia-' . $noticiaId,
                    'leido'          => 0,
                    'created_at'     => date('Y-m-d H:i:s')
                ]);
            }
        } 
        
        // Notificación 2: Notificar al autor de la publicación si no es él mismo
        if ((int)$noticia['autor_id'] !== $usuarioId) {
            $notifModel->insert([
                'estudiante_id'  => (int)$noticia['autor_id'],
                'profesor_id'    => $usuarioId,
                'seccion_id'     => null,
                'tipo'           => 'comentario',
                'titulo'         => 'Nuevo comentario en tu comunicado',
                'mensaje'        => "{$nombreComentarista} comentó en tu publicación: \"{$noticia['titulo']}\".",
                'modalidad'      => 'presencial',
                'enlace_accion'  => 'dashboard#noticia-' . $noticiaId,
                'leido'          => 0,
                'created_at'     => date('Y-m-d H:i:s')
            ]);
        }

        $totalComentariosActual = (int) $db->table('noticias_muro')->select('total_comentarios')->where('id', $noticiaId)->get()->getRow('total_comentarios');

        return [
            'success'           => true,
            'comentario_id'     => $comentarioId,
            'total_comentarios' => $totalComentariosActual,
            'comentario'        => [
                'id'            => $comentarioId,
                'noticia_id'    => $noticiaId,
                'usuario_id'    => $usuarioId,
                'parent_id'     => $parentId,
                'contenido'     => esc($contenido),
                'primer_nombre' => $comentarista['primer_nombre'] ?? 'Usuario',
                'apellidos'     => $comentarista['apellidos'] ?? '',
                'rol'           => $comentarista['rol'] ?? 'estudiante',
                'foto_perfil'   => $comentarista['foto_perfil'] ?? null,
                'created_at'    => 'Justo ahora',
                'respuestas'    => []
            ]
        ];
    }
}
