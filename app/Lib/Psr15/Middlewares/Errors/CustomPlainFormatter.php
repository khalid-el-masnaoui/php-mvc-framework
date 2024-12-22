<?php

declare(strict_types=1);

namespace App\Lib\Psr15\Middlewares\Errors;

use Throwable;
use Middlewares\ErrorFormatter\AbstractFormatter;

class CustomPlainFormatter extends AbstractFormatter
{
    protected $contentTypes = [
        'text/plain',
    ];

    protected function format(Throwable $error, string $contentType): string
    {
        return sprintf("%s\n%s", $error->getCode(), $error->getMessage());
    }
}
