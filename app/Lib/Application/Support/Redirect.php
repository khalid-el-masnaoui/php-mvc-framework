<?php

namespace App\Lib\Application\Support;

use Laminas\Diactoros\Exception;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class Redirect extends Response
{
    /**
     * @param string|UriInterface $redirectLink
     * @param array<int,string|int> $headers
    */
    public function redirect(string|UriInterface $redirectLink, int $status = 302, array $headers = []): ResponseInterface
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

    public function back(): ResponseInterface
    {
        return $this->redirect($_SERVER['HTTP_REFERER'] ?? '');
    }
}
