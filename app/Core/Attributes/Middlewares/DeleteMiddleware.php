<?php

declare(strict_types=1);

namespace App\Core\Attributes\Middlewares;

use Attribute;
use App\Core\Enums\HttpMethod;
use Psr\Http\Server\MiddlewareInterface;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class DeleteMiddleware extends Middleware
{
    public function __construct(string $routePath, public array $middlewares = [])
    {
        parent::__construct($routePath, HttpMethod::Delete, $middlewares);
    }
}
