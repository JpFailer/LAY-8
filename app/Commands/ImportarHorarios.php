<?php

namespace App\Commands;

use App\Services\HorarioSyncService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ImportarHorarios extends BaseCommand
{
    protected $group       = 'LAY-8';
    protected $name        = 'horarios:importar';
    protected $description = 'Importa los archivos horarios_procesados_*.json hacia MySQL sin ejecutar Python en el servidor.';
    protected $usage       = 'horarios:importar [CARRERA]';
    protected $arguments   = [
        'carrera' => 'Código opcional de carrera (ING-INF, ING-MAR, ING-AMB, ADM, TUR). Si se omite, importa todas.',
    ];

    public function run(array $params)
    {
        $carrera = isset($params[0]) ? strtoupper(trim((string) $params[0])) : null;
        if ($carrera !== null && !in_array($carrera, HorarioSyncService::CARRERAS_VALIDAS, true)) {
            CLI::error('Carrera inválida. Opciones: ' . implode(', ', HorarioSyncService::CARRERAS_VALIDAS));
            return;
        }

        $service = new HorarioSyncService();
        $carreras = $carrera ? [$carrera] : HorarioSyncService::CARRERAS_VALIDAS;
        $total = 0;

        foreach ($carreras as $cod) {
            $rutaJson = ROOTPATH . 'scripts_python' . DIRECTORY_SEPARATOR . "horarios_procesados_{$cod}.json";
            if (!file_exists($rutaJson)) {
                CLI::write(" - {$cod}: sin archivo horarios_procesados_{$cod}.json (omitido)", 'yellow');
                continue;
            }

            $datos = json_decode((string) file_get_contents($rutaJson), true);
            if (!is_array($datos)) {
                CLI::error(" - {$cod}: JSON inválido en {$rutaJson}");
                continue;
            }

            $cargadas = $service->insertarBloquesEstructurados($datos, $cod, true);
            $total += $cargadas;
            CLI::write(" - {$cod}: {$cargadas} bloques importados en MySQL.", 'green');
        }

        CLI::write("Total sincronizado: {$total} bloques de clases.", 'cyan');
    }
}
