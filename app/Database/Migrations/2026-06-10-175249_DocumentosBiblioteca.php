<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DocumentosBiblioteca extends Migration
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
            'subido_por_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'materia_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'titulo' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
            ],
            'ruta_archivo' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'fecha_subida' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('subido_por_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('materia_id', 'materias', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('documentos_biblioteca');
    }

    public function down()
    {
        $this->forge->dropTable('documentos_biblioteca');
    }
}