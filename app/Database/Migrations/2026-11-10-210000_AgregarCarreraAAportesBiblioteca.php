<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AgregarCarreraAAportesBiblioteca extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('aportes_biblioteca')) {
            if (!$this->db->fieldExists('carrera', 'aportes_biblioteca')) {
                $campos = [
                    'carrera' => [
                        'type'       => 'VARCHAR',
                        'constraint' => '20',
                        'null'       => true,
                        'default'    => 'GENERAL',
                        'after'      => 'categoria',
                    ],
                ];
                $this->forge->addColumn('aportes_biblioteca', $campos);
            }
            try {
                $this->db->query("ALTER TABLE `aportes_biblioteca` ADD INDEX `idx_aportes_carrera` (`carrera`)");
            } catch (\Throwable $e) {}

            // Actualizar aportes existentes con la carrera del usuario que lo subio si esta disponible
            try {
                $this->db->query("UPDATE `aportes_biblioteca` a INNER JOIN `usuarios` u ON u.id = a.usuario_id SET a.carrera = u.carrera WHERE (a.carrera IS NULL OR a.carrera = 'GENERAL') AND u.carrera IS NOT NULL AND u.carrera != ''");
            } catch (\Throwable $e) {}
        }
    }

    public function down()
    {
        if ($this->db->tableExists('aportes_biblioteca') && $this->db->fieldExists('carrera', 'aportes_biblioteca')) {
            $this->forge->dropColumn('aportes_biblioteca', 'carrera');
        }
    }
}
