<?php
namespace App\Controllers;
use App\Models\UsuarioModel;

class Registro extends BaseController
{
    public function index()
    {
        return view('auth/registro_view');
    }

    public function procesar()
    {
        $reglas = [
            'primer_nombre'      => 'required',
            'apellidos'          => 'required',
            'nombre_usuario'     => 'required|is_unique[usuarios.nombre_usuario]',
            'correo'             => 'required|valid_email|is_unique[usuarios.correo]',
            'contrasena'         => 'required|min_length[8]',
            'repetir_contrasena' => 'matches[contrasena]'
        ];

        if (!$this->validate($reglas)) {
            return view('auth/registro_view', ['errores' => $this->validator]);
        }

        $datos = [
            'primer_nombre'       => trim($this->request->getPost('primer_nombre')),
            'segundo_nombre'      => trim($this->request->getPost('segundo_nombre')) ?: null,
            'apellidos'           => trim($this->request->getPost('apellidos')),
            'nombre_usuario'      => trim($this->request->getPost('nombre_usuario')),
            'correo'              => trim($this->request->getPost('correo')),
            'telefono'            => trim($this->request->getPost('telefono')) ?: null,
            'contrasena'          => password_hash($this->request->getPost('contrasena'), PASSWORD_DEFAULT),
            'rol'                 => 'estudiante',
            'estado_verificacion' => 'no_aplica',
            'ruta_carnet'         => null
        ];

        if ($this->request->getPost('es_profesor') === 'si') {
            $archivo = $this->request->getFile('carnet');
            if ($archivo->isValid() && !$archivo->hasMoved()) {
                if (in_array($archivo->getMimeType(), ['image/jpeg', 'image/png', 'application/pdf'])) {
                    $nombre_nuevo = $archivo->getRandomName();
                    $archivo->move(ROOTPATH . 'public/uploads/carnets', $nombre_nuevo);
                    $datos['ruta_carnet'] = 'uploads/carnets/' . $nombre_nuevo;
                    $datos['estado_verificacion'] = 'pendiente';
                    $datos['rol'] = 'profesor';
                }
            }
        }

        $usuarioModel = new UsuarioModel();
        $usuarioModel->insert($datos);

        return redirect()->to('/login')->with('mensaje', 'Cuenta creada. Si enviaste un carnet, está pendiente de revisión.');
    }
}