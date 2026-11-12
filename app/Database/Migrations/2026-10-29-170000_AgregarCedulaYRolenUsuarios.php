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

            // 3. Garantizar rol administrador para las cédulas configuradas en .env (si existen)
            $cedulasFundadores = function_exists('obtener_cedulas_admins_iniciales')
                ? obtener_cedulas_admins_iniciales()
                : [];
            if ($cedulasFundadores !== []) {
                try {
                    $this->db->table('usuarios')
                        ->whereIn('cedula', $cedulasFundadores)
                        ->update([
                            'rol'                 => 'administrador',
                            'estado_verificacion' => 'aprobado',
                        ]);
                } catch (\Throwable $e) {}
            }
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
