<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $isApi = ($request->isAJAX() || str_starts_with($request->getUri()->getPath(), 'api/'));

        // 1. Debe estar logueado
        if (!$session->get('isLoggedIn') || !$session->get('usuario_id')) {
            if ($isApi) {
                return response()->setStatusCode(401)->setJSON([
                    'success' => false,
                    'error'   => 'No autorizado: Debes iniciar sesión con una cuenta de Administrador.'
                ]);
            }
            return redirect()->to('/login')->with('error', 'Debes iniciar sesión para acceder al panel administrativo.');
        }

        // 2. Validación estricta del lado del servidor contra la base de datos
        $db = \Config\Database::connect();
        $usuario = $db->table('usuarios')
                      ->select('id, rol, estado_cuenta')
                      ->where('id', (int) $session->get('usuario_id'))
                      ->get()
                      ->getRowArray();

        // 3. Si el usuario no existe o fue suspendido/baneado
        if (!$usuario || (isset($usuario['estado_cuenta']) && $usuario['estado_cuenta'] === 'baneado')) {
            $session->destroy();
            if ($isApi) {
                return response()->setStatusCode(403)->setJSON([
                    'success' => false,
                    'error'   => 'Sesión inválida o la cuenta ha sido suspendida.'
                ]);
            }
            return redirect()->to('/login')->with('error', 'Tu cuenta ha sido suspendida o no existe.');
        }

        // 4. Verificación de rol administrativo
        if (!in_array($usuario['rol'], ['administrador', 'admin'], true)) {
            if ($isApi) {
                return response()->setStatusCode(403)->setJSON([
                    'success' => false,
                    'error'   => 'Acceso denegado: Se requieren privilegios de Administrador para esta acción.'
                ]);
            }
            return redirect()->to('/dashboard')->with('error', 'Acceso denegado: Área exclusiva de Administradores.');
        }

        // Sincronizar rol en sesión por si cambió
        $session->set('rol', $usuario['rol']);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
