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
});


// Rutas de Roles (protegidas con JWT)
$routes->group('api/roles', ['namespace' => 'App\Controllers\Security', 'filter' => 'auth'], function ($routes) {
    $routes->get('/', 'RoleController::showAll');
    $routes->get('(:num)', 'RoleController::showId/$1');
    $routes->post('/', 'RoleController::create');
    $routes->put('(:num)', 'RoleController::update/$1');
    $routes->delete('(:num)', 'RoleController::delete/$1');
});