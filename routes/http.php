<?php

use App\Kernels\Kernel;
use App\Lib\Router\Router;
use Middlewares\JsonPayload;

/*
|--------------------------------------------------------------------------
| Http Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the HttpKernel
|
*/

$container = Kernel::getContainer();
$router    = $container->get(Router::class);

$router->setDefaultNamespace('App\Kernels\Http\Controllers');

/*
|--------------------------------------------------------------------------
| Base Routes
|--------------------------------------------------------------------------
|
|
*/

$router->get('/', ['HomeController', 'index']);
$router->post('/store', ['HomeController', 'store'], [new JsonPayload()]);
