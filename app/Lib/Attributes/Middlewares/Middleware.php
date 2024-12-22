<?php

declare(strict_types=1);

namespace App\Lib\Attributes\Middlewares;

use App\Lib\Enums\HttpMethod;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Middleware
{
    /** @param class-string[] $middlewares */
    public function __construct(public array $middlewares, public ?string $routeName = null, public ?string $routePath = null, public ?HttpMethod $method = null)
    {
    }
}
