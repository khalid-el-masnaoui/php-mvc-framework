<?php

declare(strict_types=1);

namespace App\Core\Enums;

use App\Core\Enums\EnumToArrayTrait;

enum HttpMethod: string
{
    use EnumToArrayTrait;

    case Get = 'GET';

    case Post = 'POST';

    case Put = 'PUT';

    case Delete = 'DELETE';
}
