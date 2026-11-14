<?php 

namespace App\Controllers;

use App\Models\AulasModel;
use App\Models\SeccionModel;
use App\Models\InscripcionModel;
use App\Models\ClaseProgramadaModel;
use App\Models\EstadoClaseModel;
use App\Models\ActividadClaseModel;
use App\Models\UsuarioModel;
use App\Services\ActividadService;
use App\Services\HorarioAulasService;
use App\Services\HorarioSyncService;

class AulasController extends BaseController 
{
    /**
     * Bloques oficiales de horario de la Universidad Marítima del Caribe (UMC)
     * Extraídos del Formulario Oficial CGD-001 (Mañana, Tarde, Noche)
     */
    protected array $bloquesOficiales = [
        'Mañana' => [
            ['inicio' => '07:15:00', 'fin' => '08:00:00', 'etiqueta' => '07:15 - 08:00'],
            ['inicio' => '08:00:00', 'fin' => '08:45:00', 'etiqueta' => '08:00 - 08:45'],
            ['inicio' => '08:45:00', 'fin' => '09:30:00', 'etiqueta' => '08:45 - 09:30'],
            ['inicio' => '09:30:00', 'fin' => '10:15:00', 'etiqueta' => '09:30 - 10:15'],
            ['inicio' => '10:15:00', 'fin' => '11:00:00', 'etiqueta' => '10:15 - 11:00'],
            ['inicio' => '11:00:00', 'fin' => '11:45:00', 'etiqueta' => '11:00 - 11:45'],
        ],
        'Tarde' => [
            ['inicio' => '12:45:00', 'fin' => '13:30:00', 'etiqueta' => '12:45 - 13:30'],
            ['inicio' => '13:30:00', 'fin' => '14:15:00', 'etiqueta' => '13:30 - 14:15'],
            ['inicio' => '14:15:00', 'fin' => '15:00:00', 'etiqueta' => '14:15 - 15:00'],
            ['inicio' => '15:00:00', 'fin' => '15:45:00', 'etiqueta' => '15:00 - 15:45'],
            ['inicio' => '15:45:00', 'fin' => '16:30:00', 'etiqueta' => '15:45 - 16:30'],
            ['inicio' => '16:30:00', 'fin' => '17:15:00', 'etiqueta' => '16:30 - 17:15'],
        ],
        'Noche' => [
            ['inicio' => '17:15:00', 'fin' => '18:00:00', 'etiqueta' => '17:15 - 18:00'],
            ['inicio' => '18:00:00', 'fin' => '18:45:00', 'etiqueta' => '18:00 - 18:45'],
            ['inicio' => '18:45:00', 'fin' => '19:30:00', 'etiqueta' => '18:45 - 19:30'],
            ['inicio' => '19:30:00', 'fin' => '20:15:00', 'etiqueta' => '19:30 - 20:15'],
            ['inicio' => '20:15:00', 'fin' => '21:00:00', 'etiqueta' => '20:15 - 21:00'],
            ['inicio' => '21:00:00', 'fin' => '21:45:00', 'etiqueta' => '21:00 - 21:45'],
            ['inicio' => '21:45:00', 'fin' => '22:30:00', 'etiqueta' => '21:45 - 22:30'],
        ],
    ];

    /**
     * Vista principal del módulo de Aulas (tipo Classroom sencillo)
     */
    public function index() 
    {
        $usuarioId   = (int) session()->get('usuario_id');
        $rol         = session()->get('rol') ?? 'estudiante';
        $estadoVerif = session()->get('estado_verificacion') ?? 'no_aplica';

        // Redireccion de rol antidayanas:
        // Solo profesores aprobados gestionan materias desde Panel Docente (/docente)
        if ($rol === 'profesor') {
            if ($estadoVerif === 'aprobado') {
                return redirect()->to(base_url('docente'))->with('info', 'Como profesor oficial de la UMC, gestionas tus materias y aulas virtuales desde tu Panel Docente.');
            }
            return redirect()->to(base_url('dashboard'))->with('error', 'Tu solicitud de rol docente está pendiente de verificación por un administrador.');
        }

        $usuarioCarrera = session()->get('carrera');
        if (!$usuarioCarrera && $usuarioId) {
            $u = (new UsuarioModel())->find($usuarioId);
            $usuarioCarrera = $u['carrera'] ?? null;
            if ($usuarioCarrera) {
                session()->set('carrera', $usuarioCarrera);
            }
        }

        $datosVista = (new HorarioAulasService())->obtenerDatosVistaAulas($usuarioId, $this->bloquesOficiales);
        $datosVista['usuarioCarrera'] = $usuarioCarrera;
        $datosVista['rolUsuario']     = $rol;

        return view('Dashboard/aulas_view', $datosVista); 
    }

    /**
     * Elimina un bloque de horario personal o asignado
     */
    public function eliminarBloqueManual(int $id)
    {
        $usuarioId = (int) session()->get('usuario_id');
        $esAjax    = $this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        if (!$usuarioId) {
            if ($esAjax) {
                return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Sesión expirada.']);
            }
            return redirect()->to('/login')->with('error', 'Sesión expirada.');
        }

        $claseModel = new ClaseProgramadaModel();
        $bloque = $claseModel->find($id);
        if (!$bloque) {
            if ($esAjax) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Bloque de horario no encontrado.']);
            }
            return redirect()->to(base_url('aulas?tab=horario'))->with('error', 'Bloque de horario no encontrado.');
        }

        $db = \Config\Database::connect();

        // Solo borrar físicamente cuando es un bloque personal del propio usuario;
        // los bloques oficiales se ocultan en su horario personal sin destruir el catálogo global.
        if (!empty($bloque['estudiante_id']) && (int) $bloque['estudiante_id'] === $usuarioId) {
            $claseModel->delete($id);
        } else {
            $yaExcluido = $db->table('estudiante_bloques_excluidos')
                ->where('estudiante_id', $usuarioId)
                ->where('clase_id', $id)
                ->countAllResults();

            if ($yaExcluido === 0) {
                $db->table('estudiante_bloques_excluidos')->insert([
                    'estudiante_id' => $usuarioId,
                    'clase_id'      => $id,
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }

        if ($esAjax) {
            return $this->response->setJSON([
                'success' => true,
                'mensaje' => 'Bloque removido de tu horario correctamente.'
            ]);
        }

        return redirect()->to(base_url('aulas?tab=horario'))->with('mensaje', 'Bloque removido de tu horario.');
    }

    /**
     * Limpia por completo el horario del estudiante para armarlo desde cero sin conflictos
     */
    public function limpiarHorarioPersonal()
    {
        $usuarioId = (int) session()->get('usuario_id');
        $esAjax    = $this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        if (!$usuarioId) {
            if ($esAjax) {
                return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Sesión expirada.']);
            }
            return redirect()->to('/login')->with('error', 'Sesión expirada.');
        }

        $db = \Config\Database::connect();
        $idsOficiales = (new HorarioAulasService())->obtenerIdsBloquesOficialesDeUsuario($usuarioId);

        $db->transStart();

        // 1. Eliminar bloques personales creados por el usuario
        $db->table('clases_programadas')
            ->where('estudiante_id', $usuarioId)
            ->delete();

        // 2. Excluir todos los bloques oficiales efectivos del usuario sin duplicar exclusiones previas
        if ($idsOficiales !== []) {
            $excluidosActuales = $db->table('estudiante_bloques_excluidos')
                ->select('clase_id')
                ->where('estudiante_id', $usuarioId)
                ->get()
                ->getResultArray();
            $yaExcluidos = array_map('intval', array_column($excluidosActuales, 'clase_id'));
            $faltantes   = array_values(array_diff($idsOficiales, $yaExcluidos));

            if ($faltantes !== []) {
                $ahora = date('Y-m-d H:i:s');
                $filasExcluir = [];
                foreach ($faltantes as $claseId) {
                    $filasExcluir[] = [
                        'estudiante_id' => $usuarioId,
                        'clase_id'      => $claseId,
                        'created_at'    => $ahora,
                    ];
                }
                $db->table('estudiante_bloques_excluidos')->insertBatch($filasExcluir, null, 250);
            }
        }

        $db->transComplete();

        if ($esAjax) {
            return $this->response->setJSON([
                'success' => true,
                'mensaje' => '¡Tu horario semanal ha sido limpiado por completo!'
            ]);
        }

        return redirect()->to(base_url('aulas?tab=horario'))->with('mensaje', '¡Tu horario semanal ha sido limpiado por completo!');
    }

    /**
     * Restaura los bloques de horario oficiales asignados originalmente por la universidad
     */
    public function restaurarHorarioOficial()
    {
        $usuarioId = (int) session()->get('usuario_id');
        $esAjax    = $this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        if (!$usuarioId) {
            if ($esAjax) {
                return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Sesión expirada.']);
            }
            return redirect()->to('/login')->with('error', 'Sesión expirada.');
        }

        $db = \Config\Database::connect();
        $db->table('estudiante_bloques_excluidos')
            ->where('estudiante_id', $usuarioId)
            ->delete();

        if ($esAjax) {
            return $this->response->setJSON([
                'success' => true,
                'mensaje' => '¡Horario oficial de la universidad restaurado correctamente!'
            ]);
        }

        return redirect()->to(base_url('aulas?tab=horario'))->with('mensaje', '¡Horario oficial restaurado con éxito!');
    }

    /**
     * Permite al estudiante subir su entrega de tarea / actividad académica (PDF, Word, ZIP, JPG)
     */
    public function entregarTarea(int $actividadId)
    {
        $usuarioId = (int) session()->get('usuario_id');
        $esAjax    = $this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        if (!$usuarioId) {
            if ($esAjax) return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Sesión expirada.']);
            return redirect()->to('/login')->with('error', 'Sesión expirada.');
        }

        $res = (new ActividadService())->registrarEntregaEstudiante(
            $actividadId,
            $usuarioId,
            (string) (session('rol') ?? 'estudiante'),
            $this->request->getFile('archivo_tarea'),
            strip_tags(trim((string) $this->request->getPost('comentario')))
        );

        if (!$res['ok']) {
            if ($esAjax) {
                return $this->response->setStatusCode($res['status'])->setJSON(['success' => false, 'error' => $res['error']]);
            }
            if ($res['status'] === 403 && str_contains((string) $res['error'], 'evaluada')) {
                return redirect()->to(base_url('aulas?tab=actividades'))->with('error', $res['error']);
            }
            return redirect()->back()->with('error', $res['error']);
        }

        if ($esAjax) {
            return $this->response->setJSON([
                'success' => true,
                'mensaje' => $res['mensaje'],
                'archivo' => $res['archivo'],
            ]);
        }

        return redirect()->to(base_url('aulas?tab=actividades'))->with('mensaje', $res['mensaje']);
    }

    /**
     * Permite descargar la entrega de tarea subida por el estudiante
     */
    public function descargarMiEntrega(int $actividadId)
    {
        $usuarioId = (int) session()->get('usuario_id');
        if (!$usuarioId) return redirect()->to('/login');

        $entregaModel = new \App\Models\EntregaActividadModel();
        $entrega = $entregaModel->obtenerEntregaEstudiante($actividadId, $usuarioId);
        if (!$entrega || empty($entrega['archivo_ruta'])) {
            return redirect()->back()->with('error', 'No hay archivo adjunto para esta entrega.');
        }

        $nombreLimpio = basename($entrega['archivo_ruta']);
        $rutaAbs = WRITEPATH . 'uploads/entregas/' . $nombreLimpio;
        if (!file_exists($rutaAbs)) {
            return redirect()->back()->with('error', 'El archivo no se encuentra físicamente en el servidor.');
        }

        return $this->response->download($rutaAbs, null)->setFileName($entrega['archivo_nombre'] ?: 'entrega.pdf');
    }

    /**
     * Crea una actividad académica / tarea en el cronograma
     */
    public function crearActividad()
    {
        $usuarioId = (int) session()->get('usuario_id');
        if (!$usuarioId) {
            return redirect()->to('/login')->with('error', 'Sesión expirada.');
        }

        $u = \Config\Database::connect()
            ->table('usuarios')
            ->select('rol, estado_verificacion')
            ->where('id', $usuarioId)
            ->get()
            ->getRowArray();

        $rol         = (string) ($u['rol'] ?? session()->get('rol') ?? 'estudiante');
        $estadoVerif = (string) ($u['estado_verificacion'] ?? session()->get('estado_verificacion') ?? 'no_aplica');

        if ($u) {
            session()->set([
                'rol'                 => $rol,
                'estado_verificacion' => $estadoVerif,
            ]);
        }

        $esAutorizado = in_array($rol, ['administrador', 'admin'], true)
            || ($rol === 'profesor' && $estadoVerif === 'aprobado');

        if (!$esAutorizado) {
            return redirect()->back()->with('error', 'Solo los docentes verificados o administradores pueden crear actividades.');
        }

        $res = (new ActividadService())->crearActividadEnSeccion(
            $usuarioId,
            $rol,
            [
                'seccion_id'    => $this->request->getPost('seccion_id'),
                'titulo'        => $this->request->getPost('titulo'),
                'descripcion'   => $this->request->getPost('descripcion'),
                'tipo'          => $this->request->getPost('tipo'),
                'fecha_entrega' => $this->request->getPost('fecha_entrega'),
                'ponderacion'   => $this->request->getPost('ponderacion'),
            ],
            true
        );

        if (!$res['ok']) {
            return redirect()->back()->with('error', $res['error']);
        }

        return redirect()->to(base_url('aulas?tab=actividades'))->with('mensaje', '¡Actividad agregada al cronograma con éxito!');
    }

    /**
     * Alterna el estado (completada / pendiente) de una tarea vía AJAX
     */
    public function toggleActividad(int $id)
    {
        $usuarioId = (int) session()->get('usuario_id');
        if (!$usuarioId) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'No autorizado']);
        }

        $actividadModel = new ActividadClaseModel();
        $actividad = $actividadModel->find($id);
        if (!$actividad) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Actividad no encontrada.']);
        }

        $exito = $actividadModel->toggleEstadoEstudiante($id, $usuarioId);

        return $this->response->setJSON([
            'success' => $exito,
            'mensaje' => $exito ? 'Estado de la actividad actualizado.' : 'No se pudo actualizar.',
        ]);
    }

    /**
     * Elimina una actividad del cronograma
     */
    public function eliminarActividad(int $id)
    {
        $usuarioId = (int) session()->get('usuario_id');
        $actividadModel = new ActividadClaseModel();

        $actividad = $actividadModel->find($id);
        if (!$actividad) {
            return redirect()->back()->with('error', 'Actividad no encontrada.');
        }

        // sistema de autorizacion estricta antidayanas (solo el creador o un admin puede eliminar la actividad)
        $rol = (string) (session()->get('rol') ?? '');
        if ((int)$actividad['creado_por'] !== $usuarioId && !in_array($rol, ['administrador', 'admin'], true)) {
            return redirect()->back()->with('error', 'Solo quien creó esta actividad puede eliminarla.');
        }

        $actividadModel->delete($id);
        return redirect()->to(base_url('aulas?tab=actividades'))->with('mensaje', 'Actividad eliminada del cronograma.');
    }

    /**
     * Importador de posiciones en JSON del campus (mantenido por compatibilidad)
     */
    public function importar_json()
    {
        $res = (new HorarioSyncService())->importarPosicionesAulasJson();
        if (!$res['ok']) {
            return 'Error: ' . $res['error'];
        }

        return "¡Proceso exitoso! Aulas nuevas: {$res['importadas']}, Aulas actualizadas: {$res['actualizadas']}. Ya puedes <a href='" . base_url('mapa') . "'>ver el mapa</a>.";
    }

    /**
     * Búsqueda en tiempo real de materias y secciones para inscripción extemporánea
     */
    public function buscarExtemporaneas()
    {
        $usuarioId = (int) session()->get('usuario_id');
        if (!$usuarioId) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Sesión expirada.']);
        }

        $q = trim((string)$this->request->getGet('q'));
        $carrera = trim((string)$this->request->getGet('carrera'));
        if (empty($carrera) || $carrera === 'todas') {
            $carrera = null;
        }

        $db = \Config\Database::connect();

        // Secciones en las que el alumno ya está inscrito
        $inscritasRows = $db->table('inscripciones')->select('seccion_id')->where('estudiante_id', $usuarioId)->get()->getResultArray();
        $inscritasIds = !empty($inscritasRows) ? array_map('intval', array_column($inscritasRows, 'seccion_id')) : [];

        // Solicitudes pendientes de aprobación enviadas por el alumno
        $solicitudesPendientes = (new \App\Models\SolicitudExtemporaneaModel())->obtenerIdsSolicitudesPendientesEstudiante($usuarioId);

        $builder = $db->table('secciones')
            ->select('secciones.id, secciones.codigo_seccion, secciones.carrera, secciones.profesor_id, materias.nombre as materia, materias.codigo_materia, materias.semestre_trayecto, usuarios.primer_nombre as prof_nombre, usuarios.apellidos as prof_apellido')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->join('usuarios', 'usuarios.id = secciones.profesor_id', 'left');

        if (!empty($carrera)) {
            $builder->where('secciones.carrera', $carrera);
        }

        if (!empty($q)) {
            $builder->groupStart()
                ->like('materias.nombre', $q)
                ->orLike('materias.codigo_materia', $q)
                ->orLike('secciones.codigo_seccion', $q)
                ->groupEnd();
        }

        $secciones = $builder->orderBy('materias.nombre', 'ASC')->limit(25)->get()->getResultArray();

        foreach ($secciones as &$s) {
            $s['ya_inscrita'] = in_array((int)$s['id'], $inscritasIds, true);
            $s['solicitud_pendiente'] = in_array((int)$s['id'], $solicitudesPendientes, true);
        }

        return $this->response->setJSON([
            'success'   => true,
            'secciones' => $secciones
        ]);
    }

    /**
     * Envía una solicitud formal de inscripción extemporánea al docente encargado de la materia
     */
    public function inscribirExtemporanea()
    {
        $usuarioId = (int) session()->get('usuario_id');
        if (!$usuarioId) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'error' => 'Sesión expirada.']);
        }

        if (session('rol') === 'profesor') {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'Los profesores no inscriben asignaturas estudiantiles.']);
        }

        $seccionId = (int) $this->request->getPost('seccion_id');
        if ($seccionId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Identificador de sección inválido.']);
        }

        $db = \Config\Database::connect();

        $seccion = $db->table('secciones')
            ->select('secciones.*, materias.nombre as materia, materias.codigo_materia')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->where('secciones.id', $seccionId)
            ->get()
            ->getRowArray();

        if (!$seccion) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'error' => 'Sección académica no encontrada.']);
        }

        // 1. Verificar si ya está inscrita formalmente
        $existe = $db->table('inscripciones')
            ->where('estudiante_id', $usuarioId)
            ->where('seccion_id', $seccionId)
            ->countAllResults();

        if ($existe > 0) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'error' => 'Ya te encuentras formalmente inscrito en esta sección.']);
        }

        // 2. Verificar si ya existe una solicitud en estado pendiente
        $solicitudPendiente = $db->table('solicitudes_extemporaneas')
            ->where('estudiante_id', $usuarioId)
            ->where('seccion_id', $seccionId)
            ->where('estado', 'pendiente')
            ->countAllResults();

        if ($solicitudPendiente > 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false, 
                'error' => 'Ya enviaste una solicitud de inscripción para esta materia que está en espera de aprobación del docente.'
            ]);
        }

        $profesorId = !empty($seccion['profesor_id']) ? (int) $seccion['profesor_id'] : null;
        $ahora = date('Y-m-d H:i:s');

        // 3. Registrar la solicitud en la tabla de control
        $db->table('solicitudes_extemporaneas')->insert([
            'estudiante_id' => $usuarioId,
            'seccion_id'    => $seccionId,
            'profesor_id'   => $profesorId,
            'estado'        => 'pendiente',
            'created_at'    => $ahora,
            'updated_at'    => $ahora,
        ]);

        // 4. Notificar al docente encargado si ya está asignado a la sección
        if ($profesorId) {
            $estudiante = $db->table('usuarios')->where('id', $usuarioId)->get()->getRowArray();
            $nombreEst = trim(($estudiante['primer_nombre'] ?? '') . ' ' . ($estudiante['apellidos'] ?? ''));
            $cedulaEst = !empty($estudiante['cedula']) ? $estudiante['cedula'] : 'N/A';

            (new \App\Models\NotificacionModel())->insert([
                'estudiante_id'  => $profesorId, // El receptor es el docente
                'profesor_id'    => $usuarioId,
                'seccion_id'     => $seccionId,
                'tipo'           => 'general',
                'titulo'         => 'Nueva Solicitud de Materia Extemporánea',
                'mensaje'        => "El alumno {$nombreEst} (C.I. {$cedulaEst}) solicita autorización para cursar tu sección de {$seccion['materia']} (Secc. {$seccion['codigo_seccion']}).",
                'modalidad'      => 'presencial',
                'enlace_accion'  => 'docente?tab=solicitudes',
                'leido'          => 0,
                'created_at'     => $ahora,
            ]);
        }

        $mensajeExito = $profesorId
            ? "Solicitud enviada exitosamente al docente encargado de {$seccion['materia']} (Secc. {$seccion['codigo_seccion']}). Recibirás una notificación cuando sea revisada."
            : "Solicitud registrada para {$seccion['materia']} (Secc. {$seccion['codigo_seccion']}). Quedará en espera de asignación y autorización docente.";

        return $this->response->setJSON([
            'success' => true,
            'mensaje' => $mensajeExito
        ]);
    }
}
