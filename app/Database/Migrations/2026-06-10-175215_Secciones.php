<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Secciones extends Migration
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
            'materia_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'profesor_id' => [
                'type'           => 'INT', // OJO: En Shield, el ID de users a veces es INT, revisa tu phpMyAdmin por si acaso es BIGINT (usa BIGINT si Shield lo creó así)
                'constraint'     => 11,
                'unsigned'       => true,
                'null'           => true, // Puede estar "Por Asignar"
            ],
            'codigo_seccion' => [
                'type'       => 'VARCHAR',
                'constraint' => '10',
            ],
            'periodo_academico' => [
                'type'       => 'VARCHAR',
                'constraint' => '20', // Ej: 2026-1
            ],
        ]);

        $this->forge->addKey('id', true);
        // Las llaves foráneas a Materias y a Users (Shield)
        $this->forge->addForeignKey('materia_id', 'materias', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('profesor_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('secciones');
    }

    public function down()
    {
        $this->forge->dropTable('secciones');
    }
}