<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Controllers\AulasController;
use App\Controllers\MapaController;

class MainSeeder extends Seeder
{
    public function run()
    {
        echo "Poblando Aulas..." . PHP_EOL;
        (new AulasController())->importar_json();

        echo "Poblando Horarios y Clases..." . PHP_EOL;
        $total = (new MapaController())->importar_horarios_automaticamente();
        echo "Se insertaron {$total} clases programadas." . PHP_EOL;
    }
}
