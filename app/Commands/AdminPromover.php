<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\UsuarioModel;
use App\Models\AuditoriaAdminModel;

class AdminPromover extends BaseCommand
{
    protected $group       = 'Seguridad';
    protected $name        = 'admin:promover';
    protected $description = 'Otorga rol de Administrador de manera segura a un usuario existente mediante consola.';
    protected $usage       = 'admin:promover [usuario_o_correo]';
    protected $arguments   = [
        'usuario_o_correo' => 'Nombre de usuario o correo electrónico a promover como administrador.',
    ];

    public function run(array $params)
    {
        $identificador = $params[0] ?? null;

        if (empty($identificador)) {
            $identificador = CLI::prompt('Introduce el nombre de usuario o correo del usuario a promover', null, 'required');
        }

        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->where('nombre_usuario', $identificador)
                                ->orWhere('correo', $identificador)
                                ->first();

        if (!$usuario) {
            CLI::error("❌ No se encontró ningún usuario con el identificador: '{$identificador}'");
            return;
        }

        if (in_array($usuario['rol'], ['administrador', 'admin'], true)) {
            CLI::write("ℹ️ El usuario '{$usuario['nombre_usuario']}' ({$usuario['correo']}) ya cuenta con privilegios de Administrador.", 'yellow');
            return;
        }

        CLI::write("Usuario localizado:");
        CLI::write(" - ID: " . $usuario['id']);
        CLI::write(" - Nombre: " . $usuario['primer_nombre'] . " " . $usuario['apellidos']);
        CLI::write(" - Usuario: @" . $usuario['nombre_usuario']);
        CLI::write(" - Correo: " . $usuario['correo']);
        CLI::write(" - Rol actual: " . $usuario['rol']);

        $confirmacion = CLI::prompt('¿Deseas otorgarle privilegios completos de Administrador? (s/n)', ['s', 'n']);
        if ($confirmacion !== 's') {
            CLI::write('Operación cancelada.', 'light_gray');
            return;
        }

        $usuarioModel->update($usuario['id'], [
            'rol'           => 'administrador',
            'estado_cuenta' => 'activo'
        ]);

        try {
            $auditoria = new AuditoriaAdminModel();
            $auditoria->registrar(
                $usuario['id'],
                'promocion_consola_cli',
                'usuarios',
                $usuario['id'],
                ['promovido_por' => 'Terminal CLI (Spark)', 'rol_anterior' => $usuario['rol']]
            );
        } catch (\Throwable $e) {}

        CLI::write("✅ ¡Éxito! El usuario '{$usuario['nombre_usuario']}' ha sido promovido a ADMINISTRADOR.", 'green');
        CLI::write("Ahora puede acceder a las funciones avanzadas y al panel de control en /admin.", 'cyan');
    }
}
