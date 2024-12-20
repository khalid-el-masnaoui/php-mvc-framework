<?php

declare(strict_types=1);

use App\App;
use Laminas\Diactoros\ServerRequestFactory;

$request = ServerRequestFactory::fromGlobals();

return (new App(
    $request
))->boot();
