<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class OptimizacionRelacionalYCarreras extends Migration
{
    public function up()
    {
        // =========================================================================
        // 1. TABLA: usuarios -> Agregar campo carrera y optimizar índices
        // =========================================================================
        if ($this->db->tableExists('usuarios')) {
            if (!$this->db->fieldExists('carrera', 'usuarios')) {
                $campos = [
                    'carrera' => [
                        'type'       => 'ENUM',
                        'constraint' => ['ING-INF', 'ING-MAR', 'ING-AMB', 'ADM', 'TUR'],
                        'default'    => 'ING-INF',
                        'null'       => true,
                        'after'      => 'rol',
                    ],
                ];
                $this->forge->addColumn('usuarios', $campos);
            }

            // Asignar carrera por defecto a usuarios existentes de tipo estudiante
            $this->db->query("UPDATE `usuarios` SET `carrera` = 'ING-INF' WHERE `rol` = 'estudiante' AND (`carrera` IS NULL OR `carrera` = '')");

            // Optimización de índices para acelerar búsquedas y garantizar integridad
            try {
                $this->db->query("ALTER TABLE `usuarios` ADD UNIQUE INDEX `idx_nombre_usuario` (`nombre_usuario`)");
            } catch (\Throwable $e) {}

            try {
                $this->db->query("ALTER TABLE `usuarios` ADD UNIQUE INDEX `idx_correo` (`correo`)");
            } catch (\Throwable $e) {}

            try {
                $this->db->query("ALTER TABLE `usuarios` ADD INDEX `idx_rol_carrera` (`rol`, `carrera`)");
            } catch (\Throwable $e) {}
        }

        // =========================================================================
        // 2. CORRECCIÓN DE CLAVES FORÁNEAS (DE `users` A `usuarios`)
        // =========================================================================

        // A) Corregir secciones.profesor_id -> usuarios.id
        if ($this->db->tableExists('secciones')) {
            try {
                $this->db->query("ALTER TABLE `secciones` DROP FOREIGN KEY `secciones_profesor_id_foreign`");
            } catch (\Throwable $e) {}

            try {
                $this->db->query("ALTER TABLE `secciones` ADD CONSTRAINT `fk_secciones_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
            } catch (\Throwable $e) {}
        }

        // B) Corregir inscripciones.estudiante_id -> usuarios.id
        if ($this->db->tableExists('inscripciones')) {
            try {
                $this->db->query("ALTER TABLE `inscripciones` DROP FOREIGN KEY `inscripciones_estudiante_id_foreign`");
            } catch (\Throwable $e) {}

            try {
                $this->db->query("ALTER TABLE `inscripciones` ADD CONSTRAINT `fk_inscripciones_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
            } catch (\Throwable $e) {}
        }

        // =========================================================================
        // 3. FORTALECER INTEGRIDAD EN TABLAS AUXILIARES Y NUEVAS
        // =========================================================================

        // C) aportes_biblioteca.usuario_id -> usuarios.id
        if ($this->db->tableExists('aportes_biblioteca')) {
            try {
                $this->db->query("ALTER TABLE `aportes_biblioteca` ADD CONSTRAINT `fk_aportes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
            } catch (\Throwable $e) {}
        }

        // D) auditoria_admin.admin_id -> usuarios.id
        if ($this->db->tableExists('auditoria_admin')) {
            try {
                $this->db->query("ALTER TABLE `auditoria_admin` ADD CONSTRAINT `fk_auditoria_admin_user` FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
            } catch (\Throwable $e) {}
        }

        // E) estados_clase_profesor -> usuarios y secciones
        if ($this->db->tableExists('estados_clase_profesor')) {
            try {
                $this->db->query("ALTER TABLE `estados_clase_profesor` ADD CONSTRAINT `fk_estados_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
            } catch (\Throwable $e) {}

            try {
                $this->db->query("ALTER TABLE `estados_clase_profesor` ADD CONSTRAINT `fk_estados_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
            } catch (\Throwable $e) {}
        }

        // G) documentos_biblioteca.subido_por_id -> usuarios.id
        if ($this->db->tableExists('documentos_biblioteca')) {
            try {
                $this->db->query("ALTER TABLE `documentos_biblioteca` DROP FOREIGN KEY `documentos_biblioteca_subido_por_id_foreign`");
            } catch (\Throwable $e) {}

            try {
                $this->db->query("ALTER TABLE `documentos_biblioteca` MODIFY `subido_por_id` INT(11) UNSIGNED NULL");
                $this->db->query("ALTER TABLE `documentos_biblioteca` ADD CONSTRAINT `fk_doc_usuario` FOREIGN KEY (`subido_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
            } catch (\Throwable $e) {}
        }
    }

    public function down()
    {
        // Revertir claves foráneas
        if ($this->db->tableExists('notificaciones')) {
            try { $this->db->query("ALTER TABLE `notificaciones` DROP FOREIGN KEY `fk_notif_estudiante`"); } catch (\Throwable $e) {}
            try { $this->db->query("ALTER TABLE `notificaciones` DROP FOREIGN KEY `fk_notif_profesor`"); } catch (\Throwable $e) {}
            try { $this->db->query("ALTER TABLE `notificaciones` DROP FOREIGN KEY `fk_notif_seccion`"); } catch (\Throwable $e) {}
        }

        if ($this->db->tableExists('estados_clase_profesor')) {
            try { $this->db->query("ALTER TABLE `estados_clase_profesor` DROP FOREIGN KEY `fk_estados_profesor`"); } catch (\Throwable $e) {}
            try { $this->db->query("ALTER TABLE `estados_clase_profesor` DROP FOREIGN KEY `fk_estados_seccion`"); } catch (\Throwable $e) {}
        }

        if ($this->db->tableExists('auditoria_admin')) {
            try { $this->db->query("ALTER TABLE `auditoria_admin` DROP FOREIGN KEY `fk_auditoria_admin_user`"); } catch (\Throwable $e) {}
        }

        if ($this->db->tableExists('aportes_biblioteca')) {
            try { $this->db->query("ALTER TABLE `aportes_biblioteca` DROP FOREIGN KEY `fk_aportes_usuario`"); } catch (\Throwable $e) {}
        }

        if ($this->db->tableExists('inscripciones')) {
            try { $this->db->query("ALTER TABLE `inscripciones` DROP FOREIGN KEY `fk_inscripciones_estudiante`"); } catch (\Throwable $e) {}
        }

        if ($this->db->tableExists('secciones')) {
            try { $this->db->query("ALTER TABLE `secciones` DROP FOREIGN KEY `fk_secciones_profesor`"); } catch (\Throwable $e) {}
        }

        if ($this->db->tableExists('usuarios') && $this->db->fieldExists('carrera', 'usuarios')) {
            $this->forge->dropColumn('usuarios', 'carrera');
        }
    }
}
