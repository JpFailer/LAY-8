<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\AulasModel;

class AulasSeeder extends Seeder
{
    public function run()
    {
        $aulasModel = new AulasModel();
        $rutaJson = ROOTPATH . 'public/posiciones_aulas.json';

        if (!file_exists($rutaJson)) {
            echo "Archivo de posiciones no encontrado: {$rutaJson}" . PHP_EOL;
            return;
        }

        $aulas = json_decode(file_get_contents($rutaJson), true);

        if (!$aulas) {
            echo "No se pudo leer el archivo JSON." . PHP_EOL;
            return;
        }

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
            } else {
                $aulasModel->insert($datos);
            }
        }

        echo "Seeder Aulas completado con éxito." . PHP_EOL;
    }
}
