<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AgregarTokenRecuperacionUsuarios extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('usuarios')) {
            $campos = [];

            if (!$this->db->fieldExists('token_recuperacion', 'usuarios')) {
                $campos['token_recuperacion'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'contrasena',
                ];
            }

            if (!$this->db->fieldExists('token_expiracion', 'usuarios')) {
                $campos['token_expiracion'] = [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                    'after'   => 'token_recuperacion',
                ];
            }

            if (!empty($campos)) {
                $this->forge->addColumn('usuarios', $campos);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('usuarios')) {
            $columnas = [];
            if ($this->db->fieldExists('token_recuperacion', 'usuarios')) {
                $columnas[] = 'token_recuperacion';
            }
            if ($this->db->fieldExists('token_expiracion', 'usuarios')) {
                $columnas[] = 'token_expiracion';
            }
            if (!empty($columnas)) {
                $this->forge->dropColumn('usuarios', $columnas);
            }
        }
    }
}
