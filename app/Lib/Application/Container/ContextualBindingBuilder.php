<?php

declare(strict_types=1);

namespace App\Lib\Application\Container;

use App\Lib\Application\Container\AppContainerInterface;

final class ContextualBindingBuilder
{
    protected AppContainerInterface $container;

    protected string $concrete;

    protected string $needs;

    public function __construct(AppContainerInterface $container, string $concrete)
    {
        $this->concrete  = $concrete;
        $this->container = $container;
    }

    public function needs(string $abstract): static
    {
        $this->needs = $abstract;

        return $this;
    }

    /** @param callable|string $implementation */
    public function give(callable|string $implementation): void
    {
        $this->container->addContextualBinding(
            $this->concrete,
            $this->needs,
            $implementation
        );
    }
}
