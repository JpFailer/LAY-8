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

        // Mostramos la vista de inicio de sesión dedicada
        return view('auth/login_view');
    }

    public function autenticar()
    {
        $session = session();

        // 1. Recibimos específicamente el nombre de usuario y la contraseña
        $nombre_usuario = trim($this->request->getPost('nombre_usuario'));
        $contrasena = $this->request->getPost('contrasena');

        // 2. Conexión a la base de datos
        $db = \Config\Database::connect();
        $usuario = $db->table('usuarios')->where('nombre_usuario', $nombre_usuario)->get()->getRowArray();

        // 3. Verificamos contraseñas
        if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
            
            // Registramos los datos del usuario en la sesión
            $session->set([
                'usuario_id'          => $usuario['id'],
                'nombre_usuario'      => $usuario['nombre_usuario'],
                'primer_nombre'       => $usuario['primer_nombre'],
                'apellidos'           => $usuario['apellidos'],
                'rol'                 => $usuario['rol'],
                'estado_verificacion' => $usuario['estado_verificacion'],
                'isLoggedIn'          => true
            ]);
            
            // REDIRECCIÓN DE ÉXITO: Van al sistema interno (dashboard)
            return redirect()->to('/dashboard');
            
        } else {
            // REDIRECCIÓN DE ERROR: Vuelven atrás con mensaje de error
            return redirect()->back()->with('error', 'Usuario o contraseña incorrectos.');
        }
    }

    public function salir()
    {
        $session = session();
        $session->destroy();
        
        // Al cerrar sesión, los mandamos a la landing page
        return redirect()->to('/');
    }
}
