<?php

use App\Lib\DiContainer\Container;
use App\Lib\DiContainer\DiContainer;
use App\Lib\Router\Router;
use Psr\Container\ContainerInterface;

require_once __DIR__ . '/vendor/autoload.php';

$di = new DiContainer();

$di->set('router', Router::class);
$concreteDi = $di->get('router');

dd($concreteDi);
