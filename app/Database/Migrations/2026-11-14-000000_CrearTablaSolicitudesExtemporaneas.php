<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaSolicitudesExtemporaneas extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('solicitudes_extemporaneas')) {
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
                'seccion_id' => [
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
                'estado' => [
                    'type'       => 'ENUM',
                    'constraint' => ['pendiente', 'aprobada', 'rechazada'],
                    'default'    => 'pendiente',
                ],
                'motivo_rechazo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
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
            $this->forge->addKey('estudiante_id');
            $this->forge->addKey('seccion_id');
            $this->forge->addKey('profesor_id');
            $this->forge->addKey(['estudiante_id', 'seccion_id', 'estado']);
            $this->forge->createTable('solicitudes_extemporaneas', true);

            try {
                $this->db->query("ALTER TABLE `solicitudes_extemporaneas` ADD CONSTRAINT `fk_sol_ext_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
            } catch (\Throwable $e) {}

            try {
                $this->db->query("ALTER TABLE `solicitudes_extemporaneas` ADD CONSTRAINT `fk_sol_ext_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
            } catch (\Throwable $e) {}

            try {
                $this->db->query("ALTER TABLE `solicitudes_extemporaneas` ADD CONSTRAINT `fk_sol_ext_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
            } catch (\Throwable $e) {}
        }
    }

    public function down()
    {
        $this->forge->dropTable('solicitudes_extemporaneas', true);
    }
}
