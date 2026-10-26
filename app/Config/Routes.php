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
$routes->get('biblioteca/subir', 'BibliotecaController::formularioSubir');
$routes->post('biblioteca/subirAporte', 'BibliotecaController::subirAporte');
$routes->get('biblioteca/descargar/(:num)', 'BibliotecaController::descargar/$1');

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
    $routes->get('/ajustes', 'Home::ajustes'); 
    
    // Aulas (Vista principal del menú lateral)
    $routes->get('/aulas', 'AulasController::index');
    $routes->post('/aulas/subir-documento', 'AulasController::subirDocumentoHorario');
    $routes->post('/aulas/guardar-bloque-manual', 'AulasController::guardarBloqueManual');
    $routes->post('/aulas/eliminar-bloque/(:num)', 'AulasController::eliminarBloqueManual/$1');
    $routes->post('/aulas/crear-actividad', 'AulasController::crearActividad');
    $routes->post('/aulas/toggle-actividad/(:num)', 'AulasController::toggleActividad/$1');
    $routes->post('/aulas/eliminar-actividad/(:num)', 'AulasController::eliminarActividad/$1');
    
    //Perfil y usuario
    $routes->get('perfil', 'PerfilController::index');
    $routes->post('perfil/subirFoto', 'PerfilController::subirFoto');
    $routes->post('perfil/actualizar', 'PerfilController::actualizar');
    $routes->post('/perfil/solicitar-profesor', 'Usuario::solicitarProfesor');

    // Sistema de Notificaciones para Alumnos
    $routes->get('/api/notificaciones', 'NotificacionController::misNotificaciones');
    $routes->get('/api/notificaciones/conteo', 'NotificacionController::conteoNoLeidas');
    $routes->post('/api/notificaciones/marcar-leida/(:num)', 'NotificacionController::marcarLeida/$1');
    
    // Rutas exclusivas para Profesores Aprobados (Filtro Profesor)
    $routes->group('', ['filter' => 'profesor'], static function ($routes) {
        $routes->get('/crear-sala', 'Sala::crear');
        
        // Panel de profesor & estados rápidos
        $routes->get('/api/profesor/clases', 'ProfesorController::misClases');
        $routes->post('/api/profesor/actualizar-estado', 'ProfesorController::actualizarEstadoClase');
        $routes->post('/api/profesor/aviso-tarea', 'ProfesorController::enviarAvisoTarea');
    });

    // Rutas exclusivas para el Administrador (Filtro Admin)
    $routes->group('', ['filter' => 'admin'], static function ($routes) {
        // Vista Principal del Panel de Administración
        $routes->get('/admin', 'AdminController::index');

        // Dashboard y Métricas
        $routes->post('/api/admin/horario/actualizar', 'AdminController::actualizarHorarioPdf');
        $routes->get('/api/admin/metricas', 'AdminController::metricas');

        // Gestión de Profesores (Aprobación, Denegación y Revocación)
        $routes->get('/api/admin/solicitudes-profesores', 'AdminController::solicitudesProfesores');
        $routes->post('/api/admin/aprobar-profesor/(:num)', 'AdminController::aprobarProfesor/$1');
        $routes->post('/api/admin/denegar-profesor/(:num)', 'AdminController::denegarProfesor/$1');
        $routes->post('/api/admin/rechazar-profesor/(:num)', 'AdminController::denegarProfesor/$1'); // compatibilidad
        $routes->post('/api/admin/revocar-profesor/(:num)', 'AdminController::revocarProfesor/$1');

        // Gestión de Usuarios (Listar, Detalle, Banear, Desbanear, Eliminar y Roles)
        $routes->get('/api/admin/usuarios', 'AdminController::listarUsuarios');
        $routes->get('/api/admin/usuarios/(:num)', 'AdminController::detalleUsuario/$1');
        $routes->post('/api/admin/usuarios/banear/(:num)', 'AdminController::banearUsuario/$1');
        $routes->post('/api/admin/usuarios/desbanear/(:num)', 'AdminController::desbanearUsuario/$1');
        $routes->post('/api/admin/usuarios/eliminar/(:num)', 'AdminController::eliminarUsuario/$1');
        $routes->delete('/api/admin/usuarios/(:num)', 'AdminController::eliminarUsuario/$1');
        $routes->post('/api/admin/usuarios/cambiar-rol/(:num)', 'AdminController::cambiarRolUsuario/$1');

        // Gestión de la Biblioteca (Listar, Borrar Aportes y Archivos Físicos Huérfanos)
        $routes->get('/api/admin/biblioteca/aportes', 'AdminController::listarAportesBiblioteca');
        $routes->post('/api/admin/biblioteca/eliminar/(:num)', 'AdminController::eliminarAporteBiblioteca/$1');
        $routes->delete('/api/admin/biblioteca/(:num)', 'AdminController::eliminarAporteBiblioteca/$1');
        $routes->post('/api/admin/biblioteca/eliminar-archivo-huerfano', 'AdminController::eliminarArchivoHuerfano');

        // Funciones Esenciales Adicionales
        $routes->get('/api/admin/auditoria', 'AdminController::historialAuditoria');
        $routes->post('/api/admin/aulas/toggle-estado/(:num)', 'AdminController::toggleEstadoAula/$1');
        $routes->post('/api/admin/comunicado-global', 'AdminController::emitirComunicadoGlobal');
    });
});