<?php 

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\MateriaModel;
use App\Models\SeccionModel;
use App\Models\ClaseProgramadaModel;
use App\Models\AulasModel; // Añadido para mantener el código limpio

class MapaController extends Controller {

    // 1. PROCESAMIENTO DEL PDF A LA BASE DE DATOS
    public function python_horarios(){
        
        $carpeta_scripts = ROOTPATH . 'scripts_python';
        $comando = 'cd "' . $carpeta_scripts . '" && "venv\Scripts\python.exe" leer_horario.py 2>&1';
        
        $json_crudo = shell_exec($comando);

        $inicio = strpos($json_crudo, '[');
        $fin = strrpos($json_crudo, ']');

        if ($inicio !== false && $fin !== false) {
            
            $json_limpio = substr($json_crudo, $inicio, $fin - $inicio + 1);
            $json_decodificado = json_decode($json_limpio, true);

            if ($json_decodificado === null) {
                dd("Sigue fallando. Motivo: " . json_last_error_msg());
            } else {
                
                $materiaModel = new MateriaModel();
                $seccionModel = new SeccionModel();
                $claseModel   = new ClaseProgramadaModel();

                $dias_semana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];
                $clases_insertadas = 0;
                $memoria_dia = [];

                $hora_inicio_global = '';
                $hora_fin_global    = '';

                foreach ($json_decodificado as $fila) {
                    
                    if (!empty($fila['Hora'])) {
                        $horas_separadas = explode("\n", $fila['Hora']);
                        $hora_inicio_global = trim($horas_separadas[0] ?? ''); 
                        $hora_fin_global    = trim($horas_separadas[1] ?? '');
                    } else {
                        if (empty($hora_inicio_global)) continue;
                    }

                    $hora_inicio = $hora_inicio_global;
                    $hora_fin    = $hora_fin_global;

                    foreach ($dias_semana as $dia) {
                        
                        if (!empty($fila[$dia])) {
                            
                            $lineas = explode("\n", $fila[$dia]);
                            $lineas_limpias = array_values(array_filter(array_map('trim', $lineas)));
                            
                            $lineas_utiles = [];
                            foreach ($lineas_limpias as $linea) {
                                
                                // 1. NORMALIZADOR DE GUIONES
                                $linea = preg_replace('/[–—−‐‑‒―]/u', '-', $linea);

                                //  2. FILTRO ANTI-BASURA
                                if (preg_match('/^(T|A|R|D|E|N|C|H|0)$/i', $linea)) continue;
                                if (preg_match('/^SECCI[OÓ]N/i', $linea)) continue;
                                
                                $lineas_utiles[] = $linea;
                            }

                            if (count($lineas_utiles) === 0) continue; 

                            $bloques_materias = [];
                            $indice = -1;

                            foreach ($lineas_utiles as $linea) {
                                
                                $aula_adjunta = '';
                                if (preg_match('/^(.*?)\s+(Aula\s*\d+|Lab\s+(?:F|Ing|Ma).*|Gimnasio\s*\d+|M\s*[IVX]*\s*\d+)$/i', $linea, $match_aula)) {
                                    $aula_adjunta = trim($match_aula[2]);
                                    $linea = trim($match_aula[1]); 
                                }

                                if (empty($linea) && !empty($aula_adjunta)) {
                                    if ($indice >= 0) $bloques_materias[$indice]['aula'] = $aula_adjunta;
                                    continue;
                                }

                                if (preg_match('/^([A-Z]{3})\s*-?\s*(\d{3})$/i', $linea, $matches)) {
                                    $indice++;
                                    $bloques_materias[$indice] = [
                                        'codigo' => strtoupper($matches[1] . '-' . $matches[2]),
                                        'semestre' => (int) substr($matches[2], 0, 1),
                                        'nombre_parts' => [],
                                        'aula' => $aula_adjunta ?: 'Por Asignar'
                                    ];
                                }
                                elseif (preg_match('/^([A-Z]{3})\s*-?\s*(\d{3})\s+(.+)$/i', $linea, $matches)) {
                                    $indice++;
                                    $bloques_materias[$indice] = [
                                        'codigo' => strtoupper($matches[1] . '-' . $matches[2]),
                                        'semestre' => (int) substr($matches[2], 0, 1),
                                        'nombre_parts' => [$matches[3]],
                                        'aula' => $aula_adjunta ?: 'Por Asignar'
                                    ];
                                }
                                else {
                                    if ($indice === -1) {
                                        $indice++;
                                        $bloques_materias[$indice] = [
                                            'codigo' => 'HUERFANO',
                                            'semestre' => 0,
                                            'nombre_parts' => [],
                                            'aula' => 'Por Asignar'
                                        ];
                                    }

                                    if ($aula_adjunta) {
                                        $bloques_materias[$indice]['aula'] = $aula_adjunta;
                                    }

                                    if (preg_match('/^(Aula\s*\d+|Lab\s+(?:F|Ing|Ma).*|Gimnasio\s*\d+|M\s*[IVX]*\s*\d+|^\d+$|^0$)/i', $linea)) {
                                        $bloques_materias[$indice]['aula'] = $linea;
                                    } else {
                                        $bloques_materias[$indice]['nombre_parts'][] = $linea;
                                    }
                                }
                            }

                            foreach ($bloques_materias as $bloque) {
                                $nombre_materia = empty($bloque['nombre_parts']) ? '' : implode(" ", $bloque['nombre_parts']);
                                $aula_clase     = $bloque['aula'];
                                $codigo_materia = $bloque['codigo'];
                                $semestre       = $bloque['semestre'];

                                if ($codigo_materia === 'HUERFANO') {
                                    if (isset($memoria_dia[$dia])) {
                                        $id_materia_vieja = $memoria_dia[$dia]['materia_id'];
                                        $materia_db = $materiaModel->find($id_materia_vieja);
                                        
                                        if (!empty($nombre_materia) && $materia_db) {
                                            $nombre_actual = $materia_db['nombre'];
                                            
                                            //  3. MOTOR DE SIMILITUD
                                            similar_text(strtolower(trim($nombre_actual)), strtolower(trim($nombre_materia)), $similitud);
                                            
                                            if ($similitud < 75 && stripos($nombre_actual, $nombre_materia) === false) {
                                                if ($nombre_actual === 'Materia Desconocida') {
                                                    $nuevo_nombre = $nombre_materia;
                                                } else {
                                                    $nuevo_nombre = trim($nombre_actual . ' ' . $nombre_materia);
                                                }
                                                $materiaModel->update($id_materia_vieja, ['nombre' => $nuevo_nombre]);
                                            }
                                        }

                                        if ($aula_clase !== 'Por Asignar') {
                                            $claseModel->update($memoria_dia[$dia]['clase_id'], [
                                                'aula'     => $aula_clase,
                                                'hora_fin' => $hora_fin 
                                            ]);
                                        }
                                    }
                                    continue; 
                                }

                                $materia_db = $materiaModel->where('codigo_materia', $codigo_materia)->first();
                                
                                if (!$materia_db) {
                                    $materia_id = $materiaModel->insert([
                                        'codigo_materia'    => $codigo_materia,
                                        'nombre'            => $nombre_materia ?: 'Materia Desconocida',
                                        'unidades_credito'  => 0, 
                                        'semestre_trayecto' => $semestre 
                                    ]);
                                } else {
                                    $materia_id = $materia_db['id'];
                                    
                                    // Sistema de auto-sanación
                                    if (!empty($nombre_materia) && strlen($nombre_materia) > strlen($materia_db['nombre'])) {
                                        $materiaModel->update($materia_id, ['nombre' => $nombre_materia]);
                                    }
                                }

                                $codigo_seccion    = isset($fila['Seccion']) ? trim($fila['Seccion']) : 'Desconocida';
                                $periodo_academico = isset($fila['Periodo']) ? trim($fila['Periodo']) : 'Desconocido';

                                $seccion_db = $seccionModel->where('materia_id', $materia_id)
                                                           ->where('codigo_seccion', $codigo_seccion)
                                                           ->where('periodo_academico', $periodo_academico)
                                                           ->first();

                                if (!$seccion_db) {
                                    $seccion_id = $seccionModel->insert([
                                        'materia_id'        => $materia_id,
                                        'codigo_seccion'    => $codigo_seccion,
                                        'periodo_academico' => $periodo_academico,
                                        'profesor_id'       => null     
                                    ]);
                                } else {
                                    $seccion_id = $seccion_db['id'];
                                }
                                
                                $clase_id = $claseModel->insert([
                                    'seccion_id'  => $seccion_id,
                                    'aula'        => $aula_clase,
                                    'dia_semana'  => $dia,
                                    'hora_inicio' => $hora_inicio,
                                    'hora_fin'    => $hora_fin
                                ]);

                                $memoria_dia[$dia] = [
                                    'materia_id' => $materia_id,
                                    'clase_id'   => $clase_id
                                ];

                                $clases_insertadas++;
                            }
                        }
                    }
                }

                return "¡Proceso Completado con éxito! Se procesaron e insertaron un total de {$clases_insertadas} bloques de clases en el mapa.";
                
            }
        } else {
            dd("Respuesta cruda de Python: " . $json_crudo);
        }
    }


    // 2. VISTA PRINCIPAL DEL MAPA (CARGA INICIAL)
    public function mostrar_mapa() {
        $aulasModel = new AulasModel();
        $clasesModel = new ClaseProgramadaModel();
        
        $lista_aulas = $aulasModel->findAll();
        
        // Sincronización horaria y de idioma
        date_default_timezone_set('America/Caracas');
        $hora_actual = date('H:i:s');
        
        $dias_ingles_a_espanol = [
            'Monday'    => 'Lunes', 
            'Tuesday'   => 'Martes', 
            'Wednesday' => 'Miercoles', 
            'Thursday'  => 'Jueves', 
            'Friday'    => 'Viernes', 
            'Saturday'  => 'Sabado', 
            'Sunday'    => 'Domingo'
        ];
        $dia_actual = $dias_ingles_a_espanol[date('l')];

        // Agregamos la lógica inicial de "Ocupado"
        foreach ($lista_aulas as &$aula) {
            $clase_activa = $clasesModel
                ->join('secciones', 'secciones.id = clases_programadas.seccion_id')
                ->where('aula', $aula['alias_pdf'])
                ->where('dia_semana', $dia_actual)
                ->where('hora_inicio <=', $hora_actual)
                ->where('hora_fin >=', $hora_actual)
                ->first();

            $aula['esta_ocupado'] = ($clase_activa !== null);
        }

        return view('mapa', ['aulas' => $lista_aulas]);
    }

    // 3. INFORMACIÓN DETALLADA DE UN AULA AL HACER CLIC
    public function info_aula($nombre_aula) {
        $clasesModel = new ClaseProgramadaModel();
        
        $horarios = $clasesModel
            ->select('clases_programadas.*, materias.nombre as materia')
            ->join('secciones', 'secciones.id = clases_programadas.seccion_id')
            ->join('materias', 'materias.id = secciones.materia_id')
            ->where('aula', $nombre_aula)
            ->orderBy('dia_semana', 'ASC')
            ->orderBy('hora_inicio', 'ASC')
            ->findAll();

        return json_encode($horarios);
    }

    // 4. API PARA ACTUALIZAR LUCES EN TIEMPO REAL
    public function estado_aulas_en_vivo() {
        date_default_timezone_set('America/Caracas');
        $hora_actual = date('H:i:s');
        
        $dias_ingles_a_espanol = [
            'Monday'    => 'Lunes', 
            'Tuesday'   => 'Martes', 
            'Wednesday' => 'Miercoles', 
            'Thursday'  => 'Jueves', 
            'Friday'    => 'Viernes', 
            'Saturday'  => 'Sabado', 
            'Sunday'    => 'Domingo'
        ];
        $dia_actual = $dias_ingles_a_espanol[date('l')];
        
        $hora_mas_15_min = date('H:i:s', strtotime('+15 minutes'));

        $aulasModel = new AulasModel();
        $clasesModel = new ClaseProgramadaModel();
        
        $lista_aulas = $aulasModel->findAll();
        $estadoMapa = [];

        foreach ($lista_aulas as $aula) {
            $alias = $aula['alias_pdf'];

            if (isset($aula['condicion']) && $aula['condicion'] == 'fuera_de_servicio') {
                $estadoMapa[$alias] = 'azul';
                continue;
            }

            $clase_activa = $clasesModel
                ->where('aula', $alias)
                ->where('dia_semana', $dia_actual)
                ->where('hora_inicio <=', $hora_actual)
                ->where('hora_fin >=', $hora_actual)
                ->first();

            if ($clase_activa) {
                $estadoMapa[$alias] = 'roja';
                continue;
            }

            $clase_proxima = $clasesModel
                ->where('aula', $alias)
                ->where('dia_semana', $dia_actual)
                ->where('hora_inicio >', $hora_actual)
                ->where('hora_inicio <=', $hora_mas_15_min)
                ->first();

            if ($clase_proxima) {
                $estadoMapa[$alias] = 'amarilla';
            } else {
                $estadoMapa[$alias] = 'verde';
            }
        }

        return $this->response->setJSON($estadoMapa);
    }
}