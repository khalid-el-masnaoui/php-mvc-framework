<?php

declare(strict_types=1);

namespace App\Kernels\Http\Middlewares\Errors;

use Throwable;
use Middlewares\ErrorFormatter\AbstractFormatter;

class CustomPlainFormatter extends AbstractFormatter
{
    protected $contentTypes = [
        'text/plain',
    ];

    protected function format(Throwable $error, string $contentType): string
    {
        return sprintf("%s %s\n%s", $error->getCode(), $error->getMessage());
    }
}
