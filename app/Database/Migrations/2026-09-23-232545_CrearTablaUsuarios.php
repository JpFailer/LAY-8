<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaUsuarios extends Migration
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
            'primer_nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
            ],
            'segundo_nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'apellidos' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'nombre_usuario' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'unique'     => true,
            ],
            'correo' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'unique'     => true,
            ],
            'telefono' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            'contrasena' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'rol' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'estudiante',
            ],
            'estado_verificacion' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'pendiente',
            ],
            'ruta_carnet' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ]
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->createTable('usuarios');
    }

    public function down()
    {
        $this->forge->dropTable('usuarios');
    }
}