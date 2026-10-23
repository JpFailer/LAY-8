<?php

namespace App\Controllers;

use App\Models\NotificacionModel;
use App\Models\InscripcionModel;
use App\Models\ClaseProgramadaModel;
use App\Models\EstadoClaseModel;

class NotificacionController extends BaseController
{
    /**
     * Obtiene las notificaciones del alumno autenticado.
     * Ejecuta automáticamente el verificador de clases próximas (10-15 minutos antes).
     */
    public function misNotificaciones()
    {
        $estudianteId = (int) session()->get('usuario_id');
        
        // Ejecutamos el motor de alertas inteligentes
        $this->ejecutarAlertasInteligentes($estudianteId);

        $notificacionModel = new NotificacionModel();
        $notificaciones = $notificacionModel->obtenerParaEstudiante($estudianteId);
        $noLeidas       = $notificacionModel->contarNoLeidas($estudianteId);

        return $this->response->setJSON([
            'success'            => true,
            'no_leidas'          => $noLeidas,
            'total'              => count($notificaciones),
            'notificaciones'     => $notificaciones
        ]);
    }

    /**
     * Conteo rápido de notificaciones no leídas para badge o campana
     */
    public function conteoNoLeidas()
    {
        $estudianteId = (int) session()->get('usuario_id');
        $notificacionModel = new NotificacionModel();

        return $this->response->setJSON([
            'success'   => true,
            'no_leidas' => $notificacionModel->contarNoLeidas($estudianteId)
        ]);
    }

    /**
     * Marcar una notificación como leída
     * Protegido por Row-Level Security: solo el dueño puede marcarla
     */
    public function marcarLeida($id)
    {
        $estudianteId = (int) session()->get('usuario_id');
        $notificacionModel = new NotificacionModel();

        $exito = $notificacionModel->marcarComoLeida((int) $id, $estudianteId);

        return $this->response->setJSON([
            'success' => $exito,
            'mensaje' => $exito ? 'Notificación marcada como leída.' : 'No se pudo actualizar la notificación.'
        ]);
    }

    /**
     * Motor de Alertas Inteligentes:
     * Cruza los horarios registrados de los estudiantes con la hora del reloj en Venezuela.
     * Si una clase comienza en los próximos 10 a 15 minutos, revisa el estado del profesor y notifica.
     */
    private function ejecutarAlertasInteligentes(int $estudianteId)
    {
        date_default_timezone_set('America/Caracas');
        $horaActual   = date('H:i:s');
        $horaMas15Min = date('H:i:s', strtotime('+15 minutes'));
        $fechaHoy     = date('Y-m-d');

        $diasInglesAEspanol = [
            'Monday'    => 'Lunes', 
            'Tuesday'   => 'Martes', 
            'Wednesday' => 'Miercoles', 
            'Thursday'  => 'Jueves', 
            'Friday'    => 'Viernes', 
            'Saturday'  => 'Sabado', 
            'Sunday'    => 'Domingo'
        ];
        $diaActual = $diasInglesAEspanol[date('l')];

        $inscripcionModel = new InscripcionModel();
        $seccionesEstudiante = $inscripcionModel->obtenerSeccionesDeEstudiante($estudianteId);

        if (empty($seccionesEstudiante)) {
            return;
        }

        $clasesModel       = new ClaseProgramadaModel();
        $estadoModel       = new EstadoClaseModel();
        $notificacionModel = new NotificacionModel();

        foreach ($seccionesEstudiante as $seccion) {
            $seccionId = (int) $seccion['id'];

            // Buscamos si tiene clase programada hoy que inicie en el intervalo [ahora, ahora + 15 min]
            $claseProxima = $clasesModel
                ->where('seccion_id', $seccionId)
                ->where('dia_semana', $diaActual)
                ->where('hora_inicio >=', $horaActual)
                ->where('hora_inicio <=', $horaMas15Min)
                ->first();

            if ($claseProxima) {
                // Verificamos si ya emitimos esta alerta hoy para no duplicarla
                $yaNotificado = $notificacionModel
                    ->where('estudiante_id', $estudianteId)
                    ->where('seccion_id', $seccionId)
                    ->where('tipo', 'clase_proxima')
                    ->where('DATE(created_at)', $fechaHoy)
                    ->first();

                if (!$yaNotificado) {
                    // Consultamos el estado rápido del docente si ya lo configuró
                    $estadoDocente = $estadoModel->obtenerEstadoHoy($seccionId);
                    
                    $modalidad = $estadoDocente ? $estadoDocente['modalidad'] : 'presencial';
                    $enlace    = $estadoDocente ? $estadoDocente['enlace_virtual'] : null;
                    $estadoTxt = $estadoDocente ? $estadoDocente['estado_rapido'] : 'En horario habitual';

                    $titulo = "Clase próxima en 15 min: {$seccion['materia']}";
                    $mensaje = "Tu clase en el {$claseProxima['aula']} inicia a las {$claseProxima['hora_inicio']}. Estado del docente: \"{$estadoTxt}\" (" . ucfirst($modalidad) . ").";

                    if ($modalidad === 'online' && $enlace) {
                        $mensaje .= " Accede al aula virtual aquí: {$enlace}";
                    }

                    $notificacionModel->insert([
                        'estudiante_id'  => $estudianteId,
                        'profesor_id'    => $seccion['profesor_id'] ?? null,
                        'seccion_id'     => $seccionId,
                        'tipo'           => 'clase_proxima',
                        'titulo'         => $titulo,
                        'mensaje'        => $mensaje,
                        'modalidad'      => $modalidad,
                        'enlace_virtual' => $enlace,
                        'leido'          => 0,
                        'created_at'     => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
    }
}
