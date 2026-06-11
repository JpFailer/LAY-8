<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ClasesProgramadas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'seccion_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'aula' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
            ],
            'dia_semana' => [
                'type'       => 'VARCHAR',
                'constraint' => '15',
            ],
            'hora_inicio' => [
                'type' => 'TIME',
            ],
            'hora_fin' => [
                'type' => 'TIME',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('seccion_id', 'secciones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('clases_programadas');
    }

    public function down()
    {
        $this->forge->dropTable('clases_programadas');
    }
}