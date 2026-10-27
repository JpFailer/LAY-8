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

        if ($usuario['rol'] === 'admin') {
            return $this->response->setJSON([
                'exito' => false,
                'mensaje' => 'Por motivos estrictos de seguridad institucional, las cuentas de Administrador no pueden restablecer credenciales mediante este formulario. Debes acceder a la consola del servidor.'
            ]);
        }

        if ($usuario['rol'] === 'profesor') {
            return $this->response->setJSON([
                'exito' => false,
                'mensaje' => 'Las cuentas de personal docente no poseen Registro de Inscripción estudiantil. Para restablecer el acceso a tu cuenta docente, contacta a la Administración Universitaria de LAY-8 con tu carnet institucional.'
            ]);
        }

        if (($usuario['estado_cuenta'] ?? 'activo') === 'baneado') {
            return $this->response->setJSON([
                'exito' => false,
                'mensaje' => 'Esta cuenta se encuentra actualmente suspendida o inhabilitada.'
            ]);
        }

        return $this->response->setJSON([
            'exito' => true,
            'usuario_id' => (int) $usuario['id'],
            'nombre' => $usuario['primer_nombre'] . ' ' . $usuario['apellidos'],
            'rol' => ucfirst($usuario['rol']),
            'usuario' => $usuario['nombre_usuario'],
            'carrera' => $usuario['carrera'] ?? 'UMC'
        ]);
    }

    public function restablecerContrasena()
    {
        $usuarioId = (int) $this->request->getPost('usuario_id');
        $nueva     = (string) $this->request->getPost('nueva_contrasena');
        $repetir   = (string) $this->request->getPost('repetir_contrasena');

        if (!$usuarioId) {
            return $this->response->setJSON(['exito' => false, 'mensaje' => 'Identificador de usuario inválido.']);
        }

        if (strlen($nueva) < 8) {
            return $this->response->setJSON(['exito' => false, 'mensaje' => 'La nueva contraseña debe contener al menos 8 caracteres.']);
        }

        if ($nueva !== $repetir) {
            return $this->response->setJSON(['exito' => false, 'mensaje' => 'Las contraseñas no coinciden.']);
        }

        $db = \Config\Database::connect();
        $usuario = $db->table('usuarios')->where('id', $usuarioId)->get()->getRowArray();
        if (!$usuario) {
            return $this->response->setJSON(['exito' => false, 'mensaje' => 'Cuenta de usuario no encontrada en el sistema.']);
        }

        // ======================================================================
        // COMPROBACIÓN OBLIGATORIA DE SEGURIDAD ANTI-SUPLANTACIÓN (UMC)
        // ======================================================================
        $archivo = $this->request->getFile('planilla_inscripcion');
        if (!$archivo || !$archivo->isValid() || $archivo->hasMoved()) {
            return $this->response->setJSON([
                'exito' => false,
                'mensaje' => 'Por motivos estrictos de seguridad institucional y prevención de suplantación de identidad, debes adjuntar obligatoriamente tu Registro de Inscripción UMC oficial en formato PDF.'
            ]);
        }

        if ($archivo->getClientMimeType() !== 'application/pdf') {
            return $this->response->setJSON([
                'exito' => false,
                'mensaje' => 'El comprobante debe ser exclusivamente un archivo digital PDF emitido por el sistema oficial campus.umc.edu.ve.'
            ]);
        }

        $verificador = new VerificadorInscripcionService();
        $carreraUsuario = $usuario['carrera'] ?? null;
        $analisis = $verificador->verificar($archivo->getTempName(), $carreraUsuario);

        if (!$analisis['es_valido']) {
            return $this->response->setJSON([
                'exito' => false,
                'mensaje' => 'Seguridad UMC: ' . ($analisis['motivo_invalidez'] ?? 'El documento de inscripción no superó los filtros de autenticidad institucional.')
            ]);
        }

        // Validar concordancia de titular para evitar que suban el documento de otro estudiante
        if (!empty($analisis['estudiante'])) {
            $norm = function(string $s): string {
                $s = mb_strtolower(trim($s), 'UTF-8');
                $r = ['á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ñ'=>'n', 'ü'=>'u'];
                return strtr($s, $r);
            };

            $nombreDoc     = $norm($analisis['estudiante']);
            $primerNombre  = $norm($usuario['primer_nombre']);
            $apellidos     = $norm($usuario['apellidos']);

            $coincide = (strpos($nombreDoc, $primerNombre) !== false) || (strpos($nombreDoc, $apellidos) !== false);
            if (!$coincide) {
                $partes = explode(' ', $apellidos);
                foreach ($partes as $p) {
                    if (strlen($p) > 2 && strpos($nombreDoc, $p) !== false) {
                        $coincide = true;
                        break;
                    }
                }
            }

            if (!$coincide) {
                return $this->response->setJSON([
                    'exito' => false,
                    'mensaje' => 'Alerta de Seguridad: El comprobante de inscripción adjunto pertenece al titular "' . $analisis['estudiante'] . '", el cual no coincide con la cuenta solicitada. Solo el estudiante legítimo puede restablecer la clave.'
                ]);
            }
        }

        // Actualizar la contraseña
        $actualizado = $db->table('usuarios')
                          ->where('id', $usuarioId)
                          ->update([
                              'contrasena' => password_hash($nueva, PASSWORD_DEFAULT)
                          ]);

        if ($actualizado) {
            return $this->response->setJSON([
                'exito' => true,
                'mensaje' => '¡Identidad verificada exitosamente con tu Registro de Inscripción UMC! Tu nueva contraseña ha sido establecida con éxito.'
            ]);
        }

        return $this->response->setJSON(['exito' => false, 'mensaje' => 'Ocurrió un error al actualizar la contraseña en el servidor.']);
    }

    public function salir()
    {
        $session = session();
        $session->destroy();
        
        return redirect()->to('/');
    }
}
