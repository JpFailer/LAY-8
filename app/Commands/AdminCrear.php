<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\UsuarioModel;
use App\Models\AuditoriaAdminModel;

class AdminCrear extends BaseCommand
{
    protected $group       = 'Seguridad';
    protected $name        = 'admin:crear';
    protected $description = 'Crea una nueva cuenta de Administrador de manera segura mediante la consola de servidor.';
    protected $usage       = 'admin:crear';

    public function run(array $params)
    {
        CLI::write("==================================================", 'cyan');
        CLI::write("CREACIÓN SEGURA DE ADMINISTRADOR DEL SISTEMA", 'bold');
        CLI::write("==================================================", 'cyan');

        $nombre        = CLI::prompt('Primer Nombre', null, 'required');
        $apellidos     = CLI::prompt('Apellidos', null, 'required');
        $nombreUsuario = CLI::prompt('Nombre de Usuario', null, 'required');
        $correo        = CLI::prompt('Correo Electrónico', null, 'required');
        $contrasena    = CLI::prompt('Contraseña (mínimo 8 caracteres)', null, 'required');

        if (strlen($contrasena) < 8) {
            CLI::error("❌ La contraseña debe tener al menos 8 caracteres.");
            return;
        }

        $usuarioModel = new UsuarioModel();

        if ($usuarioModel->where('nombre_usuario', $nombreUsuario)->first()) {
            CLI::error("❌ El nombre de usuario '@{$nombreUsuario}' ya se encuentra registrado.");
            return;
        }

        if ($usuarioModel->where('correo', $correo)->first()) {
            CLI::error("❌ El correo '{$correo}' ya se encuentra registrado.");
            return;
        }

        $nuevoAdminId = $usuarioModel->insert([
            'primer_nombre'       => trim($nombre),
            'apellidos'           => trim($apellidos),
            'nombre_usuario'      => trim($nombreUsuario),
            'correo'              => trim($correo),
            'contrasena'          => password_hash($contrasena, PASSWORD_DEFAULT),
            'rol'                 => 'administrador',
            'carrera'             => 'ING-INF',
            'estado_cuenta'       => 'activo',
            'estado_verificacion' => 'aprobado',
            'fecha_registro'      => date('Y-m-d H:i:s'),
        ], true);

        if ($nuevoAdminId) {
            try {
                $auditoria = new AuditoriaAdminModel();
                $auditoria->registrar(
                    (int) $nuevoAdminId,
                    'creacion_admin_consola_cli',
                    'usuarios',
                    (int) $nuevoAdminId,
                    ['origen' => 'Terminal CLI (Spark)']
                );
            } catch (\Throwable $e) {}

            CLI::write("✅ ¡Administrador creado exitosamente con ID #{$nuevoAdminId}!", 'green');
            CLI::write("Ya puedes iniciar sesión en la aplicación con el usuario '@{$nombreUsuario}'.", 'cyan');
        } else {
            CLI::error("❌ Ocurrió un error al guardar el administrador.");
        }
    }
}
