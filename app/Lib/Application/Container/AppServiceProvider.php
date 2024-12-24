<?php

declare(strict_types=1);

namespace App\Lib\Application\Container;

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
        $this->app->bind('App', function (): Application {
            return Application::getInstance();
        });

        $this->app->bind(AppContainerInterface::class, function () {
            return $this;
        });

        // $this->app->bind('Request', function () {
        //     return Request::singleton();
        // });

        // $this->app->bind('Session', function () {
        //     return Session::singleton();
        // });

        // $this->app->bind('Flush', function () {
        //     return FlushMessage::singleton();
        // });

        // $this->app->bind('DB', function () {
        //     return BaseDatabase::singleton();
        // });

        // $this->app->bind('Auth', function () {
        //     return new Auth();
        // });

        // $this->app->bind('Router', function () {
        //     $router = Router::singleton();

        //     $router->setDependency(Request::singleton());

        //     return $router;
        // });

        // $this->app->bind('View', function () {
        //     return View::singleton();
        // });
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
