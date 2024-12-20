<?php

declare(strict_types=1);

namespace App\Kernels\Http\Middlewares\Errors;

use Throwable;
use Middlewares\ErrorFormatter\AbstractFormatter;

class CustomHtmlErrorFormatter extends AbstractFormatter
{
    protected $contentTypes = [
        'text/html',
    ];

    protected function format(Throwable $error, string $contentType): string
    {
        $code = $error->getCode();
        $message = $error->getMessage();

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>$code</title>
    <style>html{font-family: sans-serif;}</style>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1>$code</h1>
    <p>$message</p>
</body>
</html>
HTML;
    }
}
