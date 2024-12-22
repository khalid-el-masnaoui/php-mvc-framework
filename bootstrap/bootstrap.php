<?php

declare(strict_types=1);

use App\Lib\Application\Application;

return  Application::singleton()
        ->withRouting(
            __DIR__ . '/../routes/http.php',
        )
        ->boot();
