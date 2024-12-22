<?php

declare(strict_types=1);

namespace App\Lib\Attributes\Middlewares;

use Attribute;
use App\Lib\Enums\HttpMethod;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class GetMiddleware extends Middleware
{
    public function __construct(array $middlewares, ?string $routeName = null, ?string $routePath = null)
    {
        parent::__construct($middlewares, $routeName, $routePath, HttpMethod::GET);
    }
}
