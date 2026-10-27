<?php
namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Services\VerificadorInscripcionService;

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
            'repetir_contrasena' => 'matches[contrasena]',
            'terminos'           => 'required'
        ];

        // Mensajes de validación en español amigables (Revisión Haryerit)
        $mensajes = [
            'primer_nombre' => [
                'required' => 'El primer nombre es obligatorio.'
            ],
            'apellidos' => [
                'required' => 'Los apellidos son obligatorios.'
            ],
            'nombre_usuario' => [
                'required'  => 'El nombre de usuario es obligatorio.',
                'is_unique' => 'Ese nombre de usuario ya existe. Por favor elige otro.'
            ],
            'correo' => [
                'required'    => 'El correo electrónico es obligatorio.',
                'valid_email' => 'Por favor introduce un correo electrónico válido.',
                'is_unique'   => 'Este correo electrónico ya está registrado en LAY-8.'
            ],
            'contrasena' => [
                'required'   => 'La contraseña es obligatoria.',
                'min_length' => 'La contraseña debe contener al menos 8 caracteres.'
            ],
            'repetir_contrasena' => [
                'matches' => 'Las contraseñas no coinciden.'
            ],
            'terminos' => [
                'required' => 'Debes leer y aceptar los Términos y Condiciones de uso para registrarte en LAY-8.'
            ]
        ];

        // Si el usuario indicó quitar el archivo previo en la interfaz, limpiar la sesión y el archivo
        if ($this->request->getPost('tiene_archivo_previo') === '0') {
            if (session()->has('temp_registro_pdf')) {
                $prev = session()->get('temp_registro_pdf');
                if (file_exists($prev)) {
                    @unlink($prev);
                }
                session()->remove(['temp_registro_pdf', 'temp_registro_pdf_nombre']);
            }
        }

        // Preservación de documento PDF cargado en caso de fallos posteriores en el paso 2
        $archivoTempInscripcion = $this->request->getFile('planilla_inscripcion');
        if ($archivoTempInscripcion && $archivoTempInscripcion->isValid() && !$archivoTempInscripcion->hasMoved()) {
            if ($archivoTempInscripcion->getClientMimeType() === 'application/pdf') {
                $carpetaTemp = WRITEPATH . 'uploads/temp_registro';
                if (!is_dir($carpetaTemp)) {
                    mkdir($carpetaTemp, 0777, true);
                }
                if (session()->has('temp_registro_pdf')) {
                    $prev = session()->get('temp_registro_pdf');
                    if (file_exists($prev)) {
                        @unlink($prev);
                    }
                }
                $nombreTemp = 'temp_' . md5(session_id() . time()) . '.pdf';
                $archivoTempInscripcion->move($carpetaTemp, $nombreTemp);
                session()->set('temp_registro_pdf', $carpetaTemp . DIRECTORY_SEPARATOR . $nombreTemp);
                session()->set('temp_registro_pdf_nombre', $archivoTempInscripcion->getClientName());
            }
        }

        if (!$this->validate($reglas, $mensajes)) {
            return redirect()->back()->withInput()->with('errores', $this->validator->getErrors());
        }

        $carreraSeleccionada = in_array((string)$this->request->getPost('carrera'), ['ING-INF', 'ING-MAR', 'ING-AMB', 'ADM', 'TUR'], true) 
            ? (string)$this->request->getPost('carrera') 
            : 'ING-INF';

        $datos = [
            'primer_nombre'       => trim($this->request->getPost('primer_nombre')),
            'segundo_nombre'      => trim($this->request->getPost('segundo_nombre')) ?: null,
            'apellidos'           => trim($this->request->getPost('apellidos')),
            'nombre_usuario'      => trim($this->request->getPost('nombre_usuario')),
            'correo'              => trim($this->request->getPost('correo')),
            'telefono'            => trim($this->request->getPost('telefono')) ?: null,
            'contrasena'          => password_hash($this->request->getPost('contrasena'), PASSWORD_DEFAULT),
            'rol'                 => 'estudiante',
            'carrera'             => $carreraSeleccionada,
            'estado_cuenta'       => 'activo',
            'estado_verificacion' => 'no_aplica',
            'ruta_carnet'         => null,
            'fecha_registro'      => date('Y-m-d H:i:s')
        ];

        $esProfesor = ($this->request->getPost('es_profesor') === 'si');
        $materiasInscribir = [];
        $periodoInscribir  = null;
        $carreraCertificadaNombre = '';

        if ($esProfesor) {
            $archivo = $this->request->getFile('carnet');
            if ($archivo && $archivo->isValid() && !$archivo->hasMoved()) {
                if (in_array($archivo->getMimeType(), ['image/jpeg', 'image/png', 'application/pdf'])) {
                    $carpetaCarnets = ROOTPATH . 'public/uploads/carnets';
                    if (!is_dir($carpetaCarnets)) {
                        mkdir($carpetaCarnets, 0777, true);
                    }
                    $nombre_nuevo = $archivo->getRandomName();
                    $archivo->move($carpetaCarnets, $nombre_nuevo);
                    $datos['ruta_carnet'] = 'uploads/carnets/' . $nombre_nuevo;
                    $datos['estado_verificacion'] = 'pendiente';
                    $datos['rol'] = 'profesor';
                } else {
                    return redirect()->back()->withInput()->with('error', 'Formato de carnet inválido. Solo se admiten JPG, PNG o PDF.');
                }
            } else {
                return redirect()->back()->withInput()->with('error', 'Como docente, debes adjuntar obligatoriamente el carnet o constancia institucional para revisión.');
            }
        } else {
            // Verificación Inteligente y Anti-Fraude del Registro de Inscripción UMC
            $rutaArchivoVerificar = null;
            if (session()->has('temp_registro_pdf') && file_exists(session()->get('temp_registro_pdf'))) {
                $rutaArchivoVerificar = session()->get('temp_registro_pdf');
            } else {
                $archivoInscripcion = $this->request->getFile('planilla_inscripcion');
                if ($archivoInscripcion && $archivoInscripcion->isValid() && !$archivoInscripcion->hasMoved()) {
                    if ($archivoInscripcion->getClientMimeType() === 'application/pdf') {
                        $rutaArchivoVerificar = $archivoInscripcion->getTempName();
                    } else {
                        return redirect()->back()->withInput()->with('error', 'El comprobante debe ser exclusivamente un archivo PDF original emitido por el sistema campus.umc.edu.ve.');
                    }
                }
            }

            if (!$rutaArchivoVerificar || !file_exists($rutaArchivoVerificar)) {
                return redirect()->back()->withInput()->with('error', 'Por motivos de seguridad, debes adjuntar tu Registro de Inscripción oficial emitido por la UMC (formato PDF).');
            }

            $verificador = new VerificadorInscripcionService();
            $analisis = $verificador->verificar($rutaArchivoVerificar, $carreraSeleccionada);

            if (!$analisis['es_valido']) {
                if (session()->has('temp_registro_pdf')) {
                    $p = session()->get('temp_registro_pdf');
                    if (file_exists($p)) {
                        @unlink($p);
                    }
                    session()->remove(['temp_registro_pdf', 'temp_registro_pdf_nombre']);
                }
                return redirect()->back()->withInput()->with('error', 'Seguridad UMC: ' . ($analisis['motivo_invalidez'] ?? 'El documento no superó los filtros de autenticidad institucional.'));
            }

            // Carrera certificada por los filtros del documento
            $datos['carrera'] = $analisis['carrera_codigo'];
            $materiasInscribir = $analisis['materias'];
            $periodoInscribir  = $analisis['periodo'];
            $carreraCertificadaNombre = $analisis['carrera_nombre'];
        }

        $usuarioModel = new UsuarioModel();
        $nuevoUsuarioId = $usuarioModel->insert($datos, true);

        // Limpiar archivo temporal de sesión si existía
        if (session()->has('temp_registro_pdf')) {
            $tempPath = session()->get('temp_registro_pdf');
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
            session()->remove(['temp_registro_pdf', 'temp_registro_pdf_nombre']);
        }

        // Si es estudiante, inscribir automáticamente en las aulas detectadas en el documento
        $aulasMensaje = '';
        if (!$esProfesor && $nuevoUsuarioId && !empty($materiasInscribir)) {
            $verificador = new VerificadorInscripcionService();
            $aulasInscritas = $verificador->inscribirEstudianteEnMaterias((int)$nuevoUsuarioId, $materiasInscribir, $periodoInscribir);
            $aulasMensaje = " Tu carrera ({$carreraCertificadaNombre}) fue certificada y se asignaron {$aulasInscritas} aulas automáticamente a tu cuenta.";
        }

        $mensajeFinal = $esProfesor 
            ? 'Cuenta de docente creada con éxito. Tu carnet está pendiente de aprobación por la administración.'
            : '¡Cuenta creada y verificada exitosamente!' . $aulasMensaje . ' Ya puedes iniciar sesión.';

        return redirect()->to('/login')->with('mensaje', $mensajeFinal);
    }
}
