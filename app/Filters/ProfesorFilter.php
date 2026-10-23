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

        if ($rol !== 'profesor' || $estado !== 'aprobado') {
            if ($rol === 'profesor' && $estado === 'pendiente') {
                return redirect()->to('/perfil')->with('error', 'Tu carnet está en revisión. Aún no puedes crear salas.');
            }
            if ($rol === 'profesor' && $estado === 'rechazado') {
                return redirect()->to('/perfil')->with('error', 'Tu solicitud fue rechazada. Sube un carnet válido.');
            }
            return redirect()->to('/mapa')->with('error', 'No tienes permisos de profesor.');
        }
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}