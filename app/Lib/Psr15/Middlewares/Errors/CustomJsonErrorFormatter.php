<?php

declare(strict_types=1);

namespace App\Lib\Psr15\Middlewares\Errors;

use Throwable;
use Middlewares\ErrorFormatter\AbstractFormatter;

class CustomJsonErrorFormatter extends AbstractFormatter
{
    protected $contentTypes = [
        'application/json',
    ];

    protected function format(Throwable $error, string $contentType): string
    {
        $json = [
            'status'  => 0,
            'code'    => $error->getCode(),
            'message' => $error->getMessage(),
        ];

        return (string) json_encode($json);
    }
}
