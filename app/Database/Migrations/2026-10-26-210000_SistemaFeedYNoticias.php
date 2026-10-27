<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SistemaFeedYNoticias extends Migration
{
    public function up()
    {
        // 1. TABLA: noticias_muro
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'autor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'titulo' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'contenido' => [
                'type' => 'TEXT',
            ],
            'categoria' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'Institucional',
            ],
            'fijado' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'total_likes' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'default'    => 0,
            ],
            'total_comentarios' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'default'    => 0,
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
        $this->forge->addKey('autor_id');
        $this->forge->addKey(['fijado', 'created_at']);
        $this->forge->createTable('noticias_muro', true);

        // 2. TABLA: noticias_likes
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'noticia_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'usuario_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['noticia_id', 'usuario_id']);
        $this->forge->createTable('noticias_likes', true);

        // 3. TABLA: noticias_comentarios (Hilos estilo Reddit con parent_id)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'noticia_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'usuario_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'parent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'contenido' => [
                'type' => 'TEXT',
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
        $this->forge->addKey('noticia_id');
        $this->forge->addKey('parent_id');
        $this->forge->createTable('noticias_comentarios', true);

        // 4. Modificar y ampliar tabla notificaciones
        $db = \Config\Database::connect();
        try {
            // Cambiar 'tipo' a VARCHAR(50) y agregar 'enlace_accion'
            $db->query("ALTER TABLE notificaciones MODIFY COLUMN tipo VARCHAR(50) NOT NULL DEFAULT 'general'");
            // Verificar si 'enlace_accion' ya existe antes de agregarlo
            $colCheck = $db->query("SHOW COLUMNS FROM notificaciones LIKE 'enlace_accion'")->getRow();
            if (!$colCheck) {
                $db->query("ALTER TABLE notificaciones ADD COLUMN enlace_accion VARCHAR(255) NULL AFTER enlace_virtual");
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error ajustando notificaciones: ' . $e->getMessage());
        }
    }

    public function down()
    {
        $this->forge->dropTable('noticias_comentarios', true);
        $this->forge->dropTable('noticias_likes', true);
        $this->forge->dropTable('noticias_muro', true);
    }
}
