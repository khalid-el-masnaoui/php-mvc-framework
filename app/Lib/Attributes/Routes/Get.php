<?php

declare(strict_types=1);

namespace App\Lib\Attributes\Routes;

use Attribute;
use App\Lib\Enums\HttpMethod;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Get extends Route
{
    public function __construct(string $routePath, ?string $name = null, array $middlewares = [])
    {
        parent::__construct($routePath, HttpMethod::GET, $name, $middlewares);
    }
}
