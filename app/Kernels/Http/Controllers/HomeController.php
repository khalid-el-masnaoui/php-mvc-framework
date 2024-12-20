<?php

declare(strict_types=1);

namespace App\Kernels\Http\Controllers;

use Middlewares\JsonPayload;
use App\Core\Services\Views\View;
use Middlewares\UrlEncodePayload;
use App\Core\Attributes\Routes\Get;
use App\Core\Attributes\Routes\Put;
use App\Core\Attributes\Routes\Post;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use App\Core\Attributes\Middlewares\GetMiddleware;
use App\Core\Attributes\Middlewares\PutMiddleware;
use App\Core\Attributes\Middlewares\PostMiddleware;
use App\Kernels\Http\Middlewares\SetAttributesMiddleware;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    // #[Get('/')]
    #[GetMiddleware('/', [new SetAttributesMiddleware()])]
    public function index(): ResponseInterface
    {
        // throw new \Exception("Error Processing Request", 501);
        return new HtmlResponse((string) View::make('index'), 200, ['special-header' => 'special-header-value']);

        // return (string) View::make('index');
        // return new RedirectResponse('/user/login');
        // return ["hello"];
    }

    #[Post('/store')]
    #[PostMiddleware('/store', [new JsonPayload()])]
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        // throw new \Exception("Error Processing Request", 501);
        return new JsonResponse($request->getParsedBody(), 200, ['special-header' => 'special-header-value']);
    }

    #[Put('/update')]
    #[PutMiddleware('/update', [new UrlEncodePayload()])]
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse($request->getParsedBody(), 200, ['special-header' => 'special-header-value']);
    }
}
