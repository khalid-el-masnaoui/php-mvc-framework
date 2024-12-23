<?php

namespace App\Lib\Application\Traits;

trait Singleton
{
    private static ?self $instance = null;

    public static function singleton(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }
}
