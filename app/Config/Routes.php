<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->options('(:any)', 'PlannedInspectionsController::options');
// Главная страница
$routes->get('/', 'Home::index');
// API
$routes->group('api', ['namespace' => 'App\Controllers'], function($routes) {
    // Плановые проверки
    $routes->get('inspections', 'PlannedInspectionsController::index');
    $routes->get('inspections/export', 'PlannedInspectionsController::export');
    $routes->post('inspections/import', 'PlannedInspectionsController::import');
    $routes->get('inspections/download-template', 'PlannedInspectionsController::downloadTemplate');
    $routes->post('inspections', 'PlannedInspectionsController::create');
    $routes->get('inspections/(:num)', 'PlannedInspectionsController::show/$1');
    $routes->put('inspections/(:num)', 'PlannedInspectionsController::update/$1');
    $routes->delete('inspections/(:num)', 'PlannedInspectionsController::delete/$1');
    $routes->delete('inspections/batch', 'PlannedInspectionsController::delete');
    $routes->get('controlling-authorities', 'PlannedInspectionsController::getAuthorities');

    // СМП
    $routes->get('smp/search', 'SmpController::search');
    $routes->get('smp/dropdown', 'SmpController::dropdown');
    $routes->post('smp', 'SmpController::create');
});

