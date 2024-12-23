<?php

declare(strict_types=1);

namespace App\Lib\Application\Support;

use App\Lib\Utils\Helpers;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\Response\EmptyResponse;
use App\Lib\Exceptions\Routes\RequestHandlerInvalidResponseException;

final class ResponseBuilder
{
    /** @param mixed[] $args */
    public static function __callStatic(string $method, array $args = []): ResponseInterface
    {
        $build = new self();
        return $build->$method(...$args) ?? new EmptyResponse();
    }

    /** @throws RequestHandlerInvalidResponseException */
    private function make(mixed $response, bool $notFound = false): ResponseInterface
    {
        if ($notFound === true) {
            return new TextResponse('Not Found', 404);
        }

        if ($response === null) {
            return new EmptyResponse();
        }
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        if (isHTML($response)) {
            return new HtmlResponse($response);
        }

        if (is_string($response)) {
            return new TextResponse($response);
        }

        if (is_array($response)) {
            return new JsonResponse($response);
        }

        //returning collections and entities, models ...

        throw new RequestHandlerInvalidResponseException('Request Handler Invalid Response', code: 500);
    }
}
