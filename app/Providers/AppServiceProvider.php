<?php

declare(strict_types=1);

namespace App\Providers;

use App\Lib\Application\Application;
use App\Lib\Application\Container\ServiceProvider;
use App\Lib\Application\Support\ServiceProviderInterface;

class AppServiceProvider extends ServiceProvider implements ServiceProviderInterface
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services
     * and if you want to do something before handling the request.
     */
    public function boot(): void
    {
        //
    }
}
