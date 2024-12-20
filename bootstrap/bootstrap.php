<?php

declare(strict_types=1);

use App\Application;
use Laminas\Diactoros\ServerRequestFactory;

$request = ServerRequestFactory::fromGlobals();

return (new Application(
    $request
))->boot();
