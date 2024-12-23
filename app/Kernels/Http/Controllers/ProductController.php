<?php

declare(strict_types=1);

namespace App\Kernels\Http\Controllers;

use App\Lib\Views\View;
use App\Lib\Attributes\Routes\Get;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use App\Lib\Attributes\Middlewares\GetMiddleware;
use App\Lib\Psr15\Middlewares\SetAttributesMiddleware;

#[Get('/product', 'get-products')]
#[GetMiddleware([SetAttributesMiddleware::class], 'get-products')]
class ProductController
{
    public function __invoke(): ResponseInterface
    {
        // return 'hello';
        return new HtmlResponse((string) View::make('product'), 200, ['special-header' => 'special-header-value']);
    }
}
