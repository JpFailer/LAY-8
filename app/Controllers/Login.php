<?php

namespace App\Controllers;

use App\Services\VerificadorInscripcionService;

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
                'correo'              => $usuario['correo'] ?? '',
                'cedula'              => $usuario['cedula'] ?? '',
                'rol'                 => $usuario['rol'],
                'carrera'             => $usuario['carrera'] ?? 'ING-INF',
                'estado_verificacion' => $usuario['estado_verificacion'],
                'foto_perfil'         => $usuario['foto_perfil'] ?? null,
                'isLoggedIn'          => true
            ]);
            
            return redirect()->to('/dashboard');
            
        } else {
            return redirect()->back()->with('error', 'Usuario o contraseña incorrectos.');
        }
    }

    /**
     * Paso 1: Verifica la existencia de la cuenta y genera token/correo seguro
     * Simplificado y amigable: Sin necesidad de comprobantes PDF
     */
    public function verificarRecuperacion()
    {
        $identificador = trim((string) $this->request->getPost('identificador'));
        if (empty($identificador)) {
            return $this->response->setJSON([
                'exito' => false,
                'mensaje' => 'Por favor introduce tu nombre de usuario o correo electrónico.'
            ]);
        }

        $db = \Config\Database::connect();
        $usuario = $db->table('usuarios')
                      ->where('nombre_usuario', $identificador)
                      ->orWhere('correo', $identificador)
                      ->get()
                      ->getRowArray();

        if (!$usuario) {
            return $this->response->setJSON([
                'exito' => false,
                'mensaje' => 'No encontramos ninguna cuenta universitaria asociada a este usuario o correo.'
            ]);
        }

        if (($usuario['estado_cuenta'] ?? 'activo') === 'baneado') {
            return $this->response->setJSON([
                'exito' => false,
                'mensaje' => 'Esta cuenta se encuentra actualmente suspendida o inhabilitada.'
            ]);
        }

        // Generar token criptográfico único con vigencia de 2 horas
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+2 hours'));

        $db->table('usuarios')
           ->where('id', $usuario['id'])
           ->update([
               'token_recuperacion' => $token,
               'token_expiracion'   => $expira
           ]);

        // Ofuscar correo para privacidad visual (ej: ab***@umc.edu.ve)
        $correoReal = (string) $usuario['correo'];
        $partesEmail = explode('@', $correoReal);
        $userPart = $partesEmail[0];
        $domainPart = $partesEmail[1] ?? 'umc.edu.ve';
        $ofuscado = (strlen($userPart) > 2)
            ? substr($userPart, 0, 2) . str_repeat('*', max(3, strlen($userPart) - 2)) . '@' . $domainPart
            : $userPart . '***@' . $domainPart;

        $linkRecuperacion = base_url('restablecer-contrasena?token=' . $token);

        // Intento de despacho de correo electrónico
        $correoEnviado = false;
        try {
            $emailService = \Config\Services::email();
            $emailService->setTo($correoReal);
            $emailService->setSubject('Recuperación de Contraseña - LAY-8 UMC');
            $mensajeTexto = "Hola " . ($usuario['primer_nombre'] ?? $usuario['nombre_usuario']) . ",\n\n"
                          . "Recibimos una solicitud para restablecer la contraseña de tu cuenta de LAY-8.\n\n"
                          . "Para definir tu nueva clave ingresa en el siguiente enlace seguro (válido por 2 horas):\n"
                          . $linkRecuperacion . "\n\n"
                          . "Si no solicitaste este cambio, puedes ignorar este mensaje.\n\n"
                          . "Equipo LAY-8 - Universidad Marítima del Caribe";
            $emailService->setMessage($mensajeTexto);
            $correoEnviado = @$emailService->send();
        } catch (\Throwable $e) {
            log_message('error', 'Error envío correo recuperación: ' . $e->getMessage());
        }

        $responsePayload = [
            'exito'           => true,
            'usuario_id'      => (int) $usuario['id'],
            'nombre'          => trim(($usuario['primer_nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? '')),
            'rol'             => ucfirst($usuario['rol']),
            'usuario'         => $usuario['nombre_usuario'],
            'correo_ofuscado' => $ofuscado,
            'correo_enviado'  => $correoEnviado,
            'mensaje'         => '¡Cuenta verificada! Hemos enviado un enlace de recuperación seguro a tu correo ' . $ofuscado . '.'
        ];

        // En entorno de desarrollo (localhost), permitir inspeccionar el enlace generado para pruebas locales
        if (ENVIRONMENT === 'development') {
            $responsePayload['enlace_directo'] = $linkRecuperacion;
            $responsePayload['token'] = $token;
        }

        return $this->response->setJSON($responsePayload);
    }

    /**
     * Paso 2: Restablece la contraseña directamente mediante token o ID verificado
     * Sin pedir comprobantes PDF
     */
    public function restablecerContrasena()
    {
        $usuarioId = (int) $this->request->getPost('usuario_id');
        $token     = trim((string) $this->request->getPost('token'));
        $nueva     = (string) $this->request->getPost('nueva_contrasena');
        $repetir   = (string) $this->request->getPost('repetir_contrasena');

        if (strlen($nueva) < 8) {
            return $this->response->setJSON(['exito' => false, 'mensaje' => 'La nueva contraseña debe contener al menos 8 caracteres.']);
        }

        if ($nueva !== $repetir) {
            return $this->response->setJSON(['exito' => false, 'mensaje' => 'Las contraseñas no coinciden.']);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('usuarios');

        if (empty($token)) {
            return $this->response->setJSON(['exito' => false, 'mensaje' => 'Token de recuperación ausente o inválido. Debes utilizar el enlace enviado a tu correo institucional.']);
        }

        $builder->where('token_recuperacion', $token)
                ->where('token_expiracion >=', date('Y-m-d H:i:s'));

        $usuario = $builder->get()->getRowArray();
        if (!$usuario) {
            return $this->response->setJSON(['exito' => false, 'mensaje' => 'El enlace de recuperación es inválido o ha expirado.']);
        }

        // Actualizar la contraseña y limpiar el token de recuperación
        $actualizado = $db->table('usuarios')
                          ->where('id', $usuario['id'])
                          ->update([
                              'contrasena'         => password_hash($nueva, PASSWORD_DEFAULT),
                              'token_recuperacion' => null,
                              'token_expiracion'   => null
                          ]);

        if ($actualizado) {
            return $this->response->setJSON([
                'exito'   => true,
                'mensaje' => '¡Tu nueva contraseña ha sido establecida con éxito! Ya puedes iniciar sesión en LAY-8.'
            ]);
        }

        return $this->response->setJSON(['exito' => false, 'mensaje' => 'Ocurrió un error al actualizar la contraseña en el servidor.']);
    }

    /**
     * Vista dedicada para restablecer con token desde el enlace recibido por correo
     */
    public function vistaRestablecerConToken()
    {
        $token = trim((string) $this->request->getGet('token'));
        $db = \Config\Database::connect();

        $usuario = null;
        $tokenValido = false;

        if (!empty($token)) {
            $usuario = $db->table('usuarios')
                          ->where('token_recuperacion', $token)
                          ->where('token_expiracion >=', date('Y-m-d H:i:s'))
                          ->get()
                          ->getRowArray();
            $tokenValido = ($usuario !== null);
        }

        return view('auth/restablecer_view', [
            'token'       => $token,
            'tokenValido' => $tokenValido,
            'usuario'     => $usuario
        ]);
    }

    /**
     * Procesa el cambio desde la vista con token
     */
    public function procesarRestablecerConToken()
    {
        $token   = trim((string) $this->request->getPost('token'));
        $nueva   = (string) $this->request->getPost('nueva_contrasena');
        $repetir = (string) $this->request->getPost('repetir_contrasena');

        if (empty($token)) {
            return redirect()->back()->with('error', 'Token de recuperación ausente o inválido.');
        }

        if (strlen($nueva) < 8) {
            return redirect()->back()->with('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
        }

        if ($nueva !== $repetir) {
            return redirect()->back()->with('error', 'Las contraseñas no coinciden.');
        }

        $db = \Config\Database::connect();
        $usuario = $db->table('usuarios')
                      ->where('token_recuperacion', $token)
                      ->where('token_expiracion >=', date('Y-m-d H:i:s'))
                      ->get()
                      ->getRowArray();

        if (!$usuario) {
            return redirect()->to(base_url('login'))->with('error', 'El enlace de recuperación ha caducado o ya fue utilizado.');
        }

        $db->table('usuarios')
           ->where('id', $usuario['id'])
           ->update([
               'contrasena'         => password_hash($nueva, PASSWORD_DEFAULT),
               'token_recuperacion' => null,
               'token_expiracion'   => null
           ]);

        return redirect()->to(base_url('login'))->with('mensaje', '¡Tu contraseña ha sido restablecida con éxito! Ingresa con tus nuevas credenciales.');
    }

    public function salir()
    {
        $session = session();
        $session->destroy();
        
        return redirect()->to('/');
    }
}
