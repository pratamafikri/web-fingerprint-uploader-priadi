<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');



/**
 * API Routes
 */

$routes->group('api', static function ($route) {
    // Group Routes
    $route->get('group', 'ApiController::getGroups', ['as' => "api_get_group"]);
    $route->post('group/save', 'ApiController::saveGroup', ['as' => "api_save_group"]);
    $route->post('group/delete', 'ApiController::deleteGroup', ['as' => "api_delete_group"]);

    // Participant Routes
    $route->get('participant', 'ApiController::getParticipantByGroup', ['as' => 'api_get_participant']);
    $route->get('participant/(:num)', 'ApiController::getParticipantById/$1', ['as' => 'api_get_participant_by_id']);
    $route->post('participant/save', 'ApiController::saveParticipant', ['as' => 'api_save_participant']);
    $route->post('participant/delete', 'ApiController::deleteParticipant', ['as' => 'api_delete_participant']);

    $route->post('participant/update_finger', 'ApiController::saveFingerParticipant', ['as' => 'api_update_finger_participant']);
});


/**
 * Page Routes
 */

// Group Routes
$routes->group('group', static function ($route) {
    $route->get('/', 'GroupController::index', ['as' => 'group']);
});

// Participant Routes
$routes->group('participant', static function ($route) {
    $route->get('/', 'ParticipantController::index', ['as' => 'participant']);
    $route->get('(:num)', 'ParticipantController::view/$1', ['as'=> 'participant_view_by_id']);
});
