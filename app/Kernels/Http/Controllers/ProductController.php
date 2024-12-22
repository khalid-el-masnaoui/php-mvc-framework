<?php

declare(strict_types=1);

namespace App\Kernels\Http\Controllers;

use App\Lib\Attributes\Routes\Get;
use App\Lib\Attributes\Middlewares\GetMiddleware;
use App\Lib\Psr15\Middlewares\SetAttributesMiddleware;

#[Get('/product', 'get-products')]
#[GetMiddleware([SetAttributesMiddleware::class], 'get-products')]
class ProductController
{
    public function __invoke(): string
    {
        return 'hello';
    }
}
