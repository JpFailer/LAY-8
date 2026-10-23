<?php 

namespace App\Controllers;

use App\Models\AulasModel;

class AulasController extends BaseController {

    public function importar_json() {
        $aulasModel = new AulasModel();

        $rutaJson = ROOTPATH . 'public/posiciones_aulas.json';
        if (!file_exists($rutaJson)) {
            return "Error: No se encontró el archivo de posiciones en " . $rutaJson;
        }

        // 1. Leemos archivo JSON
        $json_content = file_get_contents($rutaJson);
        $aulas = json_decode($json_content, true);

        if (!$aulas) {
            return "Error: No se pudo decodificar el archivo JSON.";
        }

        $importadas = 0;
        $actualizadas = 0;

        foreach ($aulas as $aula) {
            $nombre_json = $aula['aula'];
            
            // 2. Extraemos el ALIAS antes del paréntesis: "Aula 57 (Primer piso)" -> "Aula 57"
            $partes = explode("(", $nombre_json);
            $alias_pdf = trim($partes[0]);

            $datos = [
                'nombre_json' => $nombre_json,
                'alias_pdf'   => $alias_pdf,
                'coord_x'     => $aula['coord_x'],
                'coord_y'     => $aula['coord_y']
            ];

            // 3. Verificamos si ya existe para no duplicar registros
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
