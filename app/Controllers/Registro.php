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
            'repetir_contrasena' => 'matches[contrasena]'
        ];

        if (!$this->validate($reglas)) {
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
            $archivoInscripcion = $this->request->getFile('planilla_inscripcion');
            if (!$archivoInscripcion || !$archivoInscripcion->isValid() || $archivoInscripcion->hasMoved()) {
                return redirect()->back()->withInput()->with('error', 'Por motivos de seguridad, debes adjuntar tu Registro de Inscripción oficial emitido por la UMC (formato PDF).');
            }

            if ($archivoInscripcion->getClientMimeType() !== 'application/pdf') {
                return redirect()->back()->withInput()->with('error', 'El comprobante debe ser exclusivamente un archivo PDF original emitido por el sistema campus.umc.edu.ve.');
            }

            $verificador = new VerificadorInscripcionService();
            $analisis = $verificador->verificar($archivoInscripcion->getTempName(), $carreraSeleccionada);

            if (!$analisis['es_valido']) {
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
