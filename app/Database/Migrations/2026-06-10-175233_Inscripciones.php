<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Inscripciones extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'estudiante_id' => [
                'type'       => 'INT', 
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'seccion_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
        ]);

        // Llave primaria compuesta (Un estudiante no puede inscribir la misma sección dos veces)
        $this->forge->addKey(['estudiante_id', 'seccion_id'], true);
        $this->forge->addForeignKey('estudiante_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('seccion_id', 'secciones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('inscripciones');
    }

    public function down()
    {
        $this->forge->dropTable('inscripciones');
    }
}