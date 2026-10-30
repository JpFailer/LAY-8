<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AgregarCarreraASeccionesYClases extends Migration
{
    public function up()
    {
        // 1. Tabla secciones
        if ($this->db->tableExists('secciones')) {
            if (!$this->db->fieldExists('carrera', 'secciones')) {
                $campos = [
                    'carrera' => [
                        'type'       => 'VARCHAR',
                        'constraint' => '20',
                        'null'       => true,
                        'default'    => 'ING-INF',
                        'after'      => 'periodo_academico',
                    ],
                ];
                $this->forge->addColumn('secciones', $campos);
            }
            try {
                $this->db->query("ALTER TABLE `secciones` ADD INDEX `idx_secciones_carrera` (`carrera`)");
            } catch (\Throwable $e) {}
            $this->db->query("UPDATE `secciones` SET `carrera` = 'ING-INF' WHERE `carrera` IS NULL OR `carrera` = ''");
        }

        // 2. Tabla clases_programadas
        if ($this->db->tableExists('clases_programadas')) {
            if (!$this->db->fieldExists('carrera', 'clases_programadas')) {
                $campos = [
                    'carrera' => [
                        'type'       => 'VARCHAR',
                        'constraint' => '20',
                        'null'       => true,
                        'default'    => 'ING-INF',
                        'after'      => 'aula',
                    ],
                ];
                $this->forge->addColumn('clases_programadas', $campos);
            }
            try {
                $this->db->query("ALTER TABLE `clases_programadas` ADD INDEX `idx_clases_carrera` (`carrera`)");
            } catch (\Throwable $e) {}
            $this->db->query("UPDATE `clases_programadas` SET `carrera` = 'ING-INF' WHERE `carrera` IS NULL OR `carrera` = ''");
        }
    }

    public function down()
    {
        if ($this->db->tableExists('clases_programadas') && $this->db->fieldExists('carrera', 'clases_programadas')) {
            try {
                $this->db->query("ALTER TABLE `clases_programadas` DROP INDEX `idx_clases_carrera`");
            } catch (\Throwable $e) {}
            $this->forge->dropColumn('clases_programadas', 'carrera');
        }

        if ($this->db->tableExists('secciones') && $this->db->fieldExists('carrera', 'secciones')) {
            try {
                $this->db->query("ALTER TABLE `secciones` DROP INDEX `idx_secciones_carrera`");
            } catch (\Throwable $e) {}
            $this->forge->dropColumn('secciones', 'carrera');
        }
    }
}
