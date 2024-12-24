<?php

declare(strict_types=1);

namespace App\Lib\Application\Container;

use App\Lib\Application\Support\ServiceProviderInterface;

class ServiceProvider
{
    public function __construct(public AppContainerInterface $app)
    {
        //
    }
}
