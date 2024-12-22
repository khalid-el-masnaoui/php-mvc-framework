<?php

declare(strict_types=1);

namespace App\Lib\Enums;

use App\Lib\Enums\EnumToArrayTrait;

enum HttpMethod: string
{
    use EnumToArrayTrait;

    case GET = 'GET';

    case POST = 'POST';

    case PUT = 'PUT';

    case DELETE = 'DELETE';
}
