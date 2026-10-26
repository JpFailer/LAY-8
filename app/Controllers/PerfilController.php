<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

class PerfilController extends BaseController
{
    public function index()
    {
        $usuarioModel = new UsuarioModel();
        
        // Extrae el ID usando la clave exacta encontrada en tu sesión
        $idUsuario = session()->get('usuario_id'); 
        
        $usuario = $usuarioModel->find($idUsuario);
        
        // Validación de seguridad: evita la pantalla de error si la sesión caducó
        if (!$usuario) {
            return redirect()->to(base_url('login'))->with('error', 'Sesión expirada o usuario no encontrado.');
        }
        
        $datos = [
            'usuario'       => $usuario,
            'total_aportes' => (new \App\Models\AporteBibliotecaModel())->where('usuario_id', $idUsuario)->countAllResults(),
            'total_likes'   => 0  // Se conectará al feed más adelante
        ];

        return view('perfil_view', $datos);
    }

    public function subirFoto()
    {
        // Se asegura de usar la misma clave para que la foto se guarde en tu usuario
        $idUsuario = session()->get('usuario_id');
        
        // Validación extra de seguridad
        if (!$idUsuario) {
            return redirect()->to(base_url('login'))->with('error', 'Sesión expirada.');
        }

        $validacion = $this->validate([
            'foto' => [
                'uploaded[foto]',
                'mime_in[foto,image/jpg,image/jpeg,image/png]',
                'max_size[foto,2048]',
            ]
        ]);

        if (!$validacion) {
            return redirect()->back()->with('error', 'Formato inválido o excede 2MB. (Solo PNG y JPG).');
        }

        $archivo = $this->request->getFile('foto');
        
        if ($archivo->isValid() && !$archivo->hasMoved()) {
            $nombreAleatorio = $archivo->getRandomName();
            
            // Guarda la imagen en la carpeta public/uploads/perfiles
            $archivo->move(FCPATH . 'uploads/perfiles', $nombreAleatorio);

            // Actualiza la columna 'foto_perfil' en la base de datos
            $usuarioModel = new UsuarioModel();
            $usuarioModel->update($idUsuario, [
                'foto_perfil' => 'uploads/perfiles/' . $nombreAleatorio
            ]);

            //Actualiza la sesion (pa q salga la foto actualizada al volver al inicio)
            session()->set('foto_perfil', 'uploads/perfiles/' . $nombreAleatorio);

            return redirect()->back()->with('mensaje', 'Foto actualizada con éxito.');
        }
        
        return redirect()->back()->with('error', 'Ocurrió un error al subir la imagen.');
    }

    /**
     * Actualiza la información de perfil, carrera seleccionada y foto
     */
    public function actualizar()
    {
        $idUsuario = (int) session()->get('usuario_id');
        if (!$idUsuario) {
            return redirect()->to(base_url('login'))->with('error', 'Sesión expirada.');
        }

        $usuarioModel = new UsuarioModel();
        $carrerasValidas = ['ING-INF', 'ING-MAR', 'ING-AMB', 'ADM', 'TUR'];
        $carrera = (string) $this->request->getPost('carrera');

        $datosActualizar = [];
        if (in_array($carrera, $carrerasValidas, true)) {
            $datosActualizar['carrera'] = $carrera;
            session()->set('carrera', $carrera);
        }

        // Subida opcional de nueva foto
        $archivo = $this->request->getFile('foto');
        if ($archivo && $archivo->isValid() && !$archivo->hasMoved()) {
            if (in_array($archivo->getMimeType(), ['image/png', 'image/jpeg', 'image/jpg']) && $archivo->getSize() <= 2048 * 1024) {
                $nombreAleatorio = $archivo->getRandomName();
                $archivo->move(FCPATH . 'uploads/perfiles', $nombreAleatorio);
                $nuevaRuta = 'uploads/perfiles/' . $nombreAleatorio;
                $datosActualizar['foto_perfil'] = $nuevaRuta;
                session()->set('foto_perfil', $nuevaRuta);
            }
        }

        if (!empty($datosActualizar)) {
            $usuarioModel->update($idUsuario, $datosActualizar);
            return redirect()->back()->with('mensaje', 'Perfil y carrera actualizados exitosamente.');
        }

        return redirect()->back();
    }
}