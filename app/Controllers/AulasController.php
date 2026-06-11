<?php 

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\AulasModel;

class AulasController extends Controller {

    public function importar_json() {
        // Instanciamos el modelo de Aulas
        $aulasModel = new AulasModel();

        // 1. Leemos archivo JSON
        $json_content = file_get_contents(ROOTPATH . 'public/posiciones_aulas.json');
        $aulas = json_decode($json_content, true);

        foreach ($aulas as $aula) {
            $nombre_json = $aula['aula'];
            
            // 2. Extraemos el ALIAS. 
            // Cortamos el texto justo antes del primer paréntesis "(". 
            // "Aula 57 (Primer piso)" -> "Aula 57"
            $partes = explode("(", $nombre_json);
            $alias_pdf = trim($partes[0]);

            // 3. Guardamos usando los métodos nativos del Modelo
            $aulasModel->insert([
                'nombre_json' => $nombre_json,
                'alias_pdf'   => $alias_pdf,
                'coord_x'     => $aula['coord_x'],
                'coord_y'     => $aula['coord_y']
            ]);
        }

        return "¡Las coordenadas de las aulas han sido importadas y mapeadas al formato del PDF con el Modelo!";
    }
}