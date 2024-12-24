<?php

use App\Lib\Router\Router;

/*
|--------------------------------------------------------------------------
| Http Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the HttpKernel
|
*/

$router    = app()->get(Router::class);

$router->setDefaultNamespace('App\Kernels\Http\Controllers');

/*
|--------------------------------------------------------------------------
| Base Routes
|--------------------------------------------------------------------------
|
|
*/

$router->get('/example/{field1}/shelf/{field2?}', ['HomeController', 'example']);
