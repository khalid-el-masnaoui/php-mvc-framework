<?php

declare(strict_types=1);

namespace App\Lib\Attributes\Routes;

use App\Lib\Enums\HttpMethod;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Route
{
    /** @param class-string[] $middlewares */
    public function __construct(public string $routePath, public HttpMethod $method = HttpMethod::GET, public ?string $name = null, public array $middlewares = [])
    {
    }
}
