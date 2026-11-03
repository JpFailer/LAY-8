<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaEntregasActividades extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('entregas_actividades')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'actividad_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'estudiante_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'archivo_ruta' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'archivo_nombre' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'comentario_alumno' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'calificacion' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'null'       => true,
                ],
                'feedback_profesor' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'calificado_por' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'calificado_en' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('actividad_id');
            $this->forge->addKey('estudiante_id');
            $this->forge->addUniqueKey(['actividad_id', 'estudiante_id'], 'uq_actividad_estudiante_entrega');
            $this->forge->createTable('entregas_actividades', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('entregas_actividades', true);
    }
}
