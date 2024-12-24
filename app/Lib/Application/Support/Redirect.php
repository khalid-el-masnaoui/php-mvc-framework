<?php

namespace App\Lib\Application\Support;

use App\Lib\Router\Router;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Exception;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;

class Redirect extends Response
{
    public function __construct()
    {
    }

    /**
     * @param string|UriInterface $redirectLink
     * @param array<int,string|int> $headers
    */
    public function redirect(string|UriInterface $redirectLink, int $status = 302, array $headers = []): Redirect
    {
        if (!is_string($redirectLink) && !$redirectLink instanceof UriInterface) { // @phpstan-ignore booleanAnd.alwaysFalse
            throw new Exception\InvalidArgumentException(sprintf(
                'Uri provided to %s MUST be a string or Psr\Http\Message\UriInterface instance; received "%s"',
                self::class,
                get_debug_type($redirectLink)
            ));
        }

        $headers['location'] = [(string) $redirectLink];
        parent::__construct('php://temp', $status, $headers);

        return $this;
    }

    public function back(): Redirect
    {
        return $this->redirect($_SERVER['HTTP_REFERER'] ?? '');
    }

    // public function route(string $routeName): Redirect
    // {
    //     $path = app()->get(Router::class)->getRoute($routeName);
    //     return $this->redirect($_SERVER['HTTP_REFERER'] ?? '');
    // }
}
