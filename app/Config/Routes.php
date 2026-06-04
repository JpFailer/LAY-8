<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Rutas automáticas de Shield (Login/Logout)
service('auth')->routes($routes);

// Módulo de Validación de PDF
$routes->get('upload', 'AuthController::index'); 
$routes->post('upload', 'AuthController::procesarConstancia'); 

// Ruta para cuando la constancia es válida
$routes->get('registro', 'AuthController::pantallaRegistro');
$routes->get('horario', 'MapaController::python_horarios');