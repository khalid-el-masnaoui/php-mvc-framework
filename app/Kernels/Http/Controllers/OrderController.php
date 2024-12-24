<?php

declare(strict_types=1);

namespace App\Kernels\Http\Controllers;

use App\Lib\Views\View;
use Middlewares\JsonPayload;
use Middlewares\UrlEncodePayload;
use App\Lib\Attributes\Routes\Get;
use App\Lib\Attributes\Routes\Put;
use App\Lib\Attributes\Routes\Post;
use Psr\Http\Message\ResponseInterface;
use App\Lib\Application\Support\Redirect;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use App\Lib\Attributes\Middlewares\GetMiddleware;
use App\Lib\Attributes\Middlewares\PutMiddleware;
use App\Lib\Attributes\Middlewares\PostMiddleware;
use App\Lib\Psr15\Middlewares\SetAttributesMiddleware;

class OrderController
{
    /** @var HomeController[] */
    protected array $homes;

    public function __construct(protected int $orderId, HomeController ...$homeController)
    {
        $this->homes = $homeController;
    }
}
