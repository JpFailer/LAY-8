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
        $usuarioId = (int) $session->get('usuario_id');
        if ($usuarioId <= 0) {
            return redirect()->to('/login')->with('error', 'Debes iniciar sesión para realizar esta solicitud.');
        }

        if (in_array($session->get('rol'), ['administrador', 'admin'], true)) {
            return redirect()->to('/perfil')->with('error', 'Las cuentas de administrador no pueden solicitar cambio de rol.');
        }

        $archivo = $this->request->getFile('carnet');

        if (!$archivo || !$archivo->isValid() || $archivo->hasMoved()) {
            return redirect()->to('/perfil')->with('error', 'Error al subir el archivo.');
        }

        $extension = strtolower((string) $archivo->getExtension());
        $mimeType  = (string) $archivo->getMimeType();
        if (
            !in_array($mimeType, ['image/jpeg', 'image/png', 'application/pdf'], true)
            || !in_array($extension, ['jpg', 'jpeg', 'png', 'pdf'], true)
            || $archivo->getSize() > (5 * 1024 * 1024)
        ) {
            return redirect()->to('/perfil')->with('error', 'Formato o tamaño no válido. Solo JPG, PNG o PDF de hasta 5 MB.');
        }

        $carpetaCarnets = ROOTPATH . 'public/uploads/carnets';
        if (!is_dir($carpetaCarnets)) {
            mkdir($carpetaCarnets, 0775, true);
        }

        $nombre_nuevo = $archivo->getRandomName();
        $archivo->move($carpetaCarnets, $nombre_nuevo);
        $ruta_carnet = 'uploads/carnets/' . $nombre_nuevo;

        $usuarioModel = new UsuarioModel();
        $usuarioModel->update($usuarioId, [
            'rol'                 => 'profesor',
            'estado_verificacion' => 'pendiente',
            'ruta_carnet'         => $ruta_carnet,
        ]);

        $session->set('rol', 'profesor');
        $session->set('estado_verificacion', 'pendiente');

        return redirect()->to('/perfil')->with('mensaje', 'Solicitud enviada. Está en revisión.');
    }
}