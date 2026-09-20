<?php
namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $session = session();
        
        echo "<h1>¡Bienvenido a la página principal de LAY-8 MAP!</h1>";
        echo "<p>Hola, <strong>" . $session->get('primer_nombre') . " " . $session->get('apellidos') . "</strong>.</p>";
        echo "<p>Tu rol actual en el sistema es: <strong>" . $session->get('rol') . "</strong>.</p>";
        
        echo "<hr>";
        
        // Aquí arreglé las comillas para que los botones se vean bien
        echo '<a href="' . base_url('perfil') . '">Ir a Mi Perfil</a> | ';
        echo '<a href="' . base_url('logout') . '">Cerrar Sesión</a>';
    }
}