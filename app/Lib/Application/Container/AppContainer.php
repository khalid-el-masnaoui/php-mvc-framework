<?php

namespace App\Lib\Application\Container;

use App\Lib\Application\Application;

class AppContainer
{
    /**
     * The container's bindings.
     * @var mixed[]
    */
    private static array $bindings = [];

    /** This method helps to bind the container */
    public function bind(string $name, callable $resolver): void
    {
        static::$bindings[$name] = $resolver;
    }

    public function make(string $name): mixed
    {
        if (!isset(static::$bindings[$name])) {
            throw new \Exception($name . 'application binding not found.', 500);
        } elseif (is_callable(static::$bindings[$name])) {
            return static::$bindings[$name]();
        } else {
            return static::$bindings[$name];
        }
    }

    /**
     * Get all container list.
     */
    public function getBindings(): mixed
    {
        return static::$bindings;
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $this->app->bind('App', function () {
        //     return Application::singleton();
        // });

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
        // });
    }
}
