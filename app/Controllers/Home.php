<?php

namespace App\Controllers;

use App\Models\NoticiaMuroModel;
use App\Models\NoticiaComentarioModel;

class Home extends BaseController
{
    public function index()
    {
        $usuarioId = (int) session()->get('usuario_id');
        $userRol   = session()->get('rol') ?? 'estudiante';

        // 1. Detección de día de la semana y fecha en español
        date_default_timezone_set('America/Caracas');
        $diasMap = [
            1 => 'Lunes', 
            2 => 'Martes', 
            3 => 'Miercoles', 
            4 => 'Jueves', 
            5 => 'Viernes', 
            6 => 'Sabado', 
            7 => 'Domingo'
        ];
        $mesesMap = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        $diaNumero = (int)date('N');
        $diaSemana = $diasMap[$diaNumero] ?? 'Lunes';
        $fechaTexto = $diaSemana . ', ' . date('j') . ' de ' . ($mesesMap[(int)date('n')] ?? date('F')) . ' de ' . date('Y');

        $db = \Config\Database::connect();
        $horaActual = date('H:i:s');

        // 2. Consulta de Clases del Día (Jornada Diaria)
        $clasesHoy = [];
        if ($userRol === 'profesor') {
            $sqlClases = "SELECT cp.*, m.nombre as materia_nombre, m.codigo_materia, s.codigo_seccion
                          FROM clases_programadas cp
                          JOIN secciones s ON s.id = cp.seccion_id
                          JOIN materias m ON m.id = s.materia_id
                          WHERE cp.dia_semana = ?
                            AND s.profesor_id = ?
                          ORDER BY cp.hora_inicio ASC";
            $clasesHoy = $db->query($sqlClases, [$diaSemana, $usuarioId])->getResultArray();
        } else {
            $sqlClases = "SELECT cp.*, m.nombre as materia_nombre, m.codigo_materia, s.codigo_seccion
                          FROM clases_programadas cp
                          JOIN secciones s ON s.id = cp.seccion_id
                          JOIN materias m ON m.id = s.materia_id
                          WHERE cp.dia_semana = ?
                            AND (
                                (cp.seccion_id IN (SELECT seccion_id FROM inscripciones WHERE estudiante_id = ?) AND cp.estudiante_id IS NULL)
                                OR cp.estudiante_id = ?
                            )
                          ORDER BY cp.hora_inicio ASC";
            $clasesHoy = $db->query($sqlClases, [$diaSemana, $usuarioId, $usuarioId])->getResultArray();
        }

        foreach ($clasesHoy as &$clase) {
            $clase['en_curso'] = ($horaActual >= $clase['hora_inicio'] && $horaActual <= $clase['hora_fin']);
            $clase['hora_inicio_formato'] = substr($clase['hora_inicio'], 0, 5);
            $clase['hora_fin_formato']    = substr($clase['hora_fin'], 0, 5);
        }
        unset($clase);

        // 3. Los 3 Aportes más recientes de la Biblioteca
        $sqlAportes = "SELECT a.*, u.primer_nombre, u.apellidos, u.foto_perfil
                       FROM aportes_biblioteca a
                       LEFT JOIN usuarios u ON u.id = a.usuario_id
                       ORDER BY a.fecha_subida DESC
                       LIMIT 3";
        $aportesRecientes = $db->query($sqlAportes)->getResultArray();

        // 4. Tablón de Noticias con Likes y Comentarios
        $noticiaModel = new NoticiaMuroModel();
        $comentarioModel = new NoticiaComentarioModel();

        // Semilla automática si la tabla de noticias está vacía
        if ($noticiaModel->countAllResults() === 0) {
            $adminUser = $db->table('usuarios')->whereIn('rol', ['admin', 'administrador', 'profesor'])->get()->getRowArray();
            $autorSeedId = $adminUser ? (int)$adminUser['id'] : $usuarioId;

            if ($autorSeedId > 0) {
                $noticiaModel->insert([
                    'autor_id'          => $autorSeedId,
                    'titulo'            => '¡Bienvenidos a la plataforma académica LAY-8!',
                    'contenido'         => "Nos complace darles la bienvenida a LAY-8, el entorno universitario integral de la Universidad Marítima del Caribe (UMC).\n\nAquí podrán consultar en tiempo real la disponibilidad de aulas en el campus, gestionar sus horarios, acceder al repositorio colaborativo de la biblioteca y mantenerse al día con los comunicados oficiales de la comunidad.",
                    'categoria'         => 'Institucional',
                    'fijado'            => 1,
                    'total_likes'       => 4,
                    'total_comentarios' => 1
                ]);

                $idNoticiaSeed = $noticiaModel->getInsertID();
                if ($idNoticiaSeed) {
                    $comentarioModel->insert([
                        'noticia_id' => $idNoticiaSeed,
                        'usuario_id' => $autorSeedId,
                        'parent_id'  => null,
                        'contenido'  => '¡Éxitos a toda la comunidad universitaria en este nuevo período académico! Recuerden revisar sus horarios asignados.',
                    ]);
                }
            }
        }

        $noticias = $noticiaModel->obtenerNoticiasConInteraccion($usuarioId, 25);

        // Cargar los hilos de comentarios para cada noticia
        foreach ($noticias as &$noticia) {
            $noticia['hilos_comentarios'] = $comentarioModel->obtenerHilosPorNoticia((int)$noticia['id']);
        }
        unset($noticia);

        $datos = [
            'diaSemana'        => $diaSemana,
            'fechaTexto'       => $fechaTexto,
            'clasesHoy'        => $clasesHoy,
            'aportesRecientes' => $aportesRecientes,
            'noticias'         => $noticias,
            'userRol'          => $userRol,
            'usuarioId'        => $usuarioId
        ];

        return view('Dashboard/feed', $datos);
    }

    public function ajustes()
    {
        return view('Dashboard/ajustes_view');
    }

    public function terminos()
    {
        return view('Home/terminos_view');
    }
}
