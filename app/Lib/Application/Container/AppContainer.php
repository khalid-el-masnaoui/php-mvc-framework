<?php

namespace App\Lib\Application\Container;

use App\Lib\Application\Application;

class AppContainer
{
    /** @var mixed[] */
    private static array $bindings = [];

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

    public function getBindings(): mixed
    {
        return static::$bindings;
    }
}
