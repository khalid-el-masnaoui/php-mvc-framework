<?php

declare(strict_types=1);

namespace App\Lib\Psr15\Dispatcher\Matchers;

use Psr\Http\Message\ServerRequestInterface;

class Pattern implements MatcherInterface
{
    use NegativeResultTrait;

    private string $pattern;
    private int $flags;

    public function __construct(string $pattern, int $flags = 0)
    {
        $this->pattern = $this->getValue($pattern);
        $this->flags   = $flags;
    }

    public function __invoke(ServerRequestInterface $request): bool
    {
        return fnmatch($this->pattern, $request->getUri()->getPath(), $this->flags) === $this->result;
    }
}
