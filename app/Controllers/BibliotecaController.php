<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class BibliotecaController extends Controller
{
    public function index()
    {
        return view('subir_aporte');
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
            ]
        ];

        if (!$this->validate($reglas)) {
            return redirect()->back()->withInput()->with('errores', $this->validator->getErrors());
        }

        $archivo = $this->request->getFile('aporte');

        if ($archivo->isValid() && !$archivo->hasMoved()) {
            
            $nuevoNombre = $archivo->getRandomName();

            // --- AQUÍ ENTRA TU LÓGICA DE CATEGORÍAS ---
            // 1. Capturamos la categoría del formulario
            $categoria = $this->request->getPost('categoria');

            // 2. Filtramos para evitar inyecciones de rutas
            $carpetasPermitidas = ['guias', 'ejercicios', 'resumenes'];
            if (!in_array($categoria, $carpetasPermitidas)) {
                $categoria = 'otros'; // Carpeta de seguridad
            }

            // 3. Armamos la ruta dinámica
            $rutaDestino = WRITEPATH . 'uploads/biblioteca/' . $categoria;
            // ------------------------------------------

            // Movemos el archivo a la subcarpeta seleccionada
            $archivo->move($rutaDestino, $nuevoNombre);

            // Mensaje dinámico para que el usuario sepa dónde se guardó
            return redirect()->back()->with('mensaje', 'El aporte ha sido subido exitosamente a la sección de ' . $categoria . '.');
        }

        return redirect()->back()->with('error', 'Hubo un error técnico al subir el archivo: ' . $archivo->getErrorString());
    }
}