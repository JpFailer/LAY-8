<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// --- Rutas Públicas ---
// Landing page principal
$routes->view('/', 'Home/landing');

// Autenticación y Registro
$routes->get('/login', 'Login::index');
$routes->post('/login/autenticar', 'Login::autenticar');
$routes->get('/registro', 'Registro::index');
$routes->post('/registro/procesar', 'Registro::procesar');
$routes->get('/logout', 'Login::salir');

// Módulo de Validación de PDF / Constancia
$routes->get('upload', 'AuthController::index'); 
$routes->post('upload', 'AuthController::procesarConstancia'); 

// Biblioteca estudiantil
$routes->get('biblioteca', 'BibliotecaController::index');
$routes->post('biblioteca/subirAporte', 'BibliotecaController::subirAporte');

// Mapa interactivo y Aulas
$routes->get('mapa', 'MapaController::mostrar_mapa');
$routes->get('mapa/info_aula/(:segment)', 'MapaController::info_aula/$1');
$routes->get('mapa/estado_en_vivo', 'MapaController::estado_aulas_en_vivo');
$routes->get('aula', 'AulasController::importar_json');
$routes->get('htest', 'MapaController::python_horarios');

// --- Rutas Protegidas (Solo usuarios logueados) ---
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    
    // Panel interno / Dashboard
    $routes->get('/dashboard', 'Home::index'); 
    
    $routes->get('/perfil', 'Usuario::perfil');
    $routes->post('/perfil/solicitar-profesor', 'Usuario::solicitarProfesor');
    
    // Rutas exclusivas para Profesores Aprobados
    $routes->group('', ['filter' => 'profesor'], static function ($routes) {
        $routes->get('/crear-sala', 'Sala::crear');
    });
});
