<?php

declare(strict_types=1);

namespace App\Core\Exceptions\Routes;

use Exception;

class RequestHttpMethodUnSupported extends Exception
{
    // @phpstan-ignore property.extraNativeType
    protected string $message = 'The Request Http Method Is Not Supported';
}
