<?php

declare(strict_types=1);

namespace App\Lib\Psr15\Handlers;

use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NotFoundHandler implements RequestHandlerInterface
{
    /** @return ResponseInterface */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new TextResponse('Not Found', 404);
    }
}
