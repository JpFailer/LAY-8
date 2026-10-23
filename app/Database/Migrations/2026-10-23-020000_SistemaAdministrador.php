<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SistemaAdministrador extends Migration
{
    public function up()
    {
        // 1. Ampliar ENUM de rol en tabla usuarios para incluir 'administrador'
        // y agregar columnas de estado de cuenta (baneo), motivo y último acceso
        if ($this->db->tableExists('usuarios')) {
            // Modificar ENUM para incluir 'administrador'
            $this->db->query("ALTER TABLE `usuarios` MODIFY COLUMN `rol` ENUM('estudiante', 'profesor', 'administrador') NOT NULL DEFAULT 'estudiante'");

            $camposNuevosUsuarios = [];

            if (!$this->db->fieldExists('estado_cuenta', 'usuarios')) {
                $camposNuevosUsuarios['estado_cuenta'] = [
                    'type'       => 'ENUM',
                    'constraint' => ['activo', 'baneado'],
                    'default'    => 'activo',
                    'after'      => 'rol',
                ];
            }

            if (!$this->db->fieldExists('motivo_baneo', 'usuarios')) {
                $camposNuevosUsuarios['motivo_baneo'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'null'       => true,
                    'after'      => 'estado_cuenta',
                ];
            }

            if (!$this->db->fieldExists('baneado_en', 'usuarios')) {
                $camposNuevosUsuarios['baneado_en'] = [
                    'type'  => 'DATETIME',
                    'null'  => true,
                    'after' => 'motivo_baneo',
                ];
            }

            if (!$this->db->fieldExists('ultimo_acceso', 'usuarios')) {
                $camposNuevosUsuarios['ultimo_acceso'] = [
                    'type'  => 'DATETIME',
                    'null'  => true,
                    'after' => 'fecha_registro',
                ];
            }

            if (!empty($camposNuevosUsuarios)) {
                $this->forge->addColumn('usuarios', $camposNuevosUsuarios);
            }
        }

        // 2. Tabla para rastrear y gestionar aportes y documentos de la biblioteca
        if (!$this->db->tableExists('aportes_biblioteca')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'usuario_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'titulo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '150',
                    'null'       => true,
                ],
                'categoria' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '50',
                    'default'    => 'otros',
                ],
                'nombre_original' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                ],
                'nombre_guardado' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                ],
                'ruta_archivo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                ],
                'tamano_bytes' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'extension' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '20',
                    'default'    => '',
                ],
                'fecha_subida' => [
                    'type' => 'DATETIME',
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('usuario_id');
            $this->forge->addKey('categoria');
            $this->forge->createTable('aportes_biblioteca', true);
        }

        // 3. Tabla inmutable de auditoría para acciones del Administrador (Audit Logs)
        if (!$this->db->tableExists('auditoria_admin')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'admin_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'accion' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '100',
                ],
                'entidad' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '50',
                    'null'       => true,
                ],
                'entidad_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'detalles' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'ip' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '45',
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('admin_id');
            $this->forge->addKey('accion');
            $this->forge->createTable('auditoria_admin', true);
        }

        // 4. Agregar estado a la tabla aulas para gestión de mantenimiento
        if ($this->db->tableExists('aulas') && !$this->db->fieldExists('estado', 'aulas')) {
            $this->forge->addColumn('aulas', [
                'estado' => [
                    'type'       => 'ENUM',
                    'constraint' => ['disponible', 'mantenimiento', 'inhabilitada'],
                    'default'    => 'disponible',
                    'after'      => 'coord_y',
                ],
                'nota_estado' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'null'       => true,
                    'after'      => 'estado',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('auditoria_admin')) {
            $this->forge->dropTable('auditoria_admin');
        }

        if ($this->db->tableExists('aportes_biblioteca')) {
            $this->forge->dropTable('aportes_biblioteca');
        }

        if ($this->db->tableExists('aulas') && $this->db->fieldExists('estado', 'aulas')) {
            $this->forge->dropColumn('aulas', ['estado', 'nota_estado']);
        }

        if ($this->db->tableExists('usuarios')) {
            $camposBorrar = [];
            foreach (['estado_cuenta', 'motivo_baneo', 'baneado_en', 'ultimo_acceso'] as $col) {
                if ($this->db->fieldExists($col, 'usuarios')) {
                    $camposBorrar[] = $col;
                }
            }
            if (!empty($camposBorrar)) {
                $this->forge->dropColumn('usuarios', $camposBorrar);
            }
            $this->db->query("ALTER TABLE `usuarios` MODIFY COLUMN `rol` ENUM('estudiante', 'profesor') NOT NULL DEFAULT 'estudiante'");
        }
    }
}
