<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AgregarCoordenadasAulas222324 extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('aulas')) {
            $aulas = [
                [
                    'nombre_json' => 'Aula 22 (Planta Baja)',
                    'alias_pdf'   => 'Aula 22',
                    'coord_x'     => 33.39,
                    'coord_y'     => 67.45,
                    'estado'      => 'disponible'
                ],
                [
                    'nombre_json' => 'Aula 23 (Planta Baja)',
                    'alias_pdf'   => 'Aula 23',
                    'coord_x'     => 30.49,
                    'coord_y'     => 67.45,
                    'estado'      => 'disponible'
                ],
                [
                    'nombre_json' => 'Aula 24 (Planta Baja)',
                    'alias_pdf'   => 'Aula 24',
                    'coord_x'     => 27.49,
                    'coord_y'     => 67.45,
                    'estado'      => 'disponible'
                ],
            ];

            foreach ($aulas as $a) {
                $existe = $this->db->table('aulas')
                    ->where('alias_pdf', $a['alias_pdf'])
                    ->countAllResults();

                if ($existe === 0) {
                    $this->db->table('aulas')->insert($a);
                } else {
                    $this->db->table('aulas')
                        ->where('alias_pdf', $a['alias_pdf'])
                        ->update([
                            'coord_x' => $a['coord_x'],
                            'coord_y' => $a['coord_y']
                        ]);
                }
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('aulas')) {
            $this->db->table('aulas')
                ->whereIn('alias_pdf', ['Aula 22', 'Aula 23', 'Aula 24'])
                ->delete();
        }
    }
}
