<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AgregarCedulaYRolenUsuarios extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('usuarios')) {
            // 1. Agregar columna cedula si no existe
            if (!$this->db->fieldExists('cedula', 'usuarios')) {
                $campos = [
                    'cedula' => [
                        'type'       => 'VARCHAR',
                        'constraint' => '20',
                        'null'       => true,
                        'default'    => null,
                        'after'      => 'apellidos',
                    ],
                ];
                $this->forge->addColumn('usuarios', $campos);
            }

            // 2. Crear índice UNIQUE para garantizar unicidad estricta de cédula
            try {
                $this->db->query("ALTER TABLE `usuarios` ADD UNIQUE INDEX `idx_cedula` (`cedula`)");
            } catch (\Throwable $e) {
                // Índice ya existe o tabla en SQLite/test
            }

            // 3. Garantizar que las 4 cédulas de los desarrolladores fundadores tengan rol de administrador
            $cedulasFundadores = ['31131073', '32370840', '30702138', '30369782'];
            $inList = "'" . implode("','", $cedulasFundadores) . "'";
            try {
                $this->db->query("UPDATE `usuarios` SET `rol` = 'administrador', `estado_verificacion` = 'aprobado' WHERE `cedula` IN ($inList)");
            } catch (\Throwable $e) {}
        }
    }

    public function down()
    {
        if ($this->db->tableExists('usuarios') && $this->db->fieldExists('cedula', 'usuarios')) {
            try {
                $this->db->query("ALTER TABLE `usuarios` DROP INDEX `idx_cedula`");
            } catch (\Throwable $e) {}
            $this->forge->dropColumn('usuarios', 'cedula');
        }
    }
}
