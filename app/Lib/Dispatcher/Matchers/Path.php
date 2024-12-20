<?php

declare(strict_types=1);

namespace App\Lib\Dispatcher\Matchers;

use Psr\Http\Message\ServerRequestInterface;

class Path implements MatcherInterface
{
    use NegativeResultTrait;

    private string $path;

    public function __construct(string $path)
    {
        $this->path = rtrim($this->getValue($path), '/');
    }

    public function __invoke(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();

        return (($path === $this->path) || $path === $this->path . '/') === $this->result;
    }
}
