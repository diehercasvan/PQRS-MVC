<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Rutas de Autenticación
$routes->group('api/auth', ['namespace' => 'App\Controllers\Auth'], function ($routes) {
    $routes->post('login', 'AuthController::login');
    $routes->get('me', 'AuthController::me');
    $routes->get('permissions', 'AuthController::permissions');
});


// Rutas de Roles (protegidas con JWT)
$routes->group('api/roles', ['namespace' => 'App\Controllers\Security', 'filter' => 'auth'], function ($routes) {
    $routes->get('/', 'RoleController::showAll');
    $routes->get('(:num)', 'RoleController::showId/$1');
    $routes->post('/', 'RoleController::create');
    $routes->put('(:num)', 'RoleController::update/$1');
    $routes->delete('(:num)', 'RoleController::delete/$1');
});

// Rutas de Permisos (protegidas)
$routes->group('api/permissions', ['namespace' => 'App\Controllers\Security', 'filter' => 'auth'], function ($routes) {
    $routes->get('/', 'PermissionController::showAll');
    $routes->get('(:num)', 'PermissionController::showId/$1');
    $routes->post('/', 'PermissionController::create');
    $routes->put('(:num)', 'PermissionController::update/$1');
    $routes->delete('(:num)', 'PermissionController::delete/$1');
});

// Rutas de Módulos (protegidas)
$routes->group('api/modules', ['namespace' => 'App\Controllers\Security', 'filter' => 'auth'], function ($routes) {
    $routes->get('/', 'ModuleController::showAll');
    $routes->get('(:num)', 'ModuleController::showId/$1');
    $routes->post('/', 'ModuleController::create');
    $routes->put('(:num)', 'ModuleController::update/$1');
    $routes->delete('(:num)', 'ModuleController::delete/$1');
}); 

// Rutas de Usuarios (protegidas)
$routes->group('api/users', ['namespace' => 'App\Controllers\Security', 'filter' => 'auth'], function ($routes) {
    $routes->get('/', 'UserController::showAll');
    $routes->get('(:num)', 'UserController::showId/$1');
    $routes->post('/', 'UserController::create');
    $routes->put('(:num)', 'UserController::update/$1');
    $routes->delete('(:num)', 'UserController::delete/$1');
});