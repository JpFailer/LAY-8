<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\AporteBibliotecaModel;
use App\Services\VerificadorInscripcionService;

class PerfilController extends BaseController
{
    public function index()
    {
        $usuarioModel = new UsuarioModel();
        $idUsuario = session()->get('usuario_id'); 
        
        $usuario = $usuarioModel->find($idUsuario);
        
        if (!$usuario) {
            return redirect()->to(base_url('login'))->with('error', 'Sesión expirada o usuario no encontrado.');
        }
        
        $datos = [
            'usuario'       => $usuario,
            'total_aportes' => (new AporteBibliotecaModel())->where('usuario_id', $idUsuario)->countAllResults(),
            'total_likes'   => 0
        ];

        return view('perfil_view', $datos);
    }

    public function subirFoto()
    {
        $idUsuario = session()->get('usuario_id');
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
            $archivo->move(FCPATH . 'uploads/perfiles', $nombreAleatorio);

            $usuarioModel = new UsuarioModel();
            $usuarioModel->update($idUsuario, [
                'foto_perfil' => 'uploads/perfiles/' . $nombreAleatorio
            ]);

            session()->set('foto_perfil', 'uploads/perfiles/' . $nombreAleatorio);
            return redirect()->back()->with('mensaje', 'Foto de perfil actualizada con éxito.');
        }
        
        return redirect()->back()->with('error', 'Ocurrió un error al subir la imagen.');
    }

    /**
     * Actualiza la información de perfil, foto y certificación de cambio de carrera
     */
    public function actualizar()
    {
        $idUsuario = (int) session()->get('usuario_id');
        if (!$idUsuario) {
            return redirect()->to(base_url('login'))->with('error', 'Sesión expirada.');
        }

        $usuarioModel = new UsuarioModel();
        $usuarioActual = $usuarioModel->find($idUsuario);
        if (!$usuarioActual) {
            return redirect()->to(base_url('login'))->with('error', 'Usuario no localizado.');
        }

        $carrerasValidas = ['ING-INF', 'ING-MAR', 'ING-AMB', 'ADM', 'TUR'];
        $carreraSolicitada = (string) $this->request->getPost('carrera');
        $carreraPrevia = $usuarioActual['carrera'] ?? 'ING-INF';

        $datosActualizar = [];
        $mensajeExtra = '';

        // Si el usuario está cambiando a una carrera distinta a la que tenía
        if (in_array($carreraSolicitada, $carrerasValidas, true) && $carreraSolicitada !== $carreraPrevia) {
            // 🛡️ Filtro de Seguridad 1: Confirmación con Contraseña
            $passwordConfirm = (string) $this->request->getPost('confirmar_contrasena');
            if (empty($passwordConfirm) || !password_verify($passwordConfirm, $usuarioActual['contrasena'])) {
                return redirect()->back()->with('error', 'Seguridad: Para cambiar tu carrera debes ingresar tu contraseña actual correctamente.');
            }

            // 🛡️ Filtro de Seguridad 2: Comprobante Oficial UMC (PDF)
            $archivoInscripcion = $this->request->getFile('planilla_inscripcion');
            if (!$archivoInscripcion || !$archivoInscripcion->isValid() || $archivoInscripcion->hasMoved()) {
                return redirect()->back()->with('error', 'Seguridad: Para certificar un cambio de carrera es obligatorio adjuntar tu Registro de Inscripción emitido por campus.umc.edu.ve en PDF.');
            }

            if ($archivoInscripcion->getClientMimeType() !== 'application/pdf') {
                return redirect()->back()->with('error', 'El comprobante debe ser un archivo PDF oficial de la UMC.');
            }

            // 🛡️ Filtro de Seguridad 3: Análisis Anti-Fraude y Detección de Materias
            $verificador = new VerificadorInscripcionService();
            $analisis = $verificador->verificar($archivoInscripcion->getTempName(), $carreraSolicitada);

            if (!$analisis['es_valido']) {
                return redirect()->back()->with('error', 'Certificación denegada: ' . ($analisis['motivo_invalidez'] ?? 'El documento no superó los controles de seguridad institucionales.'));
            }

            // Comprobación de Titularidad: Cotejo estricto Cédula usuario vs Cédula oficial en el PDF UMC
            $cedulaPdfDetectada = preg_replace('/\D/', '', (string)($analisis['cedula'] ?? ''));
            $cedulaUsuario = preg_replace('/\D/', '', (string)($usuarioActual['cedula'] ?? ''));
            if (!empty($cedulaPdfDetectada) && !empty($cedulaUsuario) && $cedulaPdfDetectada !== $cedulaUsuario) {
                return redirect()->back()->with('error', "Seguridad UMC: La cédula del comprobante ({$cedulaPdfDetectada}) no coincide con la de tu cuenta ({$cedulaUsuario}).");
            }

            $datosActualizar['carrera'] = $analisis['carrera_codigo'];
            session()->set('carrera', $analisis['carrera_codigo']);

            // Asignación automática de las materias a las aulas
            $aulasInscritas = $verificador->inscribirEstudianteEnMaterias($idUsuario, $analisis['materias'], $analisis['periodo']);
            $mensajeExtra = " Carrera certificada como {$analisis['carrera_nombre']}. Se vincularon automáticamente {$aulasInscritas} nuevas aulas a tu perfil.";
        }

        // Subida opcional de nueva foto de perfil
        $archivoFoto = $this->request->getFile('foto');
        if ($archivoFoto && $archivoFoto->isValid() && !$archivoFoto->hasMoved()) {
            if (in_array($archivoFoto->getMimeType(), ['image/png', 'image/jpeg', 'image/jpg']) && $archivoFoto->getSize() <= 2048 * 1024) {
                $nombreAleatorio = $archivoFoto->getRandomName();
                $archivoFoto->move(FCPATH . 'uploads/perfiles', $nombreAleatorio);
                $nuevaRuta = 'uploads/perfiles/' . $nombreAleatorio;
                $datosActualizar['foto_perfil'] = $nuevaRuta;
                session()->set('foto_perfil', $nuevaRuta);
            }
        }

        if (!empty($datosActualizar)) {
            $usuarioModel->update($idUsuario, $datosActualizar);
            return redirect()->back()->with('mensaje', 'Perfil actualizado exitosamente.' . $mensajeExtra);
        }

        return redirect()->back()->with('mensaje', 'Información de perfil al día.');
    }
}
