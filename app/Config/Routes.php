<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Rutas automáticas de Shield (Login/Logout)
// service('auth')->routes($routes);

// Módulo de Validación de PDF
$routes->get('upload', 'AuthController::index'); 
$routes->post('upload', 'AuthController::procesarConstancia'); 

// Ruta para cuando la constancia es válida (Comentada para evitar errores viejos)
// $routes->get('registro', 'AuthController::pantallaRegistro');

// Ruta para entrar a ver el formulario
$routes->get('biblioteca', 'BibliotecaController::index');

// Ruta que procesa el formulario al presionar el botón "Subir Aporte"
$routes->post('biblioteca/subirAporte', 'BibliotecaController::subirAporte');
$routes->get('htest', 'MapaController::python_horarios');
$routes->get('aula', 'AulasController::importar_json');
$routes->get('mapa', 'MapaController::mostrar_mapa');
$routes->get('mapa/info_aula/(:segment)', 'MapaController::info_aula/$1');
$routes->get('mapa/estado_en_vivo', 'MapaController::estado_aulas_en_vivo');

// --- Rutas Públicas (Nuevas) ---
// La landing page de tu compañera es ahora la puerta de entrada (localhost/LAY-8/public/)
$routes->view('/', 'Home/landing');

$routes->post('/login/autenticar', 'Login::autenticar');
$routes->get('/registro', 'Registro::index');
$routes->post('/registro/procesar', 'Registro::procesar');
$routes->get('/logout', 'Login::salir');


// --- Rutas Protegidas (Solo usuarios logueados) ---
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    
    // El sistema interno (donde dice "Hola, tu rol es...") ahora vive en /dashboard
    $routes->get('/dashboard', 'Home::index'); 
    
    $routes->get('/perfil', 'Usuario::perfil');
    $routes->post('/perfil/solicitar-profesor', 'Usuario::solicitarProfesor');
    
    // Rutas exclusivas para Profesores Aprobados
    $routes->group('', ['filter' => 'profesor'], static function ($routes) {
        $routes->get('/crear-sala', 'Sala::crear'); // Asegúrate de crear este controlador luego
    });
});