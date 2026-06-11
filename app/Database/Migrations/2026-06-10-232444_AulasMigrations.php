<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AulasMigrations extends Migration
{
    public function up()
    {
        // Definimos todas las columnas de la tabla
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nombre_json' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
            'alias_pdf' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => false,
            ],
            'coord_x' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => false,
            ],
            'coord_y' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => false,
            ],
        ]);

        // Le indicamos que el campo 'id' es la llave primaria (Primary Key)
        $this->forge->addKey('id', true);
        
        // Creamos la tabla físicamente en MariaDB con el nombre 'aulas'
        $this->forge->createTable('aulas');
    }

    public function down()
    {
        // El método down() se ejecuta si decides deshacer (rollback) la migración
        $this->forge->dropTable('aulas');
    }
}