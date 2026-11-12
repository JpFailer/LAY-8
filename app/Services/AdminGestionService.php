<?php

namespace App\Services;

use App\Models\AporteBibliotecaModel;
use App\Models\AuditoriaAdminModel;
use App\Models\AulasModel;
use App\Models\ClaseProgramadaModel;
use App\Models\UsuarioModel;

/**
 * Servicio de dominio para métricas, resúmenes administrativos y purga transaccional de usuarios.
 */
class AdminGestionService
{
    public function obtenerMetricasGlobales(): array
    {
        $db = \Config\Database::connect();

        $estudiantes = $db->table('usuarios')->where('rol', 'estudiante')->countAllResults();
        $profesores  = $db->table('usuarios')->where('rol', 'profesor')->where('estado_verificacion', 'aprobado')->countAllResults();
        $admins      = $db->table('usuarios')->whereIn('rol', ['administrador', 'admin'])->countAllResults();
        $baneados    = $db->fieldExists('estado_cuenta', 'usuarios')
            ? $db->table('usuarios')->where('estado_cuenta', 'baneado')->countAllResults()
            : 0;
        $solicitudesPendientes = $db->table('usuarios')->where('estado_verificacion', 'pendiente')->countAllResults();

        $aporteModel = new AporteBibliotecaModel();
        $totalAportes = 0;
        $espacioTotalBytes = 0;
        $aportesPorCategoria = [];

        try {
            $totalAportes = $aporteModel->countAllResults();
            $espacioTotalBytes = $aporteModel->espacioTotalBytes();
            $categoriasQuery = $aporteModel->select('categoria, count(id) as total')->groupBy('categoria')->findAll();
            foreach ($categoriasQuery as $c) {
                $aportesPorCategoria[$c['categoria']] = (int) $c['total'];
            }
        } catch (\Throwable $e) {}

        $aulasModel = new AulasModel();
        $totalAulas = 0;
        $aulasDisponibles = 0;
        $aulasMantenimiento = 0;

        try {
            $totalAulas = $aulasModel->countAllResults();
            if ($db->fieldExists('estado', 'aulas')) {
                $aulasDisponibles = $aulasModel->where('estado', 'disponible')->countAllResults();
                $aulasMantenimiento = $aulasModel->whereIn('estado', ['mantenimiento', 'inhabilitada'])->countAllResults();
            } else {
                $aulasDisponibles = $totalAulas;
            }
        } catch (\Throwable $e) {}

        return [
            'usuarios' => [
                'total'           => $estudiantes + $profesores + $admins,
                'estudiantes'     => $estudiantes,
                'profesores'      => $profesores,
                'administradores' => $admins,
                'baneados'        => $baneados,
            ],
            'profesores' => [
                'solicitudes_pendientes' => $solicitudesPendientes,
            ],
            'biblioteca' => [
                'total_aportes'      => $totalAportes,
                'espacio_ocupado_mb' => round($espacioTotalBytes / 1048576, 2),
                'por_categoria'      => $aportesPorCategoria,
            ],
            'aulas' => [
                'total'            => $totalAulas,
                'disponibles'      => $aulasDisponibles,
                'en_mantenimiento' => $aulasMantenimiento,
            ],
        ];
    }

    public function obtenerDatosDashboard(UsuarioModel $usuarioModel, AuditoriaAdminModel $auditoriaModel): array
    {
        $metricasGlobales = $this->obtenerMetricasGlobales();

        $solicitudes = $usuarioModel
            ->where('estado_verificacion', 'pendiente')
            ->orderBy('id', 'ASC')
            ->findAll();

        $profesoresActivos = $usuarioModel
            ->select('usuarios.id, usuarios.cedula, usuarios.primer_nombre, usuarios.apellidos, usuarios.nombre_usuario, usuarios.correo, COUNT(secciones.id) as total_secciones_asignadas')
            ->join('secciones', 'secciones.profesor_id = usuarios.id', 'left')
            ->where('usuarios.rol', 'profesor')
            ->where('usuarios.estado_verificacion', 'aprobado')
            ->groupBy('usuarios.id')
            ->orderBy('usuarios.primer_nombre', 'ASC')
            ->findAll();

        $usuarios = $usuarioModel
            ->select('id, cedula, primer_nombre, apellidos, nombre_usuario, correo, rol, carrera, estado_verificacion, estado_cuenta, motivo_baneo, fecha_registro, ultimo_acceso')
            ->orderBy('id', 'DESC')
            ->findAll(100);

        $aporteModel = new AporteBibliotecaModel();
        $aportes = [];
        try {
            $aportes = $aporteModel->obtenerTodosConUsuario();
        } catch (\Throwable $e) {}

        $auditoriaLogs = $auditoriaModel->ultimosRegistros(40);

        $carrerasDefinidas = [
            'ING-INF' => ['nombre' => 'Ingeniería en Informática', 'escuela' => 'Escuela de Informática', 'icono' => 'bx-code-alt', 'color' => '#2563eb'],
            'ING-MAR' => ['nombre' => 'Ingeniería Marítima', 'escuela' => 'Escuela de Náutica', 'icono' => 'bx-anchor', 'color' => '#0284c7'],
            'ING-AMB' => ['nombre' => 'Ingeniería Ambiental', 'escuela' => 'Escuela de Ciencias Aplicadas', 'icono' => 'bx-leaf', 'color' => '#10b981'],
            'ADM'     => ['nombre' => 'Licenciatura en Administración', 'escuela' => 'Escuela de Ciencias Sociales', 'icono' => 'bx-briefcase-alt-2', 'color' => '#f59e0b'],
            'TUR'     => ['nombre' => 'Licenciatura en Turismo', 'escuela' => 'Escuela de Turismo', 'icono' => 'bx-compass', 'color' => '#ec4899'],
        ];

        $carpetaScripts = ROOTPATH . 'scripts_python';
        $carpetaHorarios = $carpetaScripts . DIRECTORY_SEPARATOR . 'horarios';
        $claseModel = new ClaseProgramadaModel();

        $clasesPorCarrera = [];
        foreach ($claseModel->select('carrera, COUNT(*) AS total')->groupBy('carrera')->findAll() as $rc) {
            $clasesPorCarrera[(string) ($rc['carrera'] ?? '')] = (int) ($rc['total'] ?? 0);
        }

        $carrerasHorarios = [];
        foreach ($carrerasDefinidas as $cod => $info) {
            $rutaPdf = $carpetaHorarios . DIRECTORY_SEPARATOR . "horario_{$cod}.pdf";
            if (!file_exists($rutaPdf) && $cod === 'ING-INF') {
                $rutaPdf = $carpetaScripts . DIRECTORY_SEPARATOR . 'horario.pdf';
            }

            $existe = file_exists($rutaPdf);
            $carrerasHorarios[$cod] = array_merge($info, [
                'codigo'              => $cod,
                'existe_archivo'      => $existe,
                'tamano_kb'           => $existe ? round(filesize($rutaPdf) / 1024, 2) : 0,
                'ultima_modificacion' => $existe ? date('d/m/Y H:i', filemtime($rutaPdf)) : 'No cargado',
                'total_clases'        => $clasesPorCarrera[$cod] ?? 0,
                'nombre_archivo'      => $existe ? basename($rutaPdf) : "horario_{$cod}.pdf",
            ]);
        }

        $rutaPdfHorario = $carpetaScripts . DIRECTORY_SEPARATOR . 'horario.pdf';
        $horarioInfo = [
            'existe_archivo'      => file_exists($rutaPdfHorario),
            'tamano_kb'           => file_exists($rutaPdfHorario) ? round(filesize($rutaPdfHorario) / 1024, 2) : 0,
            'ultima_modificacion' => file_exists($rutaPdfHorario) ? date('d/m/Y H:i', filemtime($rutaPdfHorario)) : 'No cargado',
            'total_clases'        => $metricasGlobales['aulas']['total'] > 0 ? array_sum($clasesPorCarrera) : $claseModel->countAllResults(),
            'total_aulas'         => $metricasGlobales['aulas']['total'],
        ];

        return [
            'metricas' => [
                'estudiantes'            => $metricasGlobales['usuarios']['estudiantes'],
                'profesores'             => $metricasGlobales['usuarios']['profesores'],
                'admins'                 => $metricasGlobales['usuarios']['administradores'],
                'baneados'               => $metricasGlobales['usuarios']['baneados'],
                'total_usuarios'         => $metricasGlobales['usuarios']['total'],
                'solicitudes_pendientes' => count($solicitudes),
                'total_aportes'          => count($aportes),
            ],
            'solicitudes'       => $solicitudes,
            'profesoresActivos' => $profesoresActivos,
            'usuarios'          => $usuarios,
            'aportes'           => $aportes,
            'auditoria'         => $auditoriaLogs,
            'horarioInfo'       => $horarioInfo,
            'carrerasHorarios'  => $carrerasHorarios,
        ];
    }

    public function eliminarUsuarioConLimpieza(UsuarioModel $usuarioModel, int $id, bool $borrarAportes): void
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $db->table('notificaciones')
                ->where('estudiante_id', $id)
                ->orWhere('profesor_id', $id)
                ->delete();
        } catch (\Throwable $e) {}

        try {
            $db->table('inscripciones')->where('estudiante_id', $id)->delete();
            $db->table('clases_programadas')->where('estudiante_id', $id)->delete();
        } catch (\Throwable $e) {}

        try {
            $db->table('noticias_likes')->where('usuario_id', $id)->delete();
            $db->table('noticias_comentarios')->where('usuario_id', $id)->delete();
            $db->table('actividades_estudiante_estado')->where('estudiante_id', $id)->delete();
            $db->table('entregas_actividades')->where('estudiante_id', $id)->delete();
            $db->table('estados_clase_profesor')->where('profesor_id', $id)->delete();

            $actividades = $db->table('actividades_clase')->select('id')->where('creado_por', $id)->get()->getResultArray();
            if (!empty($actividades)) {
                $actIds = array_column($actividades, 'id');
                $db->table('entregas_actividades')->whereIn('actividad_id', $actIds)->delete();
                $db->table('actividades_estudiante_estado')->whereIn('actividad_id', $actIds)->delete();
                $db->table('actividades_clase')->whereIn('id', $actIds)->delete();
            }

            $noticias = $db->table('noticias_muro')->select('id')->where('autor_id', $id)->get()->getResultArray();
            if (!empty($noticias)) {
                $nIds = array_column($noticias, 'id');
                $db->table('noticias_likes')->whereIn('noticia_id', $nIds)->delete();
                $db->table('noticias_comentarios')->whereIn('noticia_id', $nIds)->delete();
                $db->table('noticias_muro')->whereIn('id', $nIds)->delete();
            }
        } catch (\Throwable $e) {}

        $aporteModel = new AporteBibliotecaModel();
        try {
            if ($borrarAportes) {
                $aportes = $aporteModel->where('usuario_id', $id)->findAll();
                foreach ($aportes as $ap) {
                    $ruta = WRITEPATH . $ap['ruta_archivo'];
                    if (file_exists($ruta)) {
                        @unlink($ruta);
                    }
                    $aporteModel->delete($ap['id']);
                }
            } else {
                $db->table('aportes_biblioteca')->where('usuario_id', $id)->update(['usuario_id' => null]);
            }
        } catch (\Throwable $e) {}

        $usuarioModel->delete($id);
        $db->transComplete();
    }
}
