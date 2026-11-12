<?php

namespace App\Services;

use App\Models\ActividadClaseModel;
use App\Models\EntregaActividadModel;
use App\Models\InscripcionModel;
use App\Models\NotificacionModel;
use App\Models\SeccionModel;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Servicio de dominio para gestión de actividades académicas, entregas y calificaciones.
 */
class ActividadService
{
    public const TIPOS_VALIDOS = ['tarea', 'parcial', 'taller', 'proyecto', 'lectura'];
    public const EXT_ENTREGAS  = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'rar', '7z', 'png', 'jpg', 'jpeg'];

    /**
     * Registra o actualiza la entrega de una actividad por parte de un estudiante.
     */
    public function registrarEntregaEstudiante(
        int $actividadId,
        int $usuarioId,
        string $rol,
        ?UploadedFile $archivo,
        string $comentario
    ): array {
        $actividad = (new ActividadClaseModel())->find($actividadId);
        if (!$actividad) {
            return ['ok' => false, 'status' => 404, 'error' => 'Actividad no encontrada.'];
        }

        $db = \Config\Database::connect();
        if ((int) $actividad['es_personal'] === 1) {
            if ((int) $actividad['creado_por'] !== $usuarioId) {
                return ['ok' => false, 'status' => 403, 'error' => 'No autorizado.'];
            }
        } else {
            $estaInscrito = $db->table('inscripciones')
                ->where('estudiante_id', $usuarioId)
                ->where('seccion_id', $actividad['seccion_id'])
                ->countAllResults() > 0;
            if (!$estaInscrito && !in_array($rol, ['administrador', 'admin'], true)) {
                return ['ok' => false, 'status' => 403, 'error' => 'Solo puedes subir entregas en asignaturas donde estés formalmente inscrito.'];
            }
        }

        $entregaModel = new EntregaActividadModel();
        $existente = $entregaModel->obtenerEntregaEstudiante($actividadId, $usuarioId);

        if ($existente && isset($existente['calificacion']) && $existente['calificacion'] !== '' && $existente['calificacion'] !== null) {
            return [
                'ok'     => false,
                'status' => 403,
                'error'  => 'Esta tarea ya fue evaluada y calificada por el profesor; no se admiten cambios en la entrega.',
            ];
        }

        $rutaDestino = null;
        $nombreOriginal = null;

        if ($archivo && $archivo->getError() !== UPLOAD_ERR_NO_FILE && !$archivo->isValid()) {
            return ['ok' => false, 'status' => 400, 'error' => 'No se pudo procesar el archivo adjunto: ' . $archivo->getErrorString()];
        }

        if ($archivo && $archivo->isValid() && !$archivo->hasMoved()) {
            $ext = strtolower($archivo->getClientExtension());
            if (!in_array($ext, self::EXT_ENTREGAS, true)) {
                return ['ok' => false, 'status' => 400, 'error' => 'Tipo de archivo no permitido. Solo se aceptan documentos PDF, Office, imágenes o archivos comprimidos.'];
            }
            if ($archivo->getSize() > 20 * 1024 * 1024) {
                return ['ok' => false, 'status' => 400, 'error' => 'El archivo supera el tamaño máximo permitido de 20 MB.'];
            }

            $nombreOriginal = $archivo->getClientName();
            $nuevoNombre = 'entrega_' . $actividadId . '_' . $usuarioId . '_' . time() . '.' . $ext;
            $uploadDir = WRITEPATH . 'uploads/entregas';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $archivo->move($uploadDir, $nuevoNombre);
            $rutaDestino = 'uploads/entregas/' . $nuevoNombre;
        }

        if (!$existente && !$rutaDestino && $comentario === '') {
            return ['ok' => false, 'status' => 400, 'error' => 'Debes adjuntar un archivo o escribir un comentario para enviar tu entrega.'];
        }

        $datosEntrega = [
            'actividad_id'      => $actividadId,
            'estudiante_id'     => $usuarioId,
            'comentario_alumno' => $comentario,
            'updated_at'        => date('Y-m-d H:i:s'),
        ];
        if ($rutaDestino) {
            $datosEntrega['archivo_ruta']   = $rutaDestino;
            $datosEntrega['archivo_nombre'] = $nombreOriginal;
        }

        if ($existente) {
            $entregaModel->update($existente['id'], $datosEntrega);
        } else {
            $datosEntrega['created_at'] = date('Y-m-d H:i:s');
            $entregaModel->insert($datosEntrega);
        }

        $ahora = date('Y-m-d H:i:s');
        $regEstado = $db->table('actividades_estudiante_estado')
            ->where('actividad_id', $actividadId)
            ->where('estudiante_id', $usuarioId)
            ->get()
            ->getRowArray();

        if ($regEstado) {
            $db->table('actividades_estudiante_estado')
                ->where('id', $regEstado['id'])
                ->update(['completada' => 1, 'fecha_completada' => $ahora]);
        } else {
            $db->table('actividades_estudiante_estado')->insert([
                'actividad_id'     => $actividadId,
                'estudiante_id'    => $usuarioId,
                'completada'       => 1,
                'fecha_completada' => $ahora,
            ]);
        }

        return [
            'ok'      => true,
            'status'  => 200,
            'mensaje' => '¡Entrega guardada exitosamente en el cronograma!',
            'archivo' => $nombreOriginal ?: ($existente['archivo_nombre'] ?? null),
        ];
    }

    /**
     * Crea una actividad académica oficial (docente) o personal (estudiante) validando membresía.
     */
    public function crearActividadEnSeccion(int $usuarioId, string $rol, array $datos, bool $esModoDocente): array
    {
        $seccionId    = (int) ($datos['seccion_id'] ?? 0);
        $titulo       = strip_tags(trim((string) ($datos['titulo'] ?? '')));
        $descripcion  = strip_tags(trim((string) ($datos['descripcion'] ?? '')));
        $tipo         = trim((string) ($datos['tipo'] ?? 'tarea'));
        $fechaEntrega = str_replace('T', ' ', trim((string) ($datos['fecha_entrega'] ?? '')));
        $ponderacion  = isset($datos['ponderacion']) && $datos['ponderacion'] !== '' ? (float) $datos['ponderacion'] : 0.0;

        if ($titulo === '' || $fechaEntrega === '' || $seccionId <= 0) {
            return ['ok' => false, 'status' => 400, 'error' => 'Por favor ingresa el título, la materia y la fecha de entrega de la actividad.'];
        }

        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            $tipo = 'tarea';
        }

        $esAdmin = in_array($rol, ['administrador', 'admin'], true);
        $seccionInfo = null;

        if ($esModoDocente || $rol === 'profesor') {
            $builderSec = (new SeccionModel())
                ->select('secciones.*, materias.nombre as materia')
                ->join('materias', 'materias.id = secciones.materia_id')
                ->where('secciones.id', $seccionId);

            if (!$esAdmin) {
                $builderSec->where('secciones.profesor_id', $usuarioId);
            }

            $seccionInfo = $builderSec->first();
            if (!$seccionInfo) {
                return ['ok' => false, 'status' => 403, 'error' => 'Acceso denegado: No tienes permisos para crear evaluaciones en esta sección.'];
            }
        } else {
            $db = \Config\Database::connect();
            $estaInscrito = $db->table('inscripciones')
                ->where('estudiante_id', $usuarioId)
                ->where('seccion_id', $seccionId)
                ->countAllResults() > 0;
            if (!$estaInscrito && !$esAdmin) {
                return ['ok' => false, 'status' => 403, 'error' => 'Solo puedes crear tareas en materias que tengas formalmente inscritas.'];
            }
        }

        $esPersonal = ($esModoDocente || $rol === 'profesor') ? 0 : 1;
        $actividadId = (int) (new ActividadClaseModel())->insert([
            'seccion_id'    => $seccionId,
            'creado_por'    => $usuarioId,
            'es_personal'   => $esPersonal,
            'titulo'        => $titulo,
            'descripcion'   => $descripcion ?: null,
            'tipo'          => $tipo,
            'fecha_entrega' => $fechaEntrega,
            'ponderacion'   => $ponderacion,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        if ($esPersonal === 0 && $esModoDocente) {
            $alumnos = (new InscripcionModel())->obtenerEstudiantesDeSeccion($seccionId);
            $notifModel = new NotificacionModel();
            $materiaNombre = $seccionInfo['materia'] ?? 'tu asignatura';
            $ahora = date('Y-m-d H:i:s');

            foreach ($alumnos as $al) {
                $notifModel->insert([
                    'estudiante_id'  => (int) $al['estudiante_id'],
                    'profesor_id'    => $usuarioId,
                    'seccion_id'     => $seccionId,
                    'tipo'           => 'tarea_pendiente',
                    'titulo'         => "Nueva {$tipo}: {$titulo} ({$materiaNombre})",
                    'mensaje'        => $descripcion ?: "El profesor ha agendado una nueva actividad para entregar antes del {$fechaEntrega}.",
                    'modalidad'      => 'presencial',
                    'enlace_accion'  => 'aulas?tab=actividades',
                    'leido'          => 0,
                    'created_at'     => $ahora,
                ]);
            }
        }

        return [
            'ok'           => true,
            'status'       => 200,
            'actividad_id' => $actividadId,
            'titulo'       => $titulo,
        ];
    }

    /**
     * Valida si un profesor o administrador puede inspeccionar una actividad de sección.
     */
    public function obtenerActividadParaDocente(int $actividadId, int $profesorId, string $rol): array
    {
        $actividad = (new ActividadClaseModel())->find($actividadId);
        if (!$actividad) {
            return ['ok' => false, 'status' => 404, 'error' => 'Actividad no encontrada.', 'actividad' => null];
        }

        if (!in_array($rol, ['administrador', 'admin'], true)) {
            $seccion = (new SeccionModel())
                ->where('id', $actividad['seccion_id'])
                ->where('profesor_id', $profesorId)
                ->first();
            if (!$seccion) {
                return ['ok' => false, 'status' => 403, 'error' => 'No tienes permisos para acceder a las entregas de esta sección.', 'actividad' => null];
            }
        }

        return ['ok' => true, 'status' => 200, 'error' => null, 'actividad' => $actividad];
    }

    /**
     * Califica la entrega de un estudiante y le notifica automáticamente.
     */
    public function calificarEntregaEstudiante(int $entregaId, int $profesorId, string $rol, $nota, string $feedback): array
    {
        if (!is_numeric($nota) || $nota < 0 || $nota > 100) {
            return ['ok' => false, 'status' => 400, 'error' => 'La calificación debe ser un valor numérico válido (ej: de 0 a 20 o de 0 a 100).'];
        }

        $entregaModel = new EntregaActividadModel();
        $entrega = $entregaModel->find($entregaId);
        if (!$entrega) {
            return ['ok' => false, 'status' => 404, 'error' => 'Entrega no encontrada.'];
        }

        $verif = $this->obtenerActividadParaDocente((int) $entrega['actividad_id'], $profesorId, $rol);
        if (!$verif['ok']) {
            return ['ok' => false, 'status' => $verif['status'], 'error' => $verif['error']];
        }

        $notaFloat = (float) $nota;
        $entregaModel->update($entregaId, [
            'calificacion'      => $notaFloat,
            'feedback_profesor' => $feedback,
            'calificado_por'    => $profesorId,
            'calificado_en'     => date('Y-m-d H:i:s'),
        ]);

        $actividad = $verif['actividad'];
        $tituloAct = $actividad['titulo'] ?? 'Evaluación';

        (new NotificacionModel())->insert([
            'estudiante_id'  => (int) $entrega['estudiante_id'],
            'profesor_id'    => $profesorId,
            'seccion_id'     => (int) ($actividad['seccion_id'] ?? 0),
            'tipo'           => 'alerta_estado',
            'titulo'         => "Calificación publicada: {$tituloAct}",
            'mensaje'        => "Tu entrega ha sido calificada con {$nota} pts." . ($feedback !== '' ? ' Comentario del docente: ' . $feedback : ''),
            'modalidad'      => 'presencial',
            'enlace_accion'  => 'aulas?tab=actividades',
            'leido'          => 0,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        return [
            'ok'           => true,
            'status'       => 200,
            'calificacion' => $notaFloat,
            'feedback'     => $feedback,
            'mensaje'      => 'Calificación y retroalimentación guardadas con éxito.',
        ];
    }
}
