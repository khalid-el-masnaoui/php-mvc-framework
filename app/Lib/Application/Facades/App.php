<?php

declare(strict_types=1);

namespace App\Lib\Application\Facades;

use App\Lib\Application\Facades\BaseFacade;

class App extends BaseFacade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'App';
    }
}
