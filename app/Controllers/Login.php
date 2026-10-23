<?php

namespace App\Controllers;

class Login extends BaseController
{
    public function index()
    {
        // Como el login ahora vive en el modal de la landing page,
        // si alguien intenta escribir "/login" en la URL, lo devolvemos al inicio.
        return redirect()->to('/');
    }

    public function autenticar()
    {
        $session = session();

        // 1. Recibimos específicamente el nombre de usuario y la contraseña del modal
        $nombre_usuario = trim($this->request->getPost('nombre_usuario'));
        $contrasena = $this->request->getPost('contrasena');

        // 2. SOLUCIÓN ANTIBALAS: Usamos la conexión directa a la base de datos
        // Esto ignora el archivo UsuarioModel.php y evita el error de mayúsculas/minúsculas de Git
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
            
            // REDIRECCIÓN DE ERROR: Vuelven a la landing page (raíz) con mensaje de error
            return redirect()->to('/')->with('error', 'Usuario o contraseña incorrectos.');
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