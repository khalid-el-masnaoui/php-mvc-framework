<?php

declare(strict_types=1);

namespace App\Lib\Dispatcher\Matchers;

use Psr\Http\Message\ServerRequestInterface;

interface MatcherInterface
{
    /** Evaluate if the request matches with the condition */
    public function __invoke(ServerRequestInterface $request): bool;
}
