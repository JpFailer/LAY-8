<?php
namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class ProfesorFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $rol = $session->get('rol');
        $estado = $session->get('estado_verificacion');
        $isApi = ($request->isAJAX() || str_starts_with($request->getUri()->getPath(), 'api/'));

        if ($rol !== 'profesor' || $estado !== 'aprobado') {
            if ($isApi) {
                return response()->setStatusCode(403)->setJSON([
                    'success' => false,
                    'error'   => 'Acceso denegado: Se requieren permisos de profesor verificado y aprobado.'
                ]);
            }

            if ($rol === 'profesor' && $estado === 'pendiente') {
                return redirect()->to('/perfil')->with('error', 'Tu carnet está en revisión. Aún no puedes gestionar clases.');
            }
            if ($rol === 'profesor' && $estado === 'rechazado') {
                return redirect()->to('/perfil')->with('error', 'Tu solicitud fue rechazada. Por favor sube un carnet válido.');
            }
            return redirect()->to('/dashboard')->with('error', 'No tienes permisos de profesor.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
