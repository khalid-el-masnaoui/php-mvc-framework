<?php


declare(strict_types=1);

namespace App\Kernels\Http\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ParseBodyMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        if (in_array("application/json", $request->getHeader("content-type"))) {
            $parsedBody = json_decode($request->getBody()->getContents(), true);
            $request = $request->withParsedBody($parsedBody);
        }

        return $handler->handle($request);
    }
}
