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

        // Filtros opcionales por GET
        $categoriaSeleccionada = $this->request->getGet('categoria');
        $busqueda = $this->request->getGet('q');

        if ($categoriaSeleccionada === 'todas' || empty($categoriaSeleccionada)) {
            $categoriaSeleccionada = null;
        }

        // Obtener aportes con los datos del usuario que lo subió
        $aportes = $aporteModel->obtenerTodosConUsuario($categoriaSeleccionada, $busqueda);

        // Conteos para los botones/tabs de categorías
        $conteos = [
            'todos'      => (new AporteBibliotecaModel())->countAllResults(),
            'guias'      => (new AporteBibliotecaModel())->where('categoria', 'guias')->countAllResults(),
            'ejercicios' => (new AporteBibliotecaModel())->where('categoria', 'ejercicios')->countAllResults(),
            'resumenes'  => (new AporteBibliotecaModel())->where('categoria', 'resumenes')->countAllResults(),
            'otros'      => (new AporteBibliotecaModel())->where('categoria', 'otros')->countAllResults(),
        ];

        $datos = [
            'aportes'              => $aportes,
            'categoriaActual'      => $categoriaSeleccionada ?? 'todas',
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
        return view('Biblioteca/subirAporte');
    }

    /**
     * Procesa la subida del aporte y redirige a la biblioteca
     */
    public function subirAporte()
    {
        $reglas = [
            'aporte' => [
                'label' => 'Archivo de Aporte',
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
            $nuevoNombre    = $archivo->getRandomName();

            // 1. Capturamos y sanitizamos la categoría del formulario
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

        // Descarga segura de CodeIgniter 4 manteniendo el nombre original del archivo
        return $this->response->download($rutaAbsoluta, null)
                              ->setFileName($aporte['nombre_original']);
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

        $mime = mime_content_type($rutaAbsoluta) ?: 'application/pdf';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . addslashes($aporte['nombre_original']) . '"')
            ->setBody(file_get_contents($rutaAbsoluta));
    }
}
