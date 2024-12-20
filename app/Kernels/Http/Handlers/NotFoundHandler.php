<?php

declare(strict_types=1);

namespace App\Kernels\Http\Handlers;

use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NotFoundHandler implements RequestHandlerInterface
{
    /** @return ResponseInterface */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new TextResponse("Not Found", 404);
    }
}
