<?php

declare(strict_types=1);

namespace App\Core\Attributes\Middlewares;

use App\Core\Enums\HttpMethod;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Middleware
{
    /** @param class-string[] $middlewares */
    public function __construct(public string $routePath, public HttpMethod $method = HttpMethod::Get, public array $middlewares = [])
    {
    }
}
