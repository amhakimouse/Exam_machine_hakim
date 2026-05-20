<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

define('BASE_PATH', dirname($_SERVER['SCRIPT_NAME']));

use Core\Router;

$router = new Router();

$router->add('GET',  '/',               'EventController@index');
$router->add('GET',  '/dashboard',      'DashboardController@index');
$router->add('GET',  '/create',         'EventController@create');

$router->add('POST', '/auth/login',      'AuthController@login');
$router->add('GET',  '/auth/logout',     'AuthController@logout');

$router->add('POST', '/api/events',          'ApiController@searchEvents');
$router->add('POST', '/api/events/register', 'ApiController@register');
$router->add('POST', '/api/events/create',   'ApiController@createEvent');
$router->add('GET',  '/api/stats',           'ApiController@stats');

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
