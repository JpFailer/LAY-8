<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Materias extends Migration
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
            'codigo_materia' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'unique'     => true, // Obligatorio para que no existan dos CAL-114
            ],
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'unidades_credito' => [
                'type'       => 'INT',
                'constraint' => 3,
            ],
            'semestre_trayecto' => [
                'type'       => 'INT',
                'constraint' => 2,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('materias');
    }

    public function down()
    {
        $this->forge->dropTable('materias');
    }
}