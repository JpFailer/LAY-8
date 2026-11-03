<?php

namespace App\Controllers;

use App\Models\SeccionModel;
use App\Models\EstadoClaseModel;
use App\Models\NotificacionModel;
use App\Models\InscripcionModel;
use App\Models\UsuarioModel;

class ProfesorController extends BaseController
{
    /**
     * Obtiene la lista de clases y secciones asignadas al profesor autenticado
     * Retorna JSON con la materia, sección, horarios y el estado activo de hoy.
     */
    public function misClases()
    {
        $profesorId = (int) session()->get('usuario_id');
        
        $seccionModel = new SeccionModel();
        $estadoModel  = new EstadoClaseModel();

        // Row-Level Security: Consulta filtrada estrictamente por el ID del profesor en sesión
        $clases = $seccionModel
            ->select('secciones.id as seccion_id, secciones.codigo_seccion, secciones.periodo_academico, materias.nombre as materia, materias.codigo_materia')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->where('secciones.profesor_id', $profesorId)
            ->findAll();

        foreach ($clases as &$clase) {
            $clase['estado_hoy'] = $estadoModel->obtenerEstadoHoy((int) $clase['seccion_id']);
        }

        return $this->response->setJSON([
            'success' => true,
            'profesor_id' => $profesorId,
            'total_clases' => count($clases),
            'clases' => $clases
        ]);
    }

    /**
     * Sistema Automatizado de Estatus y Notificaciones
     * El profesor actualiza su estado rápido ("Voy en camino", "Suspendida por lluvia", "En el aula")
     * y su modalidad (Presencial u Online con enlace).
     * El sistema cruza y notifica AUTOMÁTICAMENTE a todos los alumnos inscritos en esa materia.
     */
    public function actualizarEstadoClase()
    {
        $profesorId = (int) session()->get('usuario_id');

        // 1. Validación de inputs estricta del lado del servidor
        $reglas = [
            'seccion_id'        => 'required|is_natural_no_zero',
            'modalidad'         => 'required|in_list[presencial,online]',
            'enlace_virtual'    => 'permit_empty|valid_url',
            'estado_rapido'     => 'required|min_length[3]|max_length[100]',
            'mensaje_adicional' => 'permit_empty|max_length[500]',
        ];

        if (!$this->validate($reglas)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'errores' => $this->validator->getErrors()
            ]);
        }

        $seccionId        = (int) $this->request->getPost('seccion_id');
        $modalidad        = $this->request->getPost('modalidad');
        $enlaceVirtual    = trim((string) $this->request->getPost('enlace_virtual')) ?: null;
        $estadoRapido     = strip_tags(trim((string) $this->request->getPost('estado_rapido')));
        $mensajeAdicional = strip_tags(trim((string) $this->request->getPost('mensaje_adicional'))) ?: null;

        // 2. Row-Level Security / Ownership: Comprobar que esta sección le pertenezca a ESTE profesor
        $seccionModel = new SeccionModel();
        $seccion = $seccionModel
            ->select('secciones.*, materias.nombre as materia')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->where('secciones.id', $seccionId)
            ->where('secciones.profesor_id', $profesorId)
            ->first();

        if (!$seccion) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error'   => 'Acceso denegado: No tienes permisos sobre esta sección o no te pertenece.'
            ]);
        }

        // 3. Guardar o actualizar el estado de hoy
        $estadoModel = new EstadoClaseModel();
        $estadoHoy = $estadoModel->obtenerEstadoHoy($seccionId);

        $datosEstado = [
            'profesor_id'       => $profesorId,
            'seccion_id'        => $seccionId,
            'modalidad'         => $modalidad,
            'enlace_virtual'    => $enlaceVirtual,
            'estado_rapido'     => $estadoRapido,
            'mensaje_adicional' => $mensajeAdicional,
            'fecha'             => date('Y-m-d')
        ];

        if ($estadoHoy) {
            $estadoModel->update($estadoHoy['id'], $datosEstado);
        } else {
            $estadoModel->insert($datosEstado);
        }

        // 4. DISPARADOR DE ALERTAS INTELIGENTES:
        // Buscamos a los estudiantes inscritos exclusivamente en esta materia/sección
        $inscripcionModel = new InscripcionModel();
        $estudiantes = $inscripcionModel->obtenerEstudiantesDeSeccion($seccionId);

        $notificacionModel = new NotificacionModel();
        $notificadosCount = 0;

        $tituloNotificacion = "Actualización de clase: {$seccion['materia']} (Secc. {$seccion['codigo_seccion']})";
        $mensajeNotificacion = "El profesor ha actualizado el estado a: \"{$estadoRapido}\" [Modalidad: " . ucfirst($modalidad) . "].";
        if ($mensajeAdicional) {
            $mensajeNotificacion .= " Mensaje: \"{$mensajeAdicional}\"";
        }

        foreach ($estudiantes as $est) {
            $estudianteId = (int) $est['estudiante_id'];
            $notificacionModel->insert([
                'estudiante_id'  => $estudianteId,
                'profesor_id'    => $profesorId,
                'seccion_id'     => $seccionId,
                'tipo'           => 'alerta_estado',
                'titulo'         => $tituloNotificacion,
                'mensaje'        => $mensajeNotificacion,
                'modalidad'      => $modalidad,
                'enlace_virtual' => $enlaceVirtual,
                'leido'          => 0,
                'created_at'     => date('Y-m-d H:i:s')
            ]);
            $notificadosCount++;
        }

        return $this->response->setJSON([
            'success'               => true,
            'mensaje'               => "Estado actualizado correctamente. Se emitieron alertas a {$notificadosCount} estudiante(s) inscritos.",
            'alumnos_notificados'   => $notificadosCount,
            'estado'                => $datosEstado
        ]);
    }

    /**
     * Emite un aviso de tarea pendiente exclusivamente a los alumnos de esta materia/sección
     */
    public function enviarAvisoTarea()
    {
        $profesorId = (int) session()->get('usuario_id');

        $reglas = [
            'seccion_id'    => 'required|is_natural_no_zero',
            'titulo_tarea'  => 'required|min_length[3]|max_length[150]',
            'descripcion'   => 'required|min_length[5]|max_length[1000]',
            'fecha_entrega' => 'permit_empty|valid_date[Y-m-d]',
        ];

        if (!$this->validate($reglas)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'errores' => $this->validator->getErrors()
            ]);
        }

        $seccionId    = (int) $this->request->getPost('seccion_id');
        $tituloTarea  = strip_tags(trim((string) $this->request->getPost('titulo_tarea')));
        $descripcion  = strip_tags(trim((string) $this->request->getPost('descripcion')));
        $fechaEntrega = $this->request->getPost('fecha_entrega');

        // Validar propiedad de la sección
        $seccionModel = new SeccionModel();
        $seccion = $seccionModel
            ->select('secciones.*, materias.nombre as materia')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->where('secciones.id', $seccionId)
            ->where('secciones.profesor_id', $profesorId)
            ->first();

        if (!$seccion) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error'   => 'Acceso denegado sobre esta sección.'
            ]);
        }

        $inscripcionModel = new InscripcionModel();
        $estudiantes = $inscripcionModel->obtenerEstudiantesDeSeccion($seccionId);

        $notificacionModel = new NotificacionModel();
        $titulo = "Nueva tarea pendiente: {$tituloTarea} - {$seccion['materia']}";
        $mensaje = $descripcion;
        if ($fechaEntrega) {
            $mensaje .= " (Fecha límite de entrega: {$fechaEntrega})";
        }

        $totalNotificados = 0;
        foreach ($estudiantes as $est) {
            $notificacionModel->insert([
                'estudiante_id'  => (int) $est['estudiante_id'],
                'profesor_id'    => $profesorId,
                'seccion_id'     => $seccionId,
                'tipo'           => 'tarea_pendiente',
                'titulo'         => $titulo,
                'mensaje'        => $mensaje,
                'modalidad'      => 'presencial',
                'enlace_virtual' => null,
                'leido'          => 0,
                'created_at'     => date('Y-m-d H:i:s')
            ]);
            $totalNotificados++;
        }

        return $this->response->setJSON([
            'success'             => true,
            'mensaje'             => "Tarea notificada a {$totalNotificados} estudiante(s) inscritos.",
            'alumnos_notificados' => $totalNotificados
        ]);
    }

    /**
     * Módulo Administrativo: Listar solicitudes pendientes de profesores
     */
    public function solicitudesPendientes()
    {
        $usuarioModel = new UsuarioModel();
        $solicitudes = $usuarioModel
            ->select('id, primer_nombre, apellidos, nombre_usuario, correo, telefono, rol, estado_verificacion, ruta_carnet, fecha_registro')
            ->where('estado_verificacion', 'pendiente')
            ->findAll();

        return $this->response->setJSON([
            'success' => true,
            'total'   => count($solicitudes),
            'solicitudes' => $solicitudes
        ]);
    }

    /**
     * Módulo Administrativo: Aprobar profesor
     */
    public function aprobarProfesor(int $id)
    {
        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->find($id);

        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Usuario no encontrado']);
        }

        $usuarioModel->update($id, [
            'rol'                 => 'profesor',
            'estado_verificacion' => 'aprobado'
        ]);

        return $this->response->setJSON([
            'success' => true,
            'mensaje' => "El usuario {$usuario['nombre_usuario']} ha sido verificado como profesor exitosamente."
        ]);
    }

    /**
     * Módulo Administrativo: Rechazar profesor
     */
    public function rechazarProfesor(int $id)
    {
        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->find($id);

        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Usuario no encontrado']);
        }

        $usuarioModel->update($id, [
            'rol'                 => 'estudiante',
            'estado_verificacion' => 'rechazado'
        ]);

        return $this->response->setJSON([
            'success' => true,
            'mensaje' => "La solicitud de profesor para {$usuario['nombre_usuario']} ha sido rechazada."
        ]);
    }

    /**
     * Vista Principal del Panel Docente / Aula Classroom
     */
    public function index()
    {
        $profesorId = (int) session()->get('usuario_id');
        $rol = session()->get('rol');

        if (!$profesorId || !in_array($rol, ['profesor', 'administrador'], true)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Acceso exclusivo para profesores acreditados de la UMC.');
        }

        $db = \Config\Database::connect();
        $seccionModel = new SeccionModel();
        $estadoModel  = new EstadoClaseModel();
        $actividadModel = new \App\Models\ActividadClaseModel();
        $entregaModel = new \App\Models\EntregaActividadModel();

        // 1. Obtener secciones asignadas al profesor (o todas si es admin para supervisión)
        $builderSec = $seccionModel
            ->select('secciones.*, materias.nombre as materia, materias.codigo_materia')
            ->join('materias', 'materias.id = secciones.materia_id');

        if ($rol === 'profesor') {
            $builderSec->where('secciones.profesor_id', $profesorId);
        }

        $secciones = $builderSec->findAll();
        $seccionesIds = !empty($secciones) ? array_column($secciones, 'id') : [];

        // 2. Enriquecer cada sección con el conteo de alumnos y estado en vivo de hoy
        $totalAlumnos = 0;
        foreach ($secciones as &$sec) {
            $sId = (int)$sec['id'];
            $sec['estado_hoy'] = $estadoModel->obtenerEstadoHoy($sId);
            $countAlumnos = $db->table('inscripciones')->where('seccion_id', $sId)->countAllResults();
            $sec['total_estudiantes'] = $countAlumnos;
            $totalAlumnos += $countAlumnos;

            // Horarios oficiales de esta sección
            $sec['horarios'] = $db->table('clases_programadas')
                ->where('seccion_id', $sId)
                ->where('estudiante_id', null)
                ->get()
                ->getResultArray();
        }
        unset($sec);

        // 3. Actividades / Tareas creadas por este profesor o para sus secciones
        $actividades = [];
        $totalEntregasSinCalificar = 0;
        if (!empty($seccionesIds)) {
            $actividades = $actividadModel
                ->select('actividades_clase.*, materias.nombre as materia, materias.codigo_materia, secciones.codigo_seccion')
                ->join('secciones', 'secciones.id = actividades_clase.seccion_id')
                ->join('materias', 'materias.id = secciones.materia_id')
                ->whereIn('actividades_clase.seccion_id', $seccionesIds)
                ->orderBy('actividades_clase.fecha_entrega', 'DESC')
                ->findAll();

            foreach ($actividades as &$act) {
                $aId = (int)$act['id'];
                $act['total_entregas'] = $entregaModel->where('actividad_id', $aId)->countAllResults();
                $sinCalificar = $entregaModel->where('actividad_id', $aId)->where('calificacion', null)->countAllResults();
                $act['sin_calificar'] = $sinCalificar;
                $totalEntregasSinCalificar += $sinCalificar;
            }
            unset($act);
        }

        $datos = [
            'secciones'                 => $secciones,
            'actividades'               => $actividades,
            'totalSecciones'            => count($secciones),
            'totalAlumnos'              => $totalAlumnos,
            'totalActividades'          => count($actividades),
            'totalEntregasSinCalificar' => $totalEntregasSinCalificar,
            'profesorNombre'            => session()->get('primer_nombre') . ' ' . session()->get('apellidos'),
            'rolUsuario'                => $rol
        ];

        return view('Profesor/dashboard_view', $datos);
    }

    /**
     * Permite al profesor publicar una tarea o examen oficial para toda la sección
     */
    public function crearTareaDocente()
    {
        $profesorId = (int) session()->get('usuario_id');
        $rol = session()->get('rol');

        if (!$profesorId || !in_array($rol, ['profesor', 'administrador'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'Acceso denegado.']);
        }

        $reglas = [
            'seccion_id'    => 'required|is_natural_no_zero',
            'titulo'        => 'required|min_length[3]|max_length[150]',
            'tipo'          => 'required|in_list[tarea,parcial,taller,proyecto,lectura]',
            'fecha_entrega' => 'required',
            'ponderacion'   => 'permit_empty|numeric',
            'descripcion'   => 'permit_empty|max_length[2000]',
        ];

        if (!$this->validate($reglas)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'errores' => $this->validator->getErrors()
            ]);
        }

        $seccionId = (int) $this->request->getPost('seccion_id');
        $titulo = strip_tags(trim((string)$this->request->getPost('titulo')));
        $tipo = $this->request->getPost('tipo');
        $fechaEntrega = str_replace('T', ' ', (string)$this->request->getPost('fecha_entrega'));
        $ponderacion = $this->request->getPost('ponderacion') ? (float)$this->request->getPost('ponderacion') : 0;
        $descripcion = strip_tags(trim((string)$this->request->getPost('descripcion')));

        $actividadModel = new \App\Models\ActividadClaseModel();
        $actividadId = $actividadModel->insert([
            'seccion_id'    => $seccionId,
            'creado_por'    => $profesorId,
            'es_personal'   => 0, // Tarea oficial para toda la sección
            'titulo'        => $titulo,
            'tipo'          => $tipo,
            'fecha_entrega' => $fechaEntrega,
            'ponderacion'   => $ponderacion,
            'descripcion'   => $descripcion,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        // Disparar notificaciones a todos los alumnos de la sección
        $inscripcionModel = new InscripcionModel();
        $alumnos = $inscripcionModel->obtenerEstudiantesDeSeccion($seccionId);
        $notifModel = new NotificacionModel();

        $seccionInfo = (new SeccionModel())
            ->select('secciones.*, materias.nombre as materia')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->find($seccionId);

        $materiaNombre = $seccionInfo['materia'] ?? 'tu asignatura';

        foreach ($alumnos as $al) {
            $notifModel->insert([
                'estudiante_id'  => (int)$al['estudiante_id'],
                'profesor_id'    => $profesorId,
                'seccion_id'     => $seccionId,
                'tipo'           => 'tarea_pendiente',
                'titulo'         => "Nueva {$tipo}: {$titulo} ({$materiaNombre})",
                'mensaje'        => $descripcion ?: "El profesor ha agendado una nueva actividad para entregar antes del {$fechaEntrega}.",
                'modalidad'      => 'presencial',
                'leido'          => 0,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->response->setJSON([
            'success'      => true,
            'mensaje'      => "¡Actividad '{$titulo}' creada con éxito y notificada a los estudiantes!",
            'actividad_id' => $actividadId
        ]);
    }

    /**
     * Retorna todas las entregas de los estudiantes para una actividad dada (AJAX)
     */
    public function obtenerEntregasAjax(int $actividadId)
    {
        $profesorId = (int) session()->get('usuario_id');
        $rol = session()->get('rol');

        if (!$profesorId || !in_array($rol, ['profesor', 'administrador'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'Acceso denegado.']);
        }

        $entregaModel = new \App\Models\EntregaActividadModel();
        $entregas = $entregaModel->obtenerEntregasDeActividad($actividadId);

        $actividad = (new \App\Models\ActividadClaseModel())->find($actividadId);

        return $this->response->setJSON([
            'success'   => true,
            'actividad' => $actividad,
            'total'     => count($entregas),
            'entregas'  => $entregas
        ]);
    }

    /**
     * Permite al profesor calificar la entrega de un estudiante y dejar retroalimentación
     */
    public function calificarEntrega()
    {
        $profesorId = (int) session()->get('usuario_id');
        $rol = session()->get('rol');

        if (!$profesorId || !in_array($rol, ['profesor', 'administrador'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'Acceso denegado.']);
        }

        $entregaId = (int) $this->request->getPost('entrega_id');
        $nota = $this->request->getPost('calificacion');
        $feedback = strip_tags(trim((string)$this->request->getPost('feedback')));

        if (!is_numeric($nota) || $nota < 0 || $nota > 100) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => 'La calificación debe ser un valor numérico válido (ej: de 0 a 20 o de 0 a 100).'
            ]);
        }

        $entregaModel = new \App\Models\EntregaActividadModel();
        $entrega = $entregaModel->find($entregaId);
        if (!$entrega) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Entrega no encontrada.']);
        }

        $entregaModel->update($entregaId, [
            'calificacion'      => (float)$nota,
            'feedback_profesor' => $feedback,
            'calificado_por'    => $profesorId,
            'calificado_en'     => date('Y-m-d H:i:s'),
        ]);

        // Notificar al alumno de su calificación
        $actividad = (new \App\Models\ActividadClaseModel())->find($entrega['actividad_id']);
        $tituloAct = $actividad['titulo'] ?? 'Evaluación';

        (new NotificacionModel())->insert([
            'estudiante_id'  => (int)$entrega['estudiante_id'],
            'profesor_id'    => $profesorId,
            'seccion_id'     => (int)($actividad['seccion_id'] ?? 0),
            'tipo'           => 'alerta_estado',
            'titulo'         => "Calificación publicada: {$tituloAct}",
            'mensaje'        => "Tu entrega ha sido calificada con {$nota} pts. " . ($feedback ? 'Comentario del docente: ' . $feedback : ''),
            'modalidad'      => 'presencial',
            'leido'          => 0,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'success'      => true,
            'mensaje'      => '¡Calificación y retroalimentación guardadas con éxito!',
            'calificacion' => (float)$nota,
            'feedback'     => $feedback
        ]);
    }

    /**
     * Permite al profesor descargar la entrega subida por el estudiante
     */
    public function descargarEntrega(int $entregaId)
    {
        $profesorId = (int) session()->get('usuario_id');
        $rol = session()->get('rol');

        if (!$profesorId || !in_array($rol, ['profesor', 'administrador'], true)) {
            return redirect()->to(base_url('dashboard'));
        }

        $entregaModel = new \App\Models\EntregaActividadModel();
        $entrega = $entregaModel->find($entregaId);
        if (!$entrega || empty($entrega['archivo_ruta'])) {
            return redirect()->back()->with('error', 'No hay archivo adjunto en esta entrega.');
        }

        $rutaAbs = WRITEPATH . $entrega['archivo_ruta'];
        if (!file_exists($rutaAbs)) {
            return redirect()->back()->with('error', 'El archivo no se encuentra físicamente en el servidor.');
        }

        return $this->response->download($rutaAbs, null)->setFileName($entrega['archivo_nombre'] ?: 'entrega.pdf');
    }
}
