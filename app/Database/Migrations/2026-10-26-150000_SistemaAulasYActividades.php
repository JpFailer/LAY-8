<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SistemaAulasYActividades extends Migration
{
    public function up()
    {
        // 1. Agregar columna estudiante_id a clases_programadas (permite bloques personales/manuales sin afectar al resto)
        if ($this->db->tableExists('clases_programadas')) {
            if (!$this->db->fieldExists('estudiante_id', 'clases_programadas')) {
                $campos = [
                    'estudiante_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                        'null'       => true,
                        'default'    => null,
                        'after'      => 'seccion_id',
                    ],
                ];
                $this->forge->addColumn('clases_programadas', $campos);

                try {
                    $this->db->query("ALTER TABLE `clases_programadas` ADD CONSTRAINT `fk_clases_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
                } catch (\Throwable $e) {}
            }
        }

        // 2. Tabla de actividades académicas / tareas tipo Classroom (actividades_clase)
        if (!$this->db->tableExists('actividades_clase')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'seccion_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'creado_por' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'es_personal' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'titulo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                ],
                'descripcion' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'tipo' => [
                    'type'       => 'ENUM',
                    'constraint' => ['tarea', 'parcial', 'taller', 'proyecto', 'lectura'],
                    'default'    => 'tarea',
                ],
                'fecha_entrega' => [
                    'type' => 'DATETIME',
                ],
                'ponderacion' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 0.00,
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
            $this->forge->addForeignKey('seccion_id', 'secciones', 'id', 'CASCADE', 'CASCADE', 'fk_actividades_seccion');
            $this->forge->addForeignKey('creado_por', 'usuarios', 'id', 'SET NULL', 'CASCADE', 'fk_actividades_creador');
            $this->forge->createTable('actividades_clase');
        }

        // 3. Tabla para el tracking y checklist de entrega de cada estudiante (actividades_estudiante_estado)
        if (!$this->db->tableExists('actividades_estudiante_estado')) {
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
                'completada' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'fecha_completada' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['actividad_id', 'estudiante_id'], false, true, 'idx_actividad_estudiante');
            $this->forge->addForeignKey('actividad_id', 'actividades_clase', 'id', 'CASCADE', 'CASCADE', 'fk_estado_actividad');
            $this->forge->addForeignKey('estudiante_id', 'usuarios', 'id', 'CASCADE', 'CASCADE', 'fk_estado_estudiante');
            $this->forge->createTable('actividades_estudiante_estado');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('actividades_estudiante_estado')) {
            $this->forge->dropTable('actividades_estudiante_estado');
        }
        if ($this->db->tableExists('actividades_clase')) {
            $this->forge->dropTable('actividades_clase');
        }
        if ($this->db->tableExists('clases_programadas') && $this->db->fieldExists('estudiante_id', 'clases_programadas')) {
            try {
                $this->db->query("ALTER TABLE `clases_programadas` DROP FOREIGN KEY `fk_clases_estudiante`");
            } catch (\Throwable $e) {}
            $this->forge->dropColumn('clases_programadas', 'estudiante_id');
        }
    }
}
