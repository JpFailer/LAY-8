<?php
namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // 1. Verificación estricta de sesión en el servidor
        if (!$session->get('isLoggedIn') || !$session->get('usuario_id')) {
            // Si es una petición API/Fetch, retornamos JSON 401 en vez de redirigir
            if ($request->isAJAX() || str_starts_with($request->getUri()->getPath(), 'api/')) {
                return response()->setStatusCode(401)->setJSON([
                    'success' => false,
                    'error'   => 'No autorizado. Debes iniciar sesión.'
                ]);
            }
            return redirect()->to('/login')->with('error', 'Debes iniciar sesión para acceder.');
        }

        // 2. Validación de integridad: confirmamos que el usuario aún existe en la base de datos
        $db = \Config\Database::connect();
        $usuario = $db->table('usuarios')
                      ->select('id, rol, estado_verificacion, estado_cuenta, motivo_baneo')
                      ->where('id', (int) $session->get('usuario_id'))
                      ->get()
                      ->getRowArray();

        if (!$usuario) {
            $session->destroy();
            return redirect()->to('/login')->with('error', 'Tu sesión ha expirado o el usuario no existe.');
        }

        // Si la cuenta fue suspendida o baneada por la administración
        if (isset($usuario['estado_cuenta']) && $usuario['estado_cuenta'] === 'baneado') {
            $session->destroy();
            $motivo = !empty($usuario['motivo_baneo']) ? ": {$usuario['motivo_baneo']}" : '.';
            if ($request->isAJAX() || str_starts_with($request->getUri()->getPath(), 'api/')) {
                return response()->setStatusCode(403)->setJSON([
                    'success' => false,
                    'error'   => "Tu cuenta ha sido suspendida/baneada{$motivo}"
                ]);
            }
            return redirect()->to('/login')->with('error', "Tu cuenta ha sido suspendida/baneada{$motivo}");
        }

        // Mantenemos sincronizado el rol y verificación si cambiaron en la base de datos
        $session->set('rol', $usuario['rol']);
        $session->set('estado_verificacion', $usuario['estado_verificacion']);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
