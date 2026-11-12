<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\AporteBibliotecaModel;
use App\Models\AuditoriaAdminModel;
use App\Models\NotificacionModel;
use App\Models\AulasModel;
use App\Models\InscripcionModel;
use App\Models\SeccionModel;
use App\Services\AdminGestionService;
use App\Services\BibliotecaService;
use App\Services\HorarioSyncService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AdminController extends BaseController
{
    protected UsuarioModel $usuarioModel;
    protected AuditoriaAdminModel $auditoriaModel;
    protected AdminGestionService $adminService;
    protected int $adminId;

    /**
     * Renderiza la interfaz visual completa del panel de administración
     */
    public function index()
    {
        $datos = $this->adminService->obtenerDatosDashboard($this->usuarioModel, $this->auditoriaModel);
        return view('admin/dashboard_view', $datos);
    }

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->usuarioModel   = new UsuarioModel();
        $this->auditoriaModel = new AuditoriaAdminModel();
        $this->adminService   = new AdminGestionService();
        $this->adminId        = (int) (session('usuario_id') ?? 0);
    }

    // =========================================================================
    // 1. DASHBOARD & MÉTRICAS DEL SISTEMA
    // =========================================================================

    /**
     * Retorna estadísticas globales del sistema para el panel administrativo
     */
    public function metricas()
    {
        return $this->response->setJSON([
            'success'  => true,
            'metricas' => $this->adminService->obtenerMetricasGlobales(),
        ]);
    }

    // =========================================================================
    // 2. GESTIÓN Y VALIDACIÓN DE PROFESORES
    // =========================================================================

    /**
     * Lista solicitudes pendientes de verificación docente
     */
    public function solicitudesProfesores()
    {
        $solicitudes = $this->usuarioModel
            ->select('id, cedula, primer_nombre, segundo_nombre, apellidos, nombre_usuario, correo, telefono, rol, carrera, estado_verificacion, ruta_carnet, fecha_registro')
            ->where('estado_verificacion', 'pendiente')
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'success'     => true,
            'total'       => count($solicitudes),
            'solicitudes' => $solicitudes,
        ]);
    }

    /**
     * Aprueba la solicitud de rol de profesor
     */
    public function aprobarProfesor(int $id)
    {
        $usuario = $this->usuarioModel->find($id);

        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Usuario no encontrado.'
            ]);
        }

        $this->usuarioModel->update($id, [
            'rol'                 => 'profesor',
            'estado_verificacion' => 'aprobado',
        ]);

        // Notificar al docente
        $notifModel = new NotificacionModel();
        $notifModel->insert([
            'estudiante_id'  => $id,
            'profesor_id'    => null,
            'seccion_id'     => null,
            'tipo'           => 'general',
            'titulo'         => '¡Solicitud de Profesor Aprobada!',
            'mensaje'        => 'Tu cuenta ha sido verificada exitosamente con el rol de Docente. Ya puedes gestionar tus clases, estados y comunicaciones privadas.',
            'modalidad'      => 'presencial',
            'enlace_virtual' => null,
            'leido'          => 0,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        // Registrar en auditoría
        $this->auditoriaModel->registrar(
            $this->adminId,
            'aprobar_profesor',
            'usuarios',
            $id,
            ['usuario' => $usuario['nombre_usuario'], 'correo' => $usuario['correo']]
        );

        return $this->response->setJSON([
            'success' => true,
            'mensaje' => "El usuario {$usuario['nombre_usuario']} ha sido aprobado exitosamente como Profesor."
        ]);
    }

    /**
     * Deniega la solicitud de rol de profesor con motivo explicativo
     */
    public function denegarProfesor(int $id)
    {
        $usuario = $this->usuarioModel->find($id);

        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Usuario no encontrado.'
            ]);
        }

        $motivo = strip_tags(trim((string) $this->request->getPost('motivo')));
        if (empty($motivo)) {
            $motivo = 'La constancia o carnet presentado no cumple con los criterios de validación docente.';
        }

        $this->usuarioModel->update($id, [
            'rol'                 => 'estudiante',
            'estado_verificacion' => 'rechazado',
        ]);

        // Notificar al usuario con el motivo del rechazo
        $notifModel = new NotificacionModel();
        $notifModel->insert([
            'estudiante_id'  => $id,
            'profesor_id'    => null,
            'seccion_id'     => null,
            'tipo'           => 'general',
            'titulo'         => 'Solicitud de Docente Rechazada',
            'mensaje'        => "Tu solicitud para obtener el rol de profesor fue rechazada. Motivo: {$motivo}",
            'modalidad'      => 'presencial',
            'enlace_virtual' => null,
            'leido'          => 0,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        // Registrar en auditoría
        $this->auditoriaModel->registrar(
            $this->adminId,
            'denegar_profesor',
            'usuarios',
            $id,
            ['usuario' => $usuario['nombre_usuario'], 'motivo' => $motivo]
        );

        return $this->response->setJSON([
            'success' => true,
            'mensaje' => "La solicitud para {$usuario['nombre_usuario']} ha sido rechazada.",
            'motivo'  => $motivo
        ]);
    }

    /**
     * Revoca el rol de profesor a un usuario y lo devuelve a estudiante
     */
    public function revocarProfesor(int $id)
    {
        $usuario = $this->usuarioModel->find($id);

        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Usuario no encontrado.'
            ]);
        }

        $this->usuarioModel->update($id, [
            'rol'                 => 'estudiante',
            'estado_verificacion' => 'no_aplica',
        ]);

        $this->auditoriaModel->registrar(
            $this->adminId,
            'revocar_profesor',
            'usuarios',
            $id,
            ['usuario' => $usuario['nombre_usuario']]
        );

        return $this->response->setJSON([
            'success' => true,
            'mensaje' => "El rol de profesor fue revocado para {$usuario['nombre_usuario']}. Ahora tiene rol de estudiante."
        ]);
    }

    // =========================================================================
    // 3. GESTIÓN DE CUENTAS: LISTAR, BANEAR, DESBANEAR, ELIMINAR Y ROLES
    // =========================================================================

    /**
     * Lista usuarios con filtros avanzados de búsqueda, rol y estado
     */
    public function listarUsuarios()
    {
        $filtros = [
            'buscar'              => $this->request->getGet('buscar'),
            'rol'                 => $this->request->getGet('rol'),
            'estado_cuenta'       => $this->request->getGet('estado_cuenta'),
            'estado_verificacion' => $this->request->getGet('estado_verificacion'),
        ];

        $pagina = max(1, (int) ($this->request->getGet('pagina') ?? 1));
        $limite = min(100, max(5, (int) ($this->request->getGet('limite') ?? 20)));
        $offset = ($pagina - 1) * $limite;

        $usuarios = $this->usuarioModel->obtenerUsuariosFiltrados($filtros, $limite, $offset);
        $total    = $this->usuarioModel->contarUsuariosFiltrados($filtros);

        return $this->response->setJSON([
            'success'     => true,
            'pagina'      => $pagina,
            'limite'      => $limite,
            'total'       => $total,
            'paginas'     => ceil($total / $limite),
            'usuarios'    => $usuarios,
        ]);
    }

    /**
     * Obtiene el detalle completo de un usuario
     */
    public function detalleUsuario(int $id)
    {
        $usuario = $this->usuarioModel
            ->select('id, cedula, primer_nombre, segundo_nombre, apellidos, nombre_usuario, correo, telefono, rol, carrera, estado_verificacion, ruta_carnet, estado_cuenta, motivo_baneo, baneado_en, fecha_registro, ultimo_acceso')
            ->find($id);

        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Usuario no encontrado.'
            ]);
        }

        // Historial de aportes subidos en biblioteca
        $aporteModel = new AporteBibliotecaModel();
        $aportes = [];
        try {
            $aportes = $aporteModel->where('usuario_id', $id)->findAll();
        } catch (\Throwable $e) {}

        // Secciones inscritas o asignadas
        $db = \Config\Database::connect();
        $secciones = [];
        if ($usuario['rol'] === 'profesor') {
            $secciones = $db->table('secciones')
                            ->select('secciones.*, materias.nombre as materia')
                            ->join('materias', 'materias.id = secciones.materia_id')
                            ->where('secciones.profesor_id', $id)
                            ->get()
                            ->getResultArray();
        } else {
            $secciones = $db->table('inscripciones')
                            ->select('secciones.*, materias.nombre as materia')
                            ->join('secciones', 'secciones.id = inscripciones.seccion_id')
                            ->join('materias', 'materias.id = secciones.materia_id')
                            ->where('inscripciones.estudiante_id', $id)
                            ->get()
                            ->getResultArray();
        }

        return $this->response->setJSON([
            'success'   => true,
            'usuario'   => $usuario,
            'aportes'   => $aportes,
            'secciones' => $secciones,
        ]);
    }

    /**
     * Suspende o banea la cuenta de un usuario
     */
    public function banearUsuario(int $id)
    {
        // sistema de prevencion de auto-baneo antidayanas (un admin no puede sabotearse a si mismo)
        if ($id === $this->adminId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => 'Operación denegada: No puedes banear tu propia cuenta de administrador.'
            ]);
        }

        $usuario = $this->usuarioModel->find($id);
        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Usuario no encontrado.'
            ]);
        }

        // No permitir banear a otro administrador directamente
        if (in_array($usuario['rol'], ['administrador', 'admin'], true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error'   => 'No puedes banear a otro Administrador del sistema.'
            ]);
        }

        $motivo = strip_tags(trim((string) $this->request->getPost('motivo')));
        if (empty($motivo)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'Debes especificar un motivo para el baneo de la cuenta.'
            ]);
        }

        $this->usuarioModel->update($id, [
            'estado_cuenta' => 'baneado',
            'motivo_baneo'  => $motivo,
            'baneado_en'    => date('Y-m-d H:i:s'),
        ]);

        $this->auditoriaModel->registrar(
            $this->adminId,
            'banear_usuario',
            'usuarios',
            $id,
            ['usuario' => $usuario['nombre_usuario'], 'motivo' => $motivo]
        );

        return $this->response->setJSON([
            'success' => true,
            'mensaje' => "La cuenta de {$usuario['nombre_usuario']} ha sido baneada exitosamente.",
            'motivo'  => $motivo
        ]);
    }

    /**
     * Desbanea o reactiva una cuenta de usuario
     */
    public function desbanearUsuario(int $id)
    {
        $usuario = $this->usuarioModel->find($id);
        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Usuario no encontrado.'
            ]);
        }

        $this->usuarioModel->update($id, [
            'estado_cuenta' => 'activo',
            'motivo_baneo'  => null,
            'baneado_en'    => null,
        ]);

        $this->auditoriaModel->registrar(
            $this->adminId,
            'desbanear_usuario',
            'usuarios',
            $id,
            ['usuario' => $usuario['nombre_usuario']]
        );

        return $this->response->setJSON([
            'success' => true,
            'mensaje' => "La cuenta de {$usuario['nombre_usuario']} ha sido reactivada exitosamente."
        ]);
    }

    /**
     * Elimina permanentemente una cuenta de usuario y desvincula datos de forma segura
     */
    public function eliminarUsuario(int $id)
    {
        // sistema de prevencion de auto-eliminacion antidayanas (evita dejar el sistema sin administradores)
        if ($id === $this->adminId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => 'Operación denegada: No puedes eliminar tu propia cuenta de administrador.'
            ]);
        }

        $usuario = $this->usuarioModel->find($id);
        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Usuario no encontrado.'
            ]);
        }

        if (in_array($usuario['rol'], ['administrador', 'admin'], true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error'   => 'Operación denegada: No puedes eliminar a otro Administrador del sistema.'
            ]);
        }

        $borrarAportes = $this->request->getPost('borrar_aportes') === 'true';
        $this->adminService->eliminarUsuarioConLimpieza($this->usuarioModel, $id, $borrarAportes);

        $this->auditoriaModel->registrar(
            $this->adminId,
            'eliminar_usuario',
            'usuarios',
            $id,
            [
                'usuario'        => $usuario['nombre_usuario'],
                'correo'         => $usuario['correo'],
                'borrar_aportes' => $borrarAportes
            ]
        );

        return $this->response->setJSON([
            'success' => true,
            'mensaje' => "La cuenta de {$usuario['nombre_usuario']} ha sido eliminada permanentemente del sistema."
        ]);
    }

    /**
     * Promueve o cambia el rol de un usuario
     */
    public function cambiarRolUsuario(int $id)
    {
        if ($id === $this->adminId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => 'Operación denegada: No puedes cambiar tu propio rol de administrador.'
            ]);
        }

        $usuario = $this->usuarioModel->find($id);
        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Usuario no encontrado.'
            ]);
        }

        $nuevoRol = (string) $this->request->getPost('nuevo_rol');
        if (!in_array($nuevoRol, ['estudiante', 'profesor', 'administrador'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'Rol inválido. Roles admitidos: estudiante, profesor, administrador.'
            ]);
        }

        $datosActualizar = ['rol' => $nuevoRol];
        if ($nuevoRol === 'profesor') {
            $datosActualizar['estado_verificacion'] = 'aprobado';
        } elseif ($nuevoRol === 'estudiante') {
            $datosActualizar['estado_verificacion'] = 'no_aplica';
        }

        $this->usuarioModel->update($id, $datosActualizar);

        $this->auditoriaModel->registrar(
            $this->adminId,
            'cambiar_rol',
            'usuarios',
            $id,
            ['usuario' => $usuario['nombre_usuario'], 'rol_anterior' => $usuario['rol'], 'nuevo_rol' => $nuevoRol]
        );

        return $this->response->setJSON([
            'success'   => true,
            'mensaje'   => "El rol de {$usuario['nombre_usuario']} ha sido actualizado a {$nuevoRol}.",
            'nuevo_rol' => $nuevoRol
        ]);
    }

    // =========================================================================
    // 4. GESTIÓN Y BORRADO DE APORTES EN LA BIBLIOTECA
    // =========================================================================

    /**
     * Lista aportes paginados con búsqueda SQL y opcionalmente escanea huérfanos en disco.
     */
    public function listarAportesBiblioteca()
    {
        $categoria    = $this->request->getGet('categoria');
        $buscar       = trim((string) ($this->request->getGet('buscar') ?? ''));
        $pagina       = max(1, (int) ($this->request->getGet('pagina') ?? 1));
        $limite       = min(100, max(5, (int) ($this->request->getGet('limite') ?? 20)));
        $sinHuerfanos = $this->request->getGet('sin_huerfanos') === '1';

        $aporteModel = new AporteBibliotecaModel();
        $pag = [
            'aportes'      => [],
            'total'        => 0,
            'paginaActual' => $pagina,
            'totalPaginas' => 1,
            'porPagina'    => $limite,
        ];

        try {
            $pag = (new BibliotecaService($aporteModel))->obtenerAportesPaginados(
                $categoria !== '' ? $categoria : null,
                $buscar !== '' ? $buscar : null,
                null,
                $pagina,
                $limite
            );
        } catch (\Throwable $e) {}

        $aportes = $pag['aportes'];
        foreach ($aportes as &$ap) {
            $rutaFisica = WRITEPATH . ($ap['ruta_archivo'] ?? '');
            $ap['archivo_existe_disco'] = !empty($ap['ruta_archivo']) && file_exists($rutaFisica);
            $ap['tamano_kb']            = round(((int) ($ap['tamano_bytes'] ?? 0)) / 1024, 2);
        }
        unset($ap);

        // Escanear huérfanos solo cuando no es búsqueda AJAX rápida paginada
        $archivosHuerfanos = [];
        $baseBiblioteca = WRITEPATH . 'uploads/biblioteca/';
        if (!$sinHuerfanos && $buscar === '' && $pagina === 1 && is_dir($baseBiblioteca)) {
            $rutasRegistradas = array_column($aporteModel->select('ruta_archivo')->findAll(), 'ruta_archivo');
            $carpetas = ['guias', 'ejercicios', 'resumenes', 'otros'];

            foreach ($carpetas as $cat) {
                $dirCat = $baseBiblioteca . $cat;
                if (is_dir($dirCat)) {
                    $archivos = array_diff(scandir($dirCat), ['.', '..', 'index.html']);
                    foreach ($archivos as $arch) {
                        $rutaRel = 'uploads/biblioteca/' . $cat . '/' . $arch;
                        if (!in_array($rutaRel, $rutasRegistradas, true)) {
                            $archivosHuerfanos[] = [
                                'categoria'        => $cat,
                                'nombre_guardado'  => $arch,
                                'ruta_relativa'    => $rutaRel,
                                'tamano_kb'        => round(filesize($dirCat . '/' . $arch) / 1024, 2),
                                'fecha_modificado' => date('Y-m-d H:i:s', filemtime($dirCat . '/' . $arch)),
                            ];
                        }
                    }
                }
            }
        }

        return $this->response->setJSON([
            'success'            => true,
            'pagina'             => $pag['paginaActual'],
            'limite'             => $pag['porPagina'],
            'total'              => $pag['total'],
            'paginas'            => $pag['totalPaginas'],
            'total_aportes'      => $pag['total'],
            'aportes'            => $aportes,
            'archivos_huerfanos' => $archivosHuerfanos,
        ]);
    }

    /**
     * Elimina un aporte de la biblioteca (borra el archivo físico y el registro de la BD)
     */
    public function eliminarAporteBiblioteca(int $id)
    {
        $aporteModel = new AporteBibliotecaModel();
        $aporte = $aporteModel->find($id);

        if (!$aporte) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Aporte no encontrado en la base de datos.'
            ]);
        }

        $motivo = strip_tags(trim((string) $this->request->getPost('motivo')));
        if (empty($motivo)) {
            $motivo = 'El archivo fue retirado por la administración por no cumplir con las normas de publicación.';
        }

        // 1. Borrado físico del archivo en disco
        $rutaFisica = WRITEPATH . $aporte['ruta_archivo'];
        $archivoEliminadoFisico = false;
        if (file_exists($rutaFisica)) {
            $archivoEliminadoFisico = @unlink($rutaFisica);
        }

        // 2. Notificar al usuario que subió el aporte si está registrado
        if (!empty($aporte['usuario_id'])) {
            $notifModel = new NotificacionModel();
            $notifModel->insert([
                'estudiante_id'  => (int) $aporte['usuario_id'],
                'profesor_id'    => null,
                'seccion_id'     => null,
                'tipo'           => 'general',
                'titulo'         => 'Aporte eliminado de la Biblioteca',
                'mensaje'        => "Tu material '{$aporte['titulo']}' ({$aporte['nombre_original']}) fue retirado por la administración. Motivo: {$motivo}",
                'modalidad'      => 'presencial',
                'enlace_virtual' => null,
                'leido'          => 0,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        }

        // 3. Borrado del registro en BD
        $aporteModel->delete($id);

        // 4. Registro en auditoría
        $this->auditoriaModel->registrar(
            $this->adminId,
            'eliminar_aporte',
            'aportes_biblioteca',
            $id,
            [
                'titulo'           => $aporte['titulo'],
                'nombre_original'  => $aporte['nombre_original'],
                'ruta_archivo'     => $aporte['ruta_archivo'],
                'usuario_id'       => $aporte['usuario_id'],
                'motivo'           => $motivo,
                'archivo_borrado'  => $archivoEliminadoFisico,
            ]
        );

        return $this->response->setJSON([
            'success'                 => true,
            'mensaje'                 => "El aporte '{$aporte['titulo']}' fue eliminado exitosamente.",
            'archivo_eliminado_disco' => $archivoEliminadoFisico,
        ]);
    }

    /**
     * Elimina archivos físicos huérfanos detectados en el directorio de la biblioteca
     */
    public function eliminarArchivoHuerfano()
    {
        $rutaRelativa = (string) $this->request->getPost('ruta_relativa');

        // 🛡️ Blindaje contra Path Traversal (evitar ../ o ..\)
        $rutaRelativa = str_replace(['../', '..\\', './', '.\\'], '', $rutaRelativa);

        if (!str_starts_with($rutaRelativa, 'uploads/biblioteca/')) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => 'Ruta inválida: Solo se permite manipular archivos dentro de uploads/biblioteca/.'
            ]);
        }

        $rutaAbsoluta = WRITEPATH . $rutaRelativa;

        if (!file_exists($rutaAbsoluta) || is_dir($rutaAbsoluta)) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'El archivo no existe en el servidor.'
            ]);
        }

        $eliminado = @unlink($rutaAbsoluta);

        $this->auditoriaModel->registrar(
            $this->adminId,
            'eliminar_archivo_huerfano',
            'archivos',
            null,
            ['ruta_relativa' => $rutaRelativa, 'exito' => $eliminado]
        );

        return $this->response->setJSON([
            'success' => $eliminado,
            'mensaje' => $eliminado ? 'Archivo huérfano eliminado del disco.' : 'No se pudo eliminar el archivo del disco.'
        ]);
    }

    // =========================================================================
    // 5. OTRAS FUNCIONES ESENCIALES DEL ADMINISTRADOR
    // =========================================================================

    /**
     // sistema de auditoria inmutable antidayanas para trazabilidad de eventos
     */
    public function historialAuditoria()
    {
        $limite = min(200, max(10, (int) ($this->request->getGet('limite') ?? 50)));
        $registros = $this->auditoriaModel->ultimosRegistros($limite);

        return $this->response->setJSON([
            'success'   => true,
            'total'     => count($registros),
            'auditoria' => $registros,
        ]);
    }

    /**
     * Control y cambio de estado de aulas (Disponibilidad / Mantenimiento)
     */
    public function toggleEstadoAula(int $id)
    {
        $aulasModel = new AulasModel();
        $aula = $aulasModel->find($id);

        if (!$aula) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Aula no encontrada.'
            ]);
        }

        $nuevoEstado = (string) $this->request->getPost('estado');
        if (!in_array($nuevoEstado, ['disponible', 'mantenimiento', 'inhabilitada'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'Estado inválido. Estados permitidos: disponible, mantenimiento, inhabilitada.'
            ]);
        }

        $notaEstado = strip_tags(trim((string) $this->request->getPost('nota_estado')));

        $aulasModel->update($id, [
            'estado'      => $nuevoEstado,
            'nota_estado' => !empty($notaEstado) ? $notaEstado : null,
        ]);

        $this->auditoriaModel->registrar(
            $this->adminId,
            'toggle_estado_aula',
            'aulas',
            $id,
            [
                'aula'        => $aula['nombre_json'],
                'nuevo_estado' => $nuevoEstado,
                'nota_estado'  => $notaEstado,
            ]
        );

        return $this->response->setJSON([
            'success'      => true,
            'mensaje'      => "El estado del aula '{$aula['nombre_json']}' se actualizó a '{$nuevoEstado}'.",
            'nuevo_estado' => $nuevoEstado,
            'nota_estado'  => $notaEstado,
        ]);
    }

    /**
     * Emisión de Comunicados Globales Institucionales (Broadcast a Alumnos y/o Profesores)
     */
    public function emitirComunicadoGlobal()
    {
        $destinatarios = (string) $this->request->getPost('destinatarios'); // 'todos', 'estudiantes', 'profesores'
        $titulo        = strip_tags(trim((string) $this->request->getPost('titulo')));
        $mensaje       = strip_tags(trim((string) $this->request->getPost('mensaje')));

        if (!in_array($destinatarios, ['todos', 'estudiantes', 'profesores'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'Destinatarios inválidos. Opciones: todos, estudiantes, profesores.'
            ]);
        }

        if (empty($titulo) || empty($mensaje)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'Debes incluir un título y un mensaje para el comunicado.'
            ]);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('usuarios')->select('id, rol');

        if ($destinatarios === 'estudiantes') {
            $builder->where('rol', 'estudiante');
        } elseif ($destinatarios === 'profesores') {
            $builder->where('rol', 'profesor');
        }

        $usuariosDestino = $builder->get()->getResultArray();
        $notifModel = new NotificacionModel();
        $ahora = date('Y-m-d H:i:s');
        $totalEnviados = 0;

        foreach ($usuariosDestino as $u) {
            $notifModel->insert([
                'estudiante_id'  => (int) $u['id'],
                'profesor_id'    => null,
                'seccion_id'     => null,
                'tipo'           => 'general',
                'titulo'         => "[COMUNICADO OFICIAL] {$titulo}",
                'mensaje'        => $mensaje,
                'modalidad'      => 'presencial',
                'enlace_virtual' => null,
                'leido'          => 0,
                'created_at'     => $ahora,
            ]);
            $totalEnviados++;
        }

        $this->auditoriaModel->registrar(
            $this->adminId,
            'comunicado_global',
            'notificaciones',
            null,
            [
                'destinatarios'  => $destinatarios,
                'titulo'         => $titulo,
                'total_enviados' => $totalEnviados,
            ]
        );

        return $this->response->setJSON([
            'success'        => true,
            'mensaje'        => "Comunicado oficial emitido con éxito a {$totalEnviados} usuario(s).",
            'total_enviados' => $totalEnviados,
        ]);
    }

    /**
     * Permite al Administrador subir el archivo .json de horarios procesados (óptimo sin Python en servidor)
     * o .pdf oficial en entorno local, re-sincronizando en lote la base de datos del mapa.
     */
    public function actualizarHorarioPdf()
    {
        $archivo = $this->request->getFile('horario_archivo') ?: $this->request->getFile('horario_pdf');
        $carrera = (string) ($this->request->getPost('carrera') ?: 'ING-INF');
        $esJsonRequest = $this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json';

        if (!$archivo) {
            if ($esJsonRequest) {
                return $this->response->setJSON(['success' => false, 'error' => 'Por favor selecciona un archivo .json (o .pdf en local) de horario válido.']);
            }
            return redirect()->to(base_url('admin'))->with('error', 'Por favor selecciona un archivo de horario válido.');
        }

        $res = (new HorarioSyncService())->sincronizarDesdeArchivo($archivo, $carrera);
        if (!$res['ok']) {
            if ($esJsonRequest) {
                return $this->response->setJSON(['success' => false, 'error' => $res['error']]);
            }
            return redirect()->to(base_url('admin'))->with('error', $res['error']);
        }

        $this->auditoriaModel->registrar(
            $this->adminId,
            'actualizar_horario_carrera',
            'horario',
            null,
            ['carrera' => $res['carrera'], 'modo' => $res['modo'] ?? 'json', 'clases_cargadas' => $res['clases_cargadas'], 'archivo' => $archivo->getClientName()]
        );

        if ($esJsonRequest) {
            return $this->response->setJSON([
                'success'         => true,
                'mensaje'         => $res['mensaje'],
                'carrera'         => $res['carrera'],
                'modo'            => $res['modo'] ?? 'json',
                'clases_cargadas' => $res['clases_cargadas'],
                'fecha'           => date('d/m/Y H:i'),
            ]);
        }

        return redirect()->to(base_url('admin'))->with('mensaje', $res['mensaje']);
    }

    /**
     // traerse las secciones que tiene el profe
     */
    public function obtenerSeccionesDocente(int $profesorId)
    {
        $db = \Config\Database::connect();
        $profesor = $this->usuarioModel->find($profesorId);
        if (!$profesor) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Docente no encontrado.']);
        }

        $secciones = $db->table('secciones')
            ->select('secciones.id, secciones.codigo_seccion, secciones.carrera, materias.nombre as materia, materias.codigo_materia')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->where('secciones.profesor_id', $profesorId)
            ->orderBy('materias.nombre', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'success'   => true,
            'profesor'  => $profesor,
            'secciones' => $secciones
        ]);
    }

    /**
     // buscar materias pa asignarselas
     */
    public function buscarSeccionesParaAsignar()
    {
        $q = trim((string)$this->request->getGet('q'));
        $db = \Config\Database::connect();

        $builder = $db->table('secciones')
            ->select('secciones.id, secciones.codigo_seccion, secciones.profesor_id, secciones.carrera, materias.nombre as materia, materias.codigo_materia, usuarios.primer_nombre as prof_nombre, usuarios.apellidos as prof_apellido')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->join('usuarios', 'usuarios.id = secciones.profesor_id', 'left');

        if (!empty($q)) {
            $builder->groupStart()
                ->like('materias.nombre', $q)
                ->orLike('materias.codigo_materia', $q)
                ->orLike('secciones.codigo_seccion', $q)
                ->orLike('secciones.carrera', $q)
                ->groupEnd();
        }

        $secciones = $builder->orderBy('materias.nombre', 'ASC')->limit(20)->get()->getResultArray();

        return $this->response->setJSON([
            'success'   => true,
            'secciones' => $secciones
        ]);
    }

    /**
     // amarrar seccion a un profe
     */
    public function asignarSeccionDocente()
    {
        $profesorId = (int) $this->request->getPost('profesor_id');
        $seccionId  = (int) $this->request->getPost('seccion_id');

        if ($profesorId <= 0 || $seccionId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Identificadores inválidos.']);
        }

        $profesor = $this->usuarioModel->find($profesorId);
        if (!$profesor) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Docente no encontrado.']);
        }

        if ($profesor['rol'] === 'profesor' && ($profesor['estado_verificacion'] ?? '') !== 'aprobado') {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'error' => 'Solo se pueden asignar secciones a docentes previamente aprobados.']);
        }

        $db = \Config\Database::connect();
        $seccion = $db->table('secciones')
            ->select('secciones.*, materias.nombre as materia')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->where('secciones.id', $seccionId)
            ->get()
            ->getRowArray();

        if (!$seccion) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Sección no encontrada.']);
        }

        $db->table('secciones')->where('id', $seccionId)->update(['profesor_id' => $profesorId]);

        $this->auditoriaModel->registrar(
            $this->adminId,
            'asignar_seccion_docente',
            'secciones',
            $seccionId,
            ['profesor_id' => $profesorId, 'profesor' => $profesor['nombre_usuario'], 'materia' => $seccion['materia'], 'seccion' => $seccion['codigo_seccion']]
        );

        return $this->response->setJSON([
            'success' => true,
            'mensaje' => "Sección {$seccion['materia']} (Secc. {$seccion['codigo_seccion']}) asignada exitosamente a {$profesor['primer_nombre']} {$profesor['apellidos']}."
        ]);
    }

    /**
     // desvincular seccion del profe
     */
    public function desasignarSeccionDocente()
    {
        $seccionId = (int) $this->request->getPost('seccion_id');
        if ($seccionId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Identificador de sección inválido.']);
        }

        $db = \Config\Database::connect();
        $seccion = $db->table('secciones')
            ->select('secciones.*, materias.nombre as materia')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->where('secciones.id', $seccionId)
            ->get()
            ->getRowArray();

        if (!$seccion) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Sección no encontrada.']);
        }

        $profAnterior = $seccion['profesor_id'];
        $db->table('secciones')->where('id', $seccionId)->update(['profesor_id' => null]);

        $this->auditoriaModel->registrar(
            $this->adminId,
            'desasignar_seccion_docente',
            'secciones',
            $seccionId,
            ['profesor_anterior' => $profAnterior, 'materia' => $seccion['materia'], 'seccion' => $seccion['codigo_seccion']]
        );

        return $this->response->setJSON([
            'success' => true,
            'mensaje' => "Sección {$seccion['materia']} desvinculada del docente."
        ]);
    }

}
