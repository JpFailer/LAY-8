<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SistemaNotificacionesYEstatus extends Migration
{
    public function up()
    {
        // 1. TABLA: estados_clase_profesor
        // Permite al docente configurar el estado rápido de su clase (Voy en camino, Suspendida, etc.) y la modalidad
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'profesor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'seccion_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'modalidad' => [
                'type'       => 'ENUM',
                'constraint' => ['presencial', 'online'],
                'default'    => 'presencial',
            ],
            'enlace_virtual' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'estado_rapido' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'mensaje_adicional' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'fecha' => [
                'type' => 'DATE',
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
        $this->forge->addKey(['profesor_id', 'seccion_id', 'fecha']);
        $this->forge->createTable('estados_clase_profesor', true);

        // 2. TABLA: notificaciones
        // Sistema privado y unidireccional de alertas dirigidas únicamente a los alumnos inscritos
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
            'profesor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'seccion_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'tipo' => [
                'type'       => 'ENUM',
                'constraint' => ['alerta_estado', 'clase_proxima', 'tarea_pendiente', 'general'],
                'default'    => 'alerta_estado',
            ],
            'titulo' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'mensaje' => [
                'type' => 'TEXT',
            ],
            'modalidad' => [
                'type'       => 'ENUM',
                'constraint' => ['presencial', 'online'],
                'default'    => 'presencial',
            ],
            'enlace_virtual' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'leido' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('estudiante_id');
        $this->forge->addKey(['estudiante_id', 'leido']);
        $this->forge->createTable('notificaciones', true);
    }

    public function down()
    {
        $this->forge->dropTable('notificaciones', true);
        $this->forge->dropTable('estados_clase_profesor', true);
    }
}
