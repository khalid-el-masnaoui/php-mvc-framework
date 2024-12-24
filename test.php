<?php

use App\Lib\Router\Router;
use Psr\Container\ContainerInterface;
use App\Lib\Application\Container\Container;
use App\Lib\Application\Container\AppContainer;
use App\Kernels\Http\Controllers\HomeController;
use App\Kernels\Http\Controllers\OrderController;

require_once __DIR__ . '/vendor/autoload.php';

// $concreteDi = $di->bind(ContainerInterface::class, Container::class);
// $concreteDi = $di->get(Router::class);

// $di->when(Router::class)
//                 ->needs(ContainerInterface::class)
//                 ->give(Container::class);

// $concreteDi = $di->get(Router::class);

// $di->when(OrderController::class)
//                 ->needs(HomeController::class)
//                 ->give(HomeController::class);

$concreteDi = app()->make(OrderController::class, ['orderId' => 1]);

dd($concreteDi);
