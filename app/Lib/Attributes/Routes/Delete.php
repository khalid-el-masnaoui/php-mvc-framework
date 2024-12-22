<?php

declare(strict_types=1);

namespace App\Lib\Attributes\Routes;

use App\Lib\Enums\HttpMethod;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Delete extends Route
{
    public function __construct(string $routePath, ?string $name = null, array $middlewares = [])
    {
        parent::__construct($routePath, HttpMethod::DELETE, $name, $middlewares);
    }
}
