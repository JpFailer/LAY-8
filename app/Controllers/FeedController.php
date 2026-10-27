<?php

namespace App\Controllers;

use App\Models\NoticiaMuroModel;
use App\Models\NoticiaLikeModel;
use App\Models\NoticiaComentarioModel;
use App\Models\NotificacionModel;
use App\Models\UsuarioModel;

class FeedController extends BaseController
{
    /**
     * Publicar un comunicado / noticia en el Tablón de la UMC
     * Solo Administradores y Profesores pueden publicar comunicados oficiales
     */
    public function publicar()
    {
        $usuarioId = (int) session()->get('usuario_id');
        $rol       = session()->get('rol') ?? 'estudiante';

        if (!in_array($rol, ['administrador', 'admin', 'profesor'], true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'mensaje' => 'Solo docentes y personal administrativo tienen permisos para emitir comunicados oficiales.'
            ]);
        }

        $titulo    = strip_tags(trim($this->request->getPost('titulo') ?? ''));
        $contenido = strip_tags(trim($this->request->getPost('contenido') ?? ''));
        $categoria = trim($this->request->getPost('categoria') ?? 'Institucional');
        $fijado    = ($this->request->getPost('fijado') === '1' && in_array($rol, ['administrador', 'admin'], true)) ? 1 : 0;

        if (empty($titulo) || empty($contenido)) {
            return $this->response->setJSON([
                'success' => false,
                'mensaje' => 'El título y el contenido son obligatorios.'
            ]);
        }

        $noticiaModel = new NoticiaMuroModel();
        $noticiaId = $noticiaModel->insert([
            'autor_id'          => $usuarioId,
            'titulo'            => $titulo,
            'contenido'         => $contenido,
            'categoria'         => $categoria,
            'fijado'            => $fijado,
            'total_likes'       => 0,
            'total_comentarios' => 0
        ], true);

        if ($noticiaId) {
            return $this->response->setJSON([
                'success'    => true,
                'mensaje'    => 'Comunicado publicado exitosamente en el tablón universitario.',
                'noticia_id' => $noticiaId
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'mensaje' => 'No se pudo guardar la publicación.'
        ]);
    }

    /**
     * Alternar Me Gusta (Like/Unlike)
     */
    public function toggleLike($noticiaId)
    {
        $usuarioId = (int) session()->get('usuario_id');
        if ($usuarioId <= 0) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'mensaje' => 'Debes iniciar sesión.']);
        }

        $likeModel = new NoticiaLikeModel();
        $res = $likeModel->toggleLike((int) $noticiaId, $usuarioId);

        return $this->response->setJSON($res);
    }

    /**
     * Comentar o Responder en hilo (Reddit style)
     */
    public function comentar($noticiaId)
    {
        $usuarioId = (int) session()->get('usuario_id');
        if ($usuarioId <= 0) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'mensaje' => 'Debes iniciar sesión.']);
        }

        $contenido = $this->request->getPost('contenido');
        $parentId  = $this->request->getPost('parent_id') ? (int)$this->request->getPost('parent_id') : null;

        $comentarioModel = new NoticiaComentarioModel();
        $res = $comentarioModel->agregarComentario((int)$noticiaId, $usuarioId, (string)$contenido, $parentId);

        return $this->response->setJSON($res);
    }

    /**
     * Obtener comentarios de una noticia para renderizado dinámico
     */
    public function comentarios($noticiaId)
    {
        $comentarioModel = new NoticiaComentarioModel();
        $hilos = $comentarioModel->obtenerHilosPorNoticia((int) $noticiaId);

        return $this->response->setJSON([
            'success' => true,
            'hilos'   => $hilos
        ]);
    }

    /**
     * Eliminar publicación (autor o administrador)
     */
    public function eliminar($noticiaId)
    {
        $usuarioId = (int) session()->get('usuario_id');
        $rol       = session()->get('rol') ?? 'estudiante';

        $noticiaModel = new NoticiaMuroModel();
        $noticia = $noticiaModel->find((int) $noticiaId);

        if (!$noticia) {
            return $this->response->setJSON(['success' => false, 'mensaje' => 'La noticia no existe.']);
        }

        if ((int)$noticia['autor_id'] !== $usuarioId && !in_array($rol, ['administrador', 'admin'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'mensaje' => 'No tienes permisos para eliminar este comunicado.']);
        }

        $noticiaModel->delete($noticiaId);
        (new NoticiaLikeModel())->where('noticia_id', $noticiaId)->delete();
        (new NoticiaComentarioModel())->where('noticia_id', $noticiaId)->delete();

        return $this->response->setJSON(['success' => true, 'mensaje' => 'Publicación eliminada correctamente.']);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function marcarTodasNotificaciones()
    {
        $usuarioId = (int) session()->get('usuario_id');
        if ($usuarioId <= 0) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false]);
        }

        $notifModel = new NotificacionModel();
        $notifModel->marcarTodasComoLeidas($usuarioId);

        return $this->response->setJSON([
            'success' => true,
            'mensaje' => 'Todas las notificaciones han sido marcadas como leídas.'
        ]);
    }
}
