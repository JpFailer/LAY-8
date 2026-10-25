<?php

namespace App\Controllers;

class Login extends BaseController
{
    public function index()
    {
        // Si ya está logueado, lo mandamos al dashboard
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login_view');
    }

    public function autenticar()
    {
        // 1. Rate Limiting (Protección contra fuerza bruta):
        // Máximo 5 intentos por minuto por dirección IP
        $throttler = service('throttler');
        $ip = $this->request->getIPAddress();
        $throttleKey = 'login_attempt_' . md5($ip);

        if ($throttler->check($throttleKey, 5, 60) === false) {
            $espera = $throttler->getTokenTime();
            return redirect()->back()->with('error', "Demasiados intentos fallidos. Por seguridad espera {$espera} segundos.");
        }

        // 2. Validación de inputs
        $reglas = [
            'nombre_usuario' => 'required|min_length[3]|max_length[50]',
            'contrasena'     => 'required|min_length[6]',
        ];

        if (!$this->validate($reglas)) {
            return redirect()->back()->with('error', 'Por favor ingresa tu usuario y contraseña.');
        }

        $session = session();
        $nombre_usuario = trim((string) $this->request->getPost('nombre_usuario'));
        $contrasena     = (string) $this->request->getPost('contrasena');

        // 3. Conexión parametrizada (Query Builder previene inyección SQL)
        $db = \Config\Database::connect();
        $usuario = $db->table('usuarios')
                      ->where('nombre_usuario', $nombre_usuario)
                      ->get()
                      ->getRowArray();

        // 4. Verificación criptográfica segura con password_verify
        if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
            
            // Verificación de estado de cuenta (Baneo/Suspensión)
            if (isset($usuario['estado_cuenta']) && $usuario['estado_cuenta'] === 'baneado') {
                $motivo = !empty($usuario['motivo_baneo']) ? ": {$usuario['motivo_baneo']}" : '.';
                return redirect()->back()->with('error', "Esta cuenta ha sido suspendida o baneada{$motivo}");
            }

            // Actualizar fecha de último acceso
            try {
                $db->table('usuarios')
                   ->where('id', (int) $usuario['id'])
                   ->update(['ultimo_acceso' => date('Y-m-d H:i:s')]);
            } catch (\Throwable $e) {
                // Silenciar si la columna aún no ha sido migrada
            }

            // Protección contra fijación de sesión (Session Fixation): regeneramos el ID de sesión
            $session->regenerate(true);

            $session->set([
                'usuario_id'          => (int) $usuario['id'],
                'nombre_usuario'      => $usuario['nombre_usuario'],
                'primer_nombre'       => $usuario['primer_nombre'],
                'apellidos'           => $usuario['apellidos'],
                'rol'                 => $usuario['rol'],
                'estado_verificacion' => $usuario['estado_verificacion'],
                'foto_perfil'         => $usuario['foto_perfil'],
                'isLoggedIn'          => true
            ]);
            
            return redirect()->to('/dashboard');
            
        } else {
            return redirect()->back()->with('error', 'Usuario o contraseña incorrectos.');
        }
    }

    public function salir()
    {
        $session = session();
        $session->destroy();
        
        return redirect()->to('/');
    }
}
