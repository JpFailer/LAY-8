<?php
namespace App\Controllers;
use App\Models\UsuarioModel;

class Usuario extends BaseController
{
    public function perfil()
    {
        return view('perfil_view');
    }

    public function solicitarProfesor()
    {
        $session = session();
        $archivo = $this->request->getFile('carnet');
        
        if ($archivo->isValid() && !$archivo->hasMoved()) {
            if (in_array($archivo->getMimeType(), ['image/jpeg', 'image/png', 'application/pdf'])) {
                
                $nombre_nuevo = $archivo->getRandomName();
                $archivo->move(ROOTPATH . 'public/uploads/carnets', $nombre_nuevo);
                $ruta_carnet = 'uploads/carnets/' . $nombre_nuevo;
                
                $usuarioModel = new UsuarioModel();
                $usuarioModel->update($session->get('usuario_id'), [
                    'rol'                 => 'profesor',
                    'estado_verificacion' => 'pendiente',
                    'ruta_carnet'         => $ruta_carnet
                ]);
                
                $session->set('rol', 'profesor');
                $session->set('estado_verificacion', 'pendiente');
                
                return redirect()->to('/perfil')->with('mensaje', 'Solicitud enviada. Está en revisión.');
            } else {
                return redirect()->to('/perfil')->with('error', 'Formato no válido. Solo JPG, PNG o PDF.');
            }
        }
        return redirect()->to('/perfil')->with('error', 'Error al subir el archivo.');
    }
}