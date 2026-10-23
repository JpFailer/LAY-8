<?php

namespace App\Controllers;

use App\Models\AporteBibliotecaModel;

class BibliotecaController extends BaseController
{
    public function index()
    {
        return view('Biblioteca/subirAporte');
    }

    public function subirAporte()
    {
        $reglas = [
            'aporte' => [
                'label' => 'Archivo de Aporte',
                'rules' => 'uploaded[aporte]|max_size[aporte,5120]|ext_in[aporte,pdf,doc,docx,ppt]',
                'errors' => [
                    'uploaded' => 'Debes seleccionar una guía, resumen o ejercicio para subir.',
                    'max_size' => 'El archivo supera el límite permitido de 5MB.',
                    'ext_in'   => 'Formato no válido. Solo se permiten archivos .pdf, .doc, .docx y .ppt.'
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

            return redirect()->back()->with('mensaje', 'El aporte ha sido subido exitosamente a la sección de ' . $categoria . '.');
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
}
