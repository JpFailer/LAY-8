<?php

namespace App\Controllers;

use Smalot\PdfParser\Parser;

class AuthController extends BaseController
{
    // Muestra la vista del formulario para subir el PDF
    public function index()
    {
        return view('Registro/validacionConstancia');
    }

    // Procesa el PDF cuando el usuario le da al botón "Enviar"
    public function procesarConstancia()
    {
        $archivo = $this->request->getFile('constancia_pdf');

        if (!$archivo || !$archivo->isValid() || $archivo->getExtension() !== 'pdf') {
            return redirect()->back()->with('error', 'Por favor, sube un archivo PDF válido.');
        }

        $carpetaUploads = WRITEPATH . 'uploads';
        if (!is_dir($carpetaUploads)) {
            mkdir($carpetaUploads, 0777, true);
        }

        $nuevoNombre = $archivo->getRandomName();
        $archivo->move($carpetaUploads, $nuevoNombre);
        $rutaCompleta = $carpetaUploads . '/' . $nuevoNombre;

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($rutaCompleta);
            $texto = $pdf->getText();
            $textoNormalizado = mb_strtolower($texto, 'UTF-8');

            // VALIDACIÓN 1: ¿Es de la universidad UMC?
            $esValido = preg_match('/universidad.*?caribe/is', $textoNormalizado);

            if (!$esValido) {
                if (file_exists($rutaCompleta)) {
                    unlink($rutaCompleta);
                }
                return redirect()->back()->with('error', 'Este documento no parece ser una constancia válida de la UMC.');
            }

            // VALIDACIÓN 2: Extraer la cédula
            if (preg_match('/[ve]-?\s*?(\d{7,8})/i', $texto, $coincidencias)) {
                $cedulaEncontrada = $coincidencias[1];
            } else {
                if (file_exists($rutaCompleta)) {
                    unlink($rutaCompleta);
                }
                return redirect()->back()->with('error', 'No se pudo detectar un número de cédula en el documento.');
            }

            // Borrado del PDF temporal
            if (file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }

            // Redirigimos al registro enviando el mensaje de éxito
            return redirect()->to('/registro')->with('exito', '¡Constancia Válida! Cédula detectada: V-' . $cedulaEncontrada);

        } catch (\Exception $e) {
            if (file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }
            return redirect()->back()->with('error', 'Hubo un error al leer el PDF. Verifica que no tenga contraseña.');
        }
    }

    public function pantallaRegistro()
    {
        return redirect()->to('/registro'); 
    }
}
