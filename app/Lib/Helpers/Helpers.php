<?php

declare(strict_types=1);

use App\Lib\Application\Application;

if (!function_exists('app')) {
    /**
     * Get Application instance.
     */
    function app(): Application
    {
        return Application::singleton();
    }
}
