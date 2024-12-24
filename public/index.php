<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/vendor/autoload.php';

$app = require_once APP_ROOT . '/bootstrap/bootstrap.php';
$app->run();
