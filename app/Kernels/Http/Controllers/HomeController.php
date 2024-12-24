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

class HomeController
{
    #[Get('/')]
    #[GetMiddleware([SetAttributesMiddleware::class], null, '/')]
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // $redirect = new Redirect();
        return back();
        // throw new \Exception("Error Processing Request", 501);
        // return new HtmlResponse((string) View::make('index'), 200, ['special-header' => 'special-header-value']);

        // return (string) View::make('index');
        // return new RedirectResponse('/user/login');
        // return ["hello"];
    }

    /** @return array<string,mixed> */
    public function example(ServerRequestInterface $request, mixed $field1 = null, mixed $field2 = null): array
    {
        return ['field1' => $field1, 'field2' => $field2];
    }

    #[Post('/store')]
    #[PostMiddleware([JsonPayload::class], null, '/store')]
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        // throw new \Exception("Error Processing Request", 501);
        return new JsonResponse($request->getParsedBody(), 200, ['special-header' => 'special-header-value']);
    }

    #[Put('/update')]
    #[PutMiddleware([UrlEncodePayload::class], null, '/update')]
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse($request->getParsedBody(), 200, ['special-header' => 'special-header-value']);
    }
}
