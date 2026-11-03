<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaEstudianteBloquesExcluidos extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('estudiante_bloques_excluidos')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'estudiante_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'clase_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'created_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['estudiante_id', 'clase_id'], 'uq_estudiante_clase_excluido');
            $this->forge->createTable('estudiante_bloques_excluidos', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('estudiante_bloques_excluidos', true);
    }
}
