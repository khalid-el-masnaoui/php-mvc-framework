<?php

declare(strict_types=1);

return  app()
        ->withRouting(
            APP_ROOT . '/routes/http.php',
        )
        ->boot();
