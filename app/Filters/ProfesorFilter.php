<?php
namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class ProfesorFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session   = session();
        $usuarioId = (int) $session->get('usuario_id');
        $rol       = $session->get('rol');
        $estado    = $session->get('estado_verificacion');

        // Sincronizar rol y estado_verificacion reales desde BD para reflejar aprobaciones o revocaciones inmediatas
        if ($usuarioId > 0) {
            try {
                $usuarioDb = \Config\Database::connect()
                    ->table('usuarios')
                    ->select('rol, estado_verificacion')
                    ->where('id', $usuarioId)
                    ->get()
                    ->getRowArray();

                if ($usuarioDb) {
                    $rol    = $usuarioDb['rol'];
                    $estado = $usuarioDb['estado_verificacion'];
                    $session->set([
                        'rol'                 => $rol,
                        'estado_verificacion' => $estado,
                    ]);
                }
            } catch (\Throwable $e) {
                // Si hay fallo temporal de conexión, continuar con valores de sesión
            }
        }

        $path = trim($request->getUri()->getPath(), '/');
        $isApi = (
            $request->isAJAX() ||
            str_contains($path, 'api/') ||
            str_contains($path, 'docente/entregas') ||
            str_contains($path, 'docente/calificar') ||
            str_contains($path, 'docente/crear-tarea') ||
            str_contains($path, 'docente/actualizar-estado') ||
            str_contains($path, 'docente/responder-solicitud-extemporanea')
        );

        // sistema de control de acceso por roles (RBAC) antidayanas (permite profes verificados y admins)
        $esAdmin = in_array($rol, ['administrador', 'admin'], true);
        $esProfesorAprobado = ($rol === 'profesor' && $estado === 'aprobado');

        if (!$esAdmin && !$esProfesorAprobado) {
            if ($isApi) {
                return response()->setStatusCode(403)->setJSON([
                    'success' => false,
                    'error'   => 'Acceso denegado: Se requiere ser docente verificado y aprobado por un administrador.'
                ]);
            }

            if ($rol === 'profesor' && $estado === 'pendiente') {
                return redirect()->to('/dashboard')->with('error', 'Tu carnet o constancia docente está en revisión por un administrador. El acceso al Panel Docente se habilitará tras su aprobación.');
            }
            if ($rol === 'profesor' && $estado === 'rechazado') {
                return redirect()->to('/dashboard')->with('error', 'Tu solicitud docente fue rechazada. Adjunta un carnet o constancia válida para solicitar revisión nuevamente.');
            }
            return redirect()->to('/dashboard')->with('error', 'Acceso exclusivo para profesores acreditados y aprobados por un administrador.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
