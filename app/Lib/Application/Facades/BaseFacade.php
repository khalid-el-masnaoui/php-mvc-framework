<?php

declare(strict_types=1);

namespace App\Lib\Application\Facades;

abstract class BaseFacade
{
    /**
     * Get the registered name of the component.
     */
    abstract protected static function getFacadeAccessor(): string;

    /**
     * Handle dynamic, static calls to the object.
     * @param mixed[] $arguments
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        return app()->make(static::getFacadeAccessor())->$name(...$arguments);
    }
}
