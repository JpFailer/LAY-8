<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AgregarDatosAUsuarios extends Migration
{
    public function up()
    {
        // Definimos los campos extra que necesita la universidad
        $camposExtra = [
            'cedula' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'unique'     => true,
                'after'      => 'id' // Esto acomoda la columna estéticamente justo después del ID
            ],
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true, // Permitimos nulo temporalmente durante el registro rápido
                'after'      => 'cedula'
            ],
            'apellido' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true, // Permitimos nulo temporalmente durante el registro rápido
                'after'      => 'nombre'
            ],
            'fecha_vencimiento_premium' => [
                'type'       => 'DATE',
                'null'       => true, 
                'after'      => 'apellido'
            ],
        ];

        // Le decimos a la forja que agregue estos campos a la tabla 'users' existente
        $this->forge->addColumn('users', $camposExtra);
    }

    public function down()
    {
        // Si damos rollback, destruimos únicamente estas tres columnas
        $this->forge->dropColumn('users', ['cedula', 'nombre', 'apellido', 'fecha_vencimiento_premium']);
    }
}