<?php

namespace App\Controllers;

use App\Models\SeccionModel;
use App\Models\EstadoClaseModel;
use App\Models\NotificacionModel;
use App\Models\InscripcionModel;
use App\Models\UsuarioModel;
use App\Services\ActividadService;
use App\Services\DocentePanelService;

class ProfesorController extends BaseController
{
    /**
     * Comprueba en BD que el usuario en sesión sea administrador o profesor oficialmente aprobado
     */
    private function usuarioEsDocenteAutorizado(): bool
    {
        $usuarioId = (int) session()->get('usuario_id');
        if ($usuarioId <= 0) {
            return false;
        }

        $u = \Config\Database::connect()
            ->table('usuarios')
            ->select('rol, estado_verificacion')
            ->where('id', $usuarioId)
            ->get()
            ->getRowArray();

        if ($u) {
            $rol    = $u['rol'];
            $estado = $u['estado_verificacion'];
            session()->set(['rol' => $rol, 'estado_verificacion' => $estado]);
        } else {
            $rol    = session()->get('rol');
            $estado = session()->get('estado_verificacion');
        }

        if (in_array($rol, ['administrador', 'admin'], true)) {
            return true;
        }

        return ($rol === 'profesor' && $estado === 'aprobado');
    }

    /**
     * Obtiene la lista de clases y secciones asignadas al profesor autenticado
     * Retorna JSON con la materia, sección, horarios y el estado activo de hoy.
     */
    public function misClases()
    {
        if (!$this->usuarioEsDocenteAutorizado()) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error'   => 'Acceso denegado: Se requieren permisos de profesor verificado y aprobado.'
            ]);
        }

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
        if (!$this->usuarioEsDocenteAutorizado()) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error'   => 'Acceso denegado: Se requiere ser docente aprobado por un administrador.'
            ]);
        }

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

        if ($enlaceVirtual !== null && (!preg_match('#^https?://#i', $enlaceVirtual) || filter_var($enlaceVirtual, FILTER_VALIDATE_URL) === false)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'El enlace de la sala virtual debe comenzar con http:// o https://.'
            ]);
        }
        $estadoRapido     = strip_tags(trim((string) $this->request->getPost('estado_rapido')));
        $mensajeAdicional = strip_tags(trim((string) $this->request->getPost('mensaje_adicional'))) ?: null;

        // 2. Row-Level Security / Ownership: Comprobar que esta sección le pertenezca a ESTE profesor (o admin)
        $seccionModel = new SeccionModel();
        $builderSec = $seccionModel
            ->select('secciones.*, materias.nombre as materia')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->where('secciones.id', $seccionId);

        if (!in_array(session('rol'), ['administrador', 'admin'], true)) {
            $builderSec->where('secciones.profesor_id', $profesorId);
        }

        $seccion = $builderSec->first();

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
        if (!$this->usuarioEsDocenteAutorizado()) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error'   => 'Acceso denegado: Se requiere ser docente aprobado por un administrador.'
            ]);
        }

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
     * Vista Principal del Panel Docente / Aula Classroom
     */
    public function index()
    {
        if (!$this->usuarioEsDocenteAutorizado()) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Acceso exclusivo para profesores verificados y aprobados por un administrador.');
        }

        $profesorId = (int) session()->get('usuario_id');
        $rol        = (string) session()->get('rol');
        $service    = new DocentePanelService();

        $filtros = [
            'carrera'    => $this->request->getGet('carrera'),
            'q'          => $this->request->getGet('q'),
            'page'       => $this->request->getGet('page'),
            'por_pagina' => $this->request->getGet('por_pagina'),
        ];

        if ($this->request->getGet('ajax_secciones') === '1') {
            $res = $service->obtenerSeccionesPaginadas($profesorId, $rol, $filtros);
            $cardsHtml = view('Profesor/_tarjetas_secciones', [
                'secciones'      => $res['secciones'],
                'esModoServidor' => in_array($rol, ['administrador', 'admin'], true),
            ]);

            return $this->response->setJSON([
                'success'        => true,
                'cardsHtml'      => $cardsHtml,
                'totalFiltradas' => $res['totalFiltradas'],
                'paginaActual'   => $res['paginaActual'],
                'totalPaginas'   => $res['totalPaginas'],
                'porPagina'      => $res['porPagina'],
            ]);
        }

        $panelData = $service->obtenerDatosPanel($profesorId, $rol, $filtros);
        $panelData['profesorNombre'] = trim((session()->get('primer_nombre') ?? '') . ' ' . (session()->get('apellidos') ?? ''));
        $panelData['rolUsuario']     = $rol;

        return view('Profesor/dashboard_view', $panelData);
    }

    /**
     * Permite al profesor publicar una tarea o examen oficial para toda la sección
     */
    public function crearTareaDocente()
    {
        if (!$this->usuarioEsDocenteAutorizado()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'Acceso denegado: Se requiere ser docente aprobado por un administrador.']);
        }

        $profesorId = (int) session()->get('usuario_id');
        $rol = session()->get('rol');

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

        $res = (new ActividadService())->crearActividadEnSeccion(
            $profesorId,
            (string) $rol,
            [
                'seccion_id'    => $this->request->getPost('seccion_id'),
                'titulo'        => $this->request->getPost('titulo'),
                'tipo'          => $this->request->getPost('tipo'),
                'fecha_entrega' => $this->request->getPost('fecha_entrega'),
                'ponderacion'   => $this->request->getPost('ponderacion'),
                'descripcion'   => $this->request->getPost('descripcion'),
            ],
            true
        );

        if (!$res['ok']) {
            return $this->response->setStatusCode($res['status'])->setJSON(['success' => false, 'error' => $res['error']]);
        }

        return $this->response->setJSON([
            'success'      => true,
            'mensaje'      => "¡Actividad '{$res['titulo']}' creada con éxito y notificada a los estudiantes!",
            'actividad_id' => $res['actividad_id'],
        ]);
    }

    /**
     * Retorna todas las entregas de los estudiantes para una actividad dada (AJAX)
     */
    public function obtenerEntregasAjax(int $actividadId)
    {
        if (!$this->usuarioEsDocenteAutorizado()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'Acceso denegado: Se requiere ser docente aprobado por un administrador.']);
        }

        $verif = (new ActividadService())->obtenerActividadParaDocente(
            $actividadId,
            (int) session()->get('usuario_id'),
            (string) session()->get('rol')
        );
        if (!$verif['ok']) {
            return $this->response->setStatusCode($verif['status'])->setJSON(['success' => false, 'error' => $verif['error']]);
        }

        $entregas = (new \App\Models\EntregaActividadModel())->obtenerEntregasDeActividad($actividadId);

        return $this->response->setJSON([
            'success'   => true,
            'actividad' => $verif['actividad'],
            'total'     => count($entregas),
            'entregas'  => $entregas,
        ]);
    }

    /**
     * Permite al profesor calificar la entrega de un estudiante y dejar retroalimentación
     */
    public function calificarEntrega()
    {
        if (!$this->usuarioEsDocenteAutorizado()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'Acceso denegado: Se requiere ser docente aprobado por un administrador.']);
        }

        $res = (new ActividadService())->calificarEntregaEstudiante(
            (int) $this->request->getPost('entrega_id'),
            (int) session()->get('usuario_id'),
            (string) session()->get('rol'),
            $this->request->getPost('calificacion'),
            strip_tags(trim((string) $this->request->getPost('feedback')))
        );

        if (!$res['ok']) {
            return $this->response->setStatusCode($res['status'])->setJSON(['success' => false, 'error' => $res['error']]);
        }

        return $this->response->setJSON([
            'success'      => true,
            'mensaje'      => $res['mensaje'],
            'calificacion' => $res['calificacion'],
            'feedback'     => $res['feedback'],
        ]);
    }

    /**
     * Permite al profesor descargar la entrega subida por el estudiante
     */
    public function descargarEntrega(int $entregaId)
    {
        if (!$this->usuarioEsDocenteAutorizado()) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Acceso exclusivo para profesores aprobados por un administrador.');
        }

        $entrega = (new \App\Models\EntregaActividadModel())->find($entregaId);
        if (!$entrega || empty($entrega['archivo_ruta'])) {
            return redirect()->back()->with('error', 'No hay archivo adjunto en esta entrega.');
        }

        $verif = (new ActividadService())->obtenerActividadParaDocente(
            (int) $entrega['actividad_id'],
            (int) session()->get('usuario_id'),
            (string) session()->get('rol')
        );
        if (!$verif['ok']) {
            return redirect()->back()->with('error', $verif['error']);
        }

        $nombreLimpio = basename($entrega['archivo_ruta']);
        $rutaAbs = WRITEPATH . 'uploads/entregas/' . $nombreLimpio;
        if (!file_exists($rutaAbs)) {
            return redirect()->back()->with('error', 'El archivo no se encuentra físicamente en el servidor.');
        }

        return $this->response->download($rutaAbs, null)->setFileName($entrega['archivo_nombre'] ?: 'entrega.pdf');
    }
}
