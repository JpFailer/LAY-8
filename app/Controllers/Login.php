<?php
namespace App\Controllers;
use App\Models\UsuarioModel;

class Login extends BaseController
{
    public function index()
    {
        return view('auth/login_view');
    }

public function autenticar()
    {
        $session = session();
        $usuarioModel = new \App\Models\UsuarioModel();

        // 1. Ahora recibimos específicamente el nombre de usuario
        $nombre_usuario = trim($this->request->getPost('nombre_usuario'));
        $contrasena = $this->request->getPost('contrasena');

        // 2. Buscamos SOLO por nombre_usuario (eliminamos la búsqueda por correo)
        $usuario = $usuarioModel->where('nombre_usuario', $nombre_usuario)->first();

        // 3. Verificamos contraseñas
        if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
            $session->set([
                'usuario_id'          => $usuario['id'],
                'nombre_usuario'      => $usuario['nombre_usuario'],
                'primer_nombre'       => $usuario['primer_nombre'],
                'apellidos'           => $usuario['apellidos'],
                'rol'                 => $usuario['rol'],
                'estado_verificacion' => $usuario['estado_verificacion'],
                'isLoggedIn'          => true
            ]);
            return redirect()->to('/');
        } else {
            return redirect()->to('/login')->with('error', 'Usuario o contraseña incorrectos.');
        }
    }

    public function salir()
    {
        session()->destroy();
        return redirect()->to('/login')->with('mensaje', 'Sesión cerrada exitosamente.');
    }
}