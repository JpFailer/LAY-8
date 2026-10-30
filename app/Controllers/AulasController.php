<?php 

namespace App\Controllers;

use App\Models\AulasModel;
use App\Models\SeccionModel;
use App\Models\InscripcionModel;
use App\Models\ClaseProgramadaModel;
use App\Models\EstadoClaseModel;
use App\Models\ActividadClaseModel;
use App\Models\UsuarioModel;
use App\Services\VerificadorInscripcionService;

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
        $usuarioId = (int) session()->get('usuario_id');
        $rol       = session()->get('rol') ?? 'estudiante';

        $db = \Config\Database::connect();
        $inscripcionModel = new InscripcionModel();
        $claseModel       = new ClaseProgramadaModel();
        $estadoModel      = new EstadoClaseModel();
        $actividadModel   = new ActividadClaseModel();
        $aulasModel       = new AulasModel();

        // 1. Obtener secciones del usuario (estudiante o profesor)
        $secciones = [];
        if ($rol === 'profesor') {
            $secciones = (new SeccionModel())
                ->select('secciones.*, materias.nombre as materia, materias.codigo_materia, materias.unidades_credito, materias.semestre_trayecto')
                ->join('materias', 'materias.id = secciones.materia_id')
                ->where('secciones.profesor_id', $usuarioId)
                ->findAll();
        } else {
            $secciones = $inscripcionModel
                ->select('secciones.*, materias.nombre as materia, materias.codigo_materia, materias.unidades_credito, materias.semestre_trayecto, usuarios.primer_nombre as prof_nombre, usuarios.apellidos as prof_apellido')
                ->join('secciones', 'secciones.id = inscripciones.seccion_id')
                ->join('materias', 'materias.id = secciones.materia_id')
                ->join('usuarios', 'usuarios.id = secciones.profesor_id', 'left')
                ->where('inscripciones.estudiante_id', $usuarioId)
                ->findAll();
        }

        $seccionesIds = !empty($secciones) ? array_column($secciones, 'id') : [];

        // 2. Enriquecer cada materia/sección con su estado en vivo, horarios y tareas pendientes
        foreach ($secciones as &$sec) {
            $secId = (int)$sec['id'];
            $sec['estado_hoy'] = $estadoModel->obtenerEstadoHoy($secId);

            // Horarios de esta sección
            $sec['horarios'] = $claseModel
                ->where('seccion_id', $secId)
                ->groupStart()
                    ->where('estudiante_id', null)
                    ->orWhere('estudiante_id', $usuarioId)
                ->groupEnd()
                ->orderBy('hora_inicio', 'ASC')
                ->findAll();

            // Total de actividades de esta materia
            $sec['total_actividades'] = $db->table('actividades_clase')
                ->where('seccion_id', $secId)
                ->countAllResults();
        }
        unset($sec);

        // 3. Obtener el Horario Semanal Completo del Estudiante
        $horarioSemanal = [];
        if (!empty($seccionesIds)) {
            $horarioSemanal = $claseModel
                ->select('clases_programadas.*, materias.nombre as materia, materias.codigo_materia, secciones.codigo_seccion')
                ->join('secciones', 'secciones.id = clases_programadas.seccion_id')
                ->join('materias', 'materias.id = secciones.materia_id')
                ->whereIn('clases_programadas.seccion_id', $seccionesIds)
                ->groupStart()
                    ->where('clases_programadas.estudiante_id', null)
                    ->orWhere('clases_programadas.estudiante_id', $usuarioId)
                ->groupEnd()
                ->findAll();
        }

        // 4. Mapear horarios en una matriz [Dia][Intervalo] para renderizar la grilla oficial
        $grillaHorarios = [];
        $diasSemana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
        foreach ($diasSemana as $d) {
            $grillaHorarios[$d] = [];
        }

        foreach ($horarioSemanal as $bloque) {
            $dia = $bloque['dia_semana'];
            if (isset($grillaHorarios[$dia])) {
                $inicioFormato = date('H:i', strtotime($bloque['hora_inicio']));
                $finFormato    = date('H:i', strtotime($bloque['hora_fin']));
                $claveIntervalo = $inicioFormato . ' - ' . $finFormato;
                $grillaHorarios[$dia][] = [
                    'id'          => $bloque['id'],
                    'seccion_id'  => $bloque['seccion_id'],
                    'materia'     => $bloque['materia'],
                    'codigo'      => $bloque['codigo_materia'],
                    'seccion'     => $bloque['codigo_seccion'],
                    'aula'        => $bloque['aula'],
                    'hora_inicio' => $bloque['hora_inicio'],
                    'hora_fin'    => $bloque['hora_fin'],
                    'intervalo'   => $claveIntervalo,
                    'es_personal' => !empty($bloque['estudiante_id']),
                ];
            }
        }

        // 5. Cronograma de Actividades / Pendientes tipo Classroom
        $actividades = $actividadModel->obtenerParaEstudiante($usuarioId);

        // 6. Lista de aulas del campus para el selector del armado de horario
        $todasAulas = $aulasModel->orderBy('alias_pdf', 'ASC')->findAll();

        $datosVista = [
            'secciones'        => $secciones,
            'totalMaterias'    => count($secciones),
            'horarioSemanal'   => $horarioSemanal,
            'grillaHorarios'   => $grillaHorarios,
            'actividades'      => $actividades,
            'todasAulas'       => $todasAulas,
            'bloquesOficiales' => $this->bloquesOficiales,
            'diasSemana'       => $diasSemana,
            'rolUsuario'       => $rol,
        ];

        return view('Dashboard/aulas_view', $datosVista); 
    }

    /**
     * Procesa la subida del Registro de Inscripción o del Horario en PDF
     */
    public function subirDocumentoHorario()
    {
        $usuarioId = (int) session()->get('usuario_id');
        if (!$usuarioId) {
            return redirect()->to('/login')->with('error', 'Sesión expirada.');
        }

        $archivo = $this->request->getFile('documento_pdf');
        if (!$archivo || !$archivo->isValid() || $archivo->getClientMimeType() !== 'application/pdf') {
            return redirect()->back()->with('error', 'Por favor selecciona un archivo PDF válido emitido por la UMC.');
        }

        $carpetaUploads = WRITEPATH . 'uploads/documentos_aulas';
        if (!is_dir($carpetaUploads)) {
            mkdir($carpetaUploads, 0777, true);
        }

        $nuevoNombre = $archivo->getRandomName();
        $archivo->move($carpetaUploads, $nuevoNombre);
        $rutaCompleta = $carpetaUploads . '/' . $nuevoNombre;

        try {
            $verificador = new VerificadorInscripcionService();
            $analisis = $verificador->verificar($rutaCompleta);

            if ($analisis['es_valido'] && !empty($analisis['materias'])) {
                // Comprobación de Titularidad: Cotejo de Cédula con la cuenta del usuario en sesión
                $usuarioModel = new \App\Models\UsuarioModel();
                $usuario = $usuarioModel->find($usuarioId);
                $cedulaPdf = preg_replace('/\D/', '', (string)($analisis['cedula'] ?? ''));
                $cedulaUsuario = preg_replace('/\D/', '', (string)($usuario['cedula'] ?? ''));
                $esAdmin = ($usuario['rol'] ?? session('rol')) === 'administrador';

                if (!$esAdmin && !empty($cedulaPdf) && !empty($cedulaUsuario) && $cedulaPdf !== $cedulaUsuario) {
                    @unlink($rutaCompleta);
                    return redirect()->back()->with('error', "Seguridad UMC: El documento pertenece a la cédula {$cedulaPdf}, la cual no coincide con tu usuario ({$cedulaUsuario}).");
                }

                // Autocompletar la cédula del usuario en BD si aún no la tenía
                if (empty($cedulaUsuario) && !empty($cedulaPdf)) {
                    $usuarioModel->update($usuarioId, ['cedula' => $cedulaPdf]);
                    session()->set('cedula', $cedulaPdf);
                }

                // Sincronizar carrera del usuario si fue certificada en el documento oficial
                if (!empty($analisis['carrera_codigo']) && (empty($usuario['carrera']) || $usuario['carrera'] !== $analisis['carrera_codigo'])) {
                    $usuarioModel->update($usuarioId, ['carrera' => $analisis['carrera_codigo']]);
                    session()->set('carrera', $analisis['carrera_codigo']);
                }

                // Es un Registro de Inscripción UMC oficial (UPE-002 o Forma 006 REE)
                $inscritas = $verificador->inscribirEstudianteEnMaterias($usuarioId, $analisis['materias'], $analisis['periodo'], true, $analisis['carrera_codigo'] ?? null);
                @unlink($rutaCompleta);

                return redirect()->to('/aulas')->with('mensaje', "¡Registro de Inscripción validado con éxito! Se certificaron y asignaron {$inscritas} materias/aulas a tu cuenta.");
            }

            // Si no fue detectado como Registro de Inscripción, verificar si es Horario Académico General (CGD-001)
            $textoLineas = $verificador->extraerLineasDePdf($rutaCompleta);
            $textoCompleto = implode(' ', $textoLineas);

            if (preg_match('/HORARIO\s+ACAD[EÉ]MICO/i', $textoCompleto) || preg_match('/CGD-001/i', $textoCompleto)) {
                @unlink($rutaCompleta);
                return redirect()->to('/aulas')->with('error', 'El documento adjunto es un Horario Académico General institucional (CGD-001). Los horarios generales son configurados por el Administrador. Para sincronizar tus asignaturas debes subir tu Registro de Inscripción Estudiantil individual (Formato Web UPE-002 o Forma 006).');
            }

            @unlink($rutaCompleta);
            return redirect()->back()->with('error', 'El documento no coincide con el formato oficial de Registro de Inscripción (UPE-002) ni Horario Académico (CGD-001) de la UMC.');

        } catch (\Throwable $e) {
            if (file_exists($rutaCompleta)) @unlink($rutaCompleta);
            return redirect()->back()->with('error', 'Error al procesar el documento PDF: ' . $e->getMessage());
        }
    }

    /**
     * Guarda un bloque de clase de forma manual asistida por plantilla
     * Regla estricta: La materia DEBE estar inscrita por el estudiante
     */
    public function guardarBloqueManual()
    {
        $usuarioId = (int) session()->get('usuario_id');
        if (!$usuarioId) {
            return redirect()->to('/login')->with('error', 'Sesión expirada');
        }

        $seccionId   = (int) $this->request->getPost('seccion_id');
        $diaSemana   = trim((string) $this->request->getPost('dia_semana'));
        $bloqueHoras = trim((string) $this->request->getPost('bloque_horas')); // Formato "07:15:00|08:00:00"
        $aula        = trim((string) $this->request->getPost('aula'));

        // 1. Validar que la sección le pertenezca a las materias inscritas del alumno
        $db = \Config\Database::connect();
        $estaInscrito = $db->table('inscripciones')
            ->where('estudiante_id', $usuarioId)
            ->where('seccion_id', $seccionId)
            ->countAllResults();

        // Si es profesor, validar que sea el profesor de la sección
        $esProfesorSeccion = false;
        if (session()->get('rol') === 'profesor') {
            $esProfesorSeccion = ($db->table('secciones')
                ->where('id', $seccionId)
                ->where('profesor_id', $usuarioId)
                ->countAllResults() > 0);
        }

        if ($estaInscrito === 0 && !$esProfesorSeccion) {
            return redirect()->back()->with('error', 'Acceso restringido: Solo puedes programar horarios de materias que tengas formalmente inscritas en tu registro.');
        }

        // 2. Extraer horas de inicio y fin
        $partesHoras = explode('|', $bloqueHoras);
        if (count($partesHoras) !== 2) {
            return redirect()->back()->with('error', 'Selecciona un bloque de hora oficial válido de la UMC.');
        }
        $horaInicio = trim($partesHoras[0]);
        $horaFin    = trim($partesHoras[1]);

        if (empty($diaSemana) || empty($aula)) {
            return redirect()->back()->with('error', 'Por favor completa el día y el aula física correspondiente.');
        }

        // 3. Insertar o registrar el bloque de clase
        $claseModel = new ClaseProgramadaModel();

        // Evitar duplicados idénticos
        $duplicado = $claseModel->where('seccion_id', $seccionId)
            ->where('dia_semana', $diaSemana)
            ->where('hora_inicio', $horaInicio)
            ->first();

        if ($duplicado) {
            return redirect()->back()->with('error', 'Ya existe un bloque de clase asignado en este mismo día y horario para esta materia.');
        }

        $seccionData = $db->table('secciones')->where('id', $seccionId)->get()->getRowArray();
        $carreraBloque = $seccionData['carrera'] ?? (session('carrera') ?: 'ING-INF');

        $claseModel->insert([
            'seccion_id'    => $seccionId,
            'estudiante_id' => ($estaInscrito > 0) ? $usuarioId : null,
            'carrera'       => $carreraBloque,
            'dia_semana'    => $diaSemana,
            'hora_inicio'   => $horaInicio,
            'hora_fin'      => $horaFin,
            'aula'          => $aula
        ]);

        return redirect()->to('/aulas')->with('mensaje', '¡Bloque de horario programado exitosamente!');
    }

    /**
     * Elimina un bloque de horario personal
     */
    public function eliminarBloqueManual(int $id)
    {
        $usuarioId = (int) session()->get('usuario_id');
        $rol       = session()->get('rol') ?? 'estudiante';
        $claseModel = new ClaseProgramadaModel();

        $bloque = $claseModel->find($id);
        if (!$bloque) {
            return redirect()->back()->with('error', 'Bloque de horario no encontrado.');
        }

        // Si es un bloque oficial de la universidad (estudiante_id es null), solo profesores o administradores pueden eliminarlo
        if (empty($bloque['estudiante_id'])) {
            if ($rol !== 'administrador' && $rol !== 'profesor') {
                return redirect()->back()->with('error', 'No tienes permisos para eliminar bloques oficiales de horario.');
            }
        } else {
            // Si es un bloque personal, solo el alumno que lo creó o un administrador pueden eliminarlo
            if ((int)$bloque['estudiante_id'] !== $usuarioId && $rol !== 'administrador') {
                return redirect()->back()->with('error', 'No tienes permisos para eliminar este bloque de clase.');
            }
        }

        $claseModel->delete($id);
        return redirect()->to('/aulas')->with('mensaje', 'Bloque de horario eliminado.');
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

        $seccionId     = (int) $this->request->getPost('seccion_id');
        $titulo        = strip_tags(trim((string)$this->request->getPost('titulo')));
        $descripcion   = strip_tags(trim((string)$this->request->getPost('descripcion')));
        $tipo          = trim((string)$this->request->getPost('tipo'));
        $fechaEntrega  = trim((string)$this->request->getPost('fecha_entrega'));
        $ponderacion   = (float) $this->request->getPost('ponderacion');

        if (empty($titulo) || empty($fechaEntrega) || $seccionId <= 0) {
            return redirect()->back()->with('error', 'Por favor ingresa el título, la materia y la fecha de entrega de la actividad.');
        }

        $tiposValidos = ['tarea', 'parcial', 'taller', 'proyecto', 'lectura'];
        if (!in_array($tipo, $tiposValidos, true)) {
            $tipo = 'tarea';
        }

        $rol = session()->get('rol') ?? 'estudiante';
        $esProfesor = ($rol === 'profesor');

        // Validar que el usuario pertenezca a la sección (inscrito o profesor asignado)
        $db = \Config\Database::connect();
        if ($esProfesor) {
            $esProfesorSeccion = ($db->table('secciones')
                ->where('id', $seccionId)
                ->where('profesor_id', $usuarioId)
                ->countAllResults() > 0);
            if (!$esProfesorSeccion && $rol !== 'administrador') {
                return redirect()->back()->with('error', 'Solo puedes crear actividades para tus secciones asignadas.');
            }
        } else {
            $estaInscrito = ($db->table('inscripciones')
                ->where('estudiante_id', $usuarioId)
                ->where('seccion_id', $seccionId)
                ->countAllResults() > 0);
            if (!$estaInscrito && $rol !== 'administrador') {
                return redirect()->back()->with('error', 'Solo puedes crear tareas en materias que tengas formalmente inscritas.');
            }
        }

        $actividadModel = new ActividadClaseModel();
        $actividadModel->insert([
            'seccion_id'    => $seccionId,
            'creado_por'    => $usuarioId,
            'es_personal'   => $esProfesor ? 0 : 1,
            'titulo'        => $titulo,
            'descripcion'   => $descripcion ?: null,
            'tipo'          => $tipo,
            'fecha_entrega' => $fechaEntrega,
            'ponderacion'   => $ponderacion ?: 0.00,
        ]);

        return redirect()->to('/aulas')->with('mensaje', '¡Actividad agregada al cronograma con éxito!');
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

        if ((int)$actividad['creado_por'] !== $usuarioId && session()->get('rol') !== 'administrador') {
            return redirect()->back()->with('error', 'Solo quien creó esta actividad puede eliminarla.');
        }

        $actividadModel->delete($id);
        return redirect()->to('/aulas')->with('mensaje', 'Actividad eliminada del cronograma.');
    }

    /**
     * Importador de posiciones en JSON del campus (mantenido por compatibilidad)
     */
    public function importar_json() 
    {
        $aulasModel = new AulasModel();

        $rutaJson = ROOTPATH . 'public/posiciones_aulas.json';
        if (!file_exists($rutaJson)) {
            return "Error: No se encontró el archivo de posiciones en " . $rutaJson;
        }

        $json_content = file_get_contents($rutaJson);
        $aulas = json_decode($json_content, true);

        if (!$aulas) {
            return "Error: No se pudo decodificar el archivo JSON.";
        }

        $importadas = 0;
        $actualizadas = 0;

        foreach ($aulas as $aula) {
            $nombre_json = $aula['aula'];
            $partes = explode("(", $nombre_json);
            $alias_pdf = trim($partes[0]);

            $datos = [
                'nombre_json' => $nombre_json,
                'alias_pdf'   => $alias_pdf,
                'coord_x'     => $aula['coord_x'],
                'coord_y'     => $aula['coord_y']
            ];

            $existente = $aulasModel->where('nombre_json', $nombre_json)->first();
            if ($existente) {
                $aulasModel->update($existente['id'], $datos);
                $actualizadas++;
            } else {
                $aulasModel->insert($datos);
                $importadas++;
            }
        }

        return "¡Proceso exitoso! Aulas nuevas: {$importadas}, Aulas actualizadas: {$actualizadas}. Ya puedes <a href='" . base_url('mapa') . "'>ver el mapa</a>.";
    }
}
