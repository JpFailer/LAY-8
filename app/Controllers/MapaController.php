<?php 
namespace App\Controllers;

use CodeIgniter\Controller;

class MapaController extends Controller {

public function python_horarios(){
    
    $carpeta_scripts = ROOTPATH . 'scripts_python';
    $comando = 'cd "' . $carpeta_scripts . '" && "venv\Scripts\python.exe" leer_horario.py 2>&1';
    
$json_crudo = shell_exec($comando);

    // 1. Buscamos en qué posición exacta empieza el '[' y termina el ']'
    $inicio = strpos($json_crudo, '[');
    $fin = strrpos($json_crudo, ']');

    // 2. Si encontramos ambos corchetes...
    if ($inicio !== false && $fin !== false) {
        
        // Recortamos exactamente ese pedazo (ni un espacio más, ni un espacio menos)
        $json_limpio = substr($json_crudo, $inicio, $fin - $inicio + 1);
        
        // 3. Ahora sí, decodificamos nuestra versión limpia
        $json_decodificado = json_decode($json_limpio, true);

        // Volvemos a pasar el detector
        if ($json_decodificado === null) {
            dd("Sigue fallando. Motivo: " . json_last_error_msg());
        } else {
            dd($json_decodificado);
        }
    } else {
        dd("Error: El script de Python no devolvió ningún JSON válido.");
    }
}
}