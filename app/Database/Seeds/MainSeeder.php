<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Services\HorarioSyncService;

class MainSeeder extends Seeder
{
    public function run()
    {
        $sync = new HorarioSyncService();

        echo "Poblando Aulas..." . PHP_EOL;
        $sync->importarPosicionesAulasJson();

        echo "Poblando Horarios y Clases..." . PHP_EOL;
        $total = $sync->importarHorariosAutomaticamente();
        echo "Se insertaron {$total} clases programadas." . PHP_EOL;
    }
}
