<?php

declare(strict_types=1);

namespace App\Kernels\Http\Middlewares\Errors;

use Throwable;
use Middlewares\ErrorFormatter\AbstractFormatter;

class CustomXmlFormatter extends AbstractFormatter
{
    protected $contentTypes = [
        'text/xml', 'application/xml', 'application/x-xml',
    ];

    protected function format(Throwable $error, string $contentType): string
    {
        $code    = $error->getCode();
        $message = $error->getMessage();

        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<error>
    <code>$code</code>
    <message>$message</message>
</error>
XML;
    }
}
