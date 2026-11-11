<?php

namespace App\Controllers;

use App\Models\AporteBibliotecaModel;

class BibliotecaController extends BaseController
{
    /**
     * Muestra la lista de aportes y materiales de la biblioteca estudiantil
     */
    public function index()
    {
        $aporteModel = new AporteBibliotecaModel();

        // Filtros opcionales por GET (Categoria, Carrera y Buscador)
        $categoriaSeleccionada = $this->request->getGet('categoria');
        $carreraSeleccionada   = $this->request->getGet('carrera');
        $busqueda              = $this->request->getGet('q');

        if ($categoriaSeleccionada === 'todas' || empty($categoriaSeleccionada)) {
            $categoriaSeleccionada = null;
        }

        if ($carreraSeleccionada === 'todas' || empty($carreraSeleccionada)) {
            $carreraSeleccionada = null;
        }

        // Obtener aportes con los datos del usuario que lo subió y filtros aplicados
        $aportes = $aporteModel->obtenerTodosConUsuario($categoriaSeleccionada, $busqueda, $carreraSeleccionada);

        // Definición de carreras disponibles en la UMC
        $carrerasDefinidas = [
            'todas'   => ['nombre' => 'Todas las Carreras', 'icono' => 'bx-grid-alt', 'color' => '#2563eb'],
            'ING-INF' => ['nombre' => 'Informática', 'escuela' => 'Ingeniería en Informática', 'icono' => 'bx-code-alt', 'color' => '#2563eb'],
            'ING-MAR' => ['nombre' => 'Marítima', 'escuela' => 'Ingeniería Marítima', 'icono' => 'bx-anchor', 'color' => '#0284c7'],
            'ING-AMB' => ['nombre' => 'Ambiental', 'escuela' => 'Ingeniería Ambiental', 'icono' => 'bx-leaf', 'color' => '#10b981'],
            'ADM'     => ['nombre' => 'Administración', 'escuela' => 'Licenciatura en Administración', 'icono' => 'bx-briefcase-alt-2', 'color' => '#f59e0b'],
            'TUR'     => ['nombre' => 'Turismo', 'escuela' => 'Licenciatura en Turismo', 'icono' => 'bx-compass', 'color' => '#ec4899'],
            'GENERAL' => ['nombre' => 'General / Tronco Común', 'escuela' => 'Materias Básicas y Generales', 'icono' => 'bx-book-open', 'color' => '#8b5cf6'],
        ];

        // Conteos para los botones/tabs de categorías
        $conteosBuilder = function($cat = null) use ($carreraSeleccionada) {
            $m = new AporteBibliotecaModel();
            if ($cat) {
                $m->where('categoria', $cat);
            }
            if ($carreraSeleccionada) {
                $m->groupStart()
                  ->where('carrera', $carreraSeleccionada)
                  ->orWhere('carrera', 'GENERAL')
                  ->groupEnd();
            }
            return $m->countAllResults();
        };

        $conteos = [
            'todos'      => $conteosBuilder(),
            'guias'      => $conteosBuilder('guias'),
            'ejercicios' => $conteosBuilder('ejercicios'),
            'resumenes'  => $conteosBuilder('resumenes'),
            'otros'      => $conteosBuilder('otros'),
        ];

        // Conteos por carrera
        $conteosCarreras = [
            'todas'   => (new AporteBibliotecaModel())->countAllResults(),
            'ING-INF' => (new AporteBibliotecaModel())->where('carrera', 'ING-INF')->countAllResults(),
            'ING-MAR' => (new AporteBibliotecaModel())->where('carrera', 'ING-MAR')->countAllResults(),
            'ING-AMB' => (new AporteBibliotecaModel())->where('carrera', 'ING-AMB')->countAllResults(),
            'ADM'     => (new AporteBibliotecaModel())->where('carrera', 'ADM')->countAllResults(),
            'TUR'     => (new AporteBibliotecaModel())->where('carrera', 'TUR')->countAllResults(),
            'GENERAL' => (new AporteBibliotecaModel())->where('carrera', 'GENERAL')->countAllResults(),
        ];

        $datos = [
            'aportes'              => $aportes,
            'categoriaActual'      => $categoriaSeleccionada ?? 'todas',
            'carreraActual'        => $carreraSeleccionada ?? 'todas',
            'carrerasDefinidas'    => $carrerasDefinidas,
            'conteosCarreras'      => $conteosCarreras,
            'busquedaActual'       => $busqueda ?? '',
            'conteos'              => $conteos,
            'totalAportes'         => count($aportes)
        ];

        return view('Biblioteca/feedInicio', $datos);
    }

    /**
     * Muestra el formulario para subir un nuevo material (pantalla aparte)
     */
    public function formularioSubir()
    {
        $carrerasDefinidas = [
            'GENERAL' => 'Tronco Común / Todas las Carreras (General)',
            'ING-INF' => 'Ingeniería en Informática',
            'ING-MAR' => 'Ingeniería Marítima',
            'ING-AMB' => 'Ingeniería Ambiental',
            'ADM'     => 'Licenciatura en Administración',
            'TUR'     => 'Licenciatura en Turismo',
        ];

        $carreraUsuario = session('carrera') ?: 'GENERAL';

        return view('Biblioteca/subirAporte', [
            'carrerasDefinidas' => $carrerasDefinidas,
            'carreraUsuario'    => $carreraUsuario,
        ]);
    }

    /**
     * Procesa la subida del aporte y redirige a la biblioteca
     */
    public function subirAporte()
    {
        $reglas = [
            'aporte' => [
                'label' => 'Archivo de Aporte',
                // si el estudiante intenta hacer trampa subiendo un ejecutable .exe o .bat se friega
            'rules' => 'uploaded[aporte]|max_size[aporte,5120]|ext_in[aporte,pdf,doc,docx,ppt,pptx]',
                'errors' => [
                    'uploaded' => 'Debes seleccionar una guía, resumen o ejercicio para subir.',
                    'max_size' => 'El archivo supera el límite permitido de 5MB.',
                    'ext_in'   => 'Formato no válido. Solo se permiten archivos .pdf, .doc, .docx, .ppt y .pptx.'
                ]
            ],
            'titulo' => 'permit_empty|max_length[150]',
        ];

        if (!$this->validate($reglas)) {
            if ($this->request->isAJAX() || $this->request->is('json')) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'errors'  => $this->validator->getErrors()
                ]);
            }
            return redirect()->back()->withInput()->with('errores', $this->validator->getErrors());
        }

        $archivo = $this->request->getFile('aporte');

        if ($archivo->isValid() && !$archivo->hasMoved()) {
            $nombreOriginal = $archivo->getClientName();
            $extension      = $archivo->getClientExtension();
            $tamanoBytes    = $archivo->getSize();
            // sistema de renombrado aleatorio antidayanas (evita sobreescritura y ejecucion de scripts php)
            $nuevoNombre    = $archivo->getRandomName();

            // sistema de lista blanca de categorias antidayanas (evita path traversal o inyeccion de rutas)
            $categoria = (string) $this->request->getPost('categoria');
            $carpetasPermitidas = ['guias', 'ejercicios', 'resumenes'];
            if (!in_array($categoria, $carpetasPermitidas, true)) {
                $categoria = 'otros';
            }

            // 2. Armamos la ruta dinámica asegurando que el directorio exista
            $rutaDestino = WRITEPATH . 'uploads/biblioteca/' . $categoria;
            if (!is_dir($rutaDestino)) {
                mkdir($rutaDestino, 0777, true);
            }

            // 3. Movemos el archivo a la subcarpeta seleccionada
            $archivo->move($rutaDestino, $nuevoNombre);

            // sanitizacion y asignacion de carrera antidayanas (evita carreras inexistentes)
            $carreraInput = (string) $this->request->getPost('carrera');
            $carrerasValidas = ['ING-INF', 'ING-MAR', 'ING-AMB', 'ADM', 'TUR', 'GENERAL'];
            if (!in_array($carreraInput, $carrerasValidas, true)) {
                $carreraInput = session('carrera') ?: 'GENERAL';
            }

            // 4. Persistencia en Base de Datos para gestión, trazabilidad y control de administración
            $tituloInput = strip_tags(trim((string) $this->request->getPost('titulo')));
            $tituloFinal = !empty($tituloInput) ? $tituloInput : pathinfo($nombreOriginal, PATHINFO_FILENAME);

            $aporteId = null;
            try {
                $aporteModel = new AporteBibliotecaModel();
                $aporteId = $aporteModel->insert([
                    'usuario_id'      => session('usuario_id') ? (int) session('usuario_id') : null,
                    'titulo'          => $tituloFinal,
                    'categoria'       => $categoria,
                    'carrera'         => $carreraInput,
                    'nombre_original' => $nombreOriginal,
                    'nombre_guardado' => $nuevoNombre,
                    'ruta_archivo'    => 'uploads/biblioteca/' . $categoria . '/' . $nuevoNombre,
                    'tamano_bytes'    => $tamanoBytes,
                    'extension'       => strtolower($extension),
                    'fecha_subida'    => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                log_message('error', 'Error al registrar aporte en BD: ' . $e->getMessage());
            }

            if ($this->request->isAJAX() || $this->request->is('json')) {
                return $this->response->setJSON([
                    'success'   => true,
                    'aporte_id' => $aporteId,
                    'mensaje'   => 'Aporte subido exitosamente a la sección de ' . $categoria . '.'
                ]);
            }

            // Redirigir a la biblioteca principal para que el estudiante vea su aporte publicado
            return redirect()->to(base_url('biblioteca'))->with('mensaje', '¡Tu aporte "' . $tituloFinal . '" ha sido publicado con éxito en la biblioteca!');
        }

        $errorMsg = 'Hubo un error técnico al subir el archivo: ' . $archivo->getErrorString();
        if ($this->request->isAJAX() || $this->request->is('json')) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error'   => $errorMsg
            ]);
        }

        return redirect()->back()->with('error', $errorMsg);
    }

    /**
     * Descarga segura de archivos desde el almacenamiento protegido WRITEPATH
     */
    // sistema de descarga segura fuera de la raiz web antidayanas (almacenamiento en WRITEPATH protegido)
    public function descargar(int $id)
    {
        $aporteModel = new AporteBibliotecaModel();
        $aporte = $aporteModel->find($id);

        if (!$aporte) {
            return redirect()->to(base_url('biblioteca'))->with('error', 'El archivo solicitado no existe en los registros.');
        }

        $rutaAbsoluta = WRITEPATH . $aporte['ruta_archivo'];

        if (!file_exists($rutaAbsoluta)) {
            return redirect()->to(base_url('biblioteca'))->with('error', 'El archivo físico no se encuentra disponible en el servidor.');
        }

        // por si algun estudiante comico inventa meter comillas o saltos de linea en el archivo
        $nombreLimpio = preg_replace('/[\r\n\t"]+/', '_', basename($aporte['nombre_original'] ?? 'documento.pdf'));
        return $this->response->download($rutaAbsoluta, null)
                              ->setFileName($nombreLimpio);
    }

    /**
     * Visualizador nativo en línea (stream inline sin forzar descarga externa)
     */
    public function ver(int $id)
    {
        $aporteModel = new AporteBibliotecaModel();
        $aporte = $aporteModel->find($id);

        if (!$aporte) {
            return $this->response->setStatusCode(404)->setBody('Archivo no encontrado');
        }

        $rutaAbsoluta = WRITEPATH . $aporte['ruta_archivo'];

        if (!file_exists($rutaAbsoluta)) {
            return $this->response->setStatusCode(404)->setBody('El archivo físico no se encuentra en el servidor');
        }

        // sistema de deteccion real de mime antidayanas (no se fia del content-type del cliente)
        $mime = null;
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($rutaAbsoluta);
        }
        if (empty($mime)) {
            $ext = strtolower(pathinfo($aporte['nombre_original'] ?? $aporte['ruta_archivo'], PATHINFO_EXTENSION));
            $mime = \Config\Mimes::guessTypeFromExtension($ext) ?: 'application/pdf';
        }

        // sanitizacion contra header injection por si el nombre del archivo trae cosas raras
        $nombreLimpio = preg_replace('/[\r\n\t"]+/', '_', basename($aporte['nombre_original'] ?? 'documento.pdf'));
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . addslashes($nombreLimpio) . '"')
            ->setBody(file_get_contents($rutaAbsoluta));
    }
}
