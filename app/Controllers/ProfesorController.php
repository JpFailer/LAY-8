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
}
