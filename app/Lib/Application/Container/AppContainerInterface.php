<?php

declare(strict_types=1);

namespace App\Lib\Application\Container;

use Psr\Container\ContainerInterface;
use App\Lib\Application\Container\ContextualBindingBuilder;

interface AppContainerInterface extends ContainerInterface
{
    public function bind(string $abstract, callable|string $concrete = null, bool $shared = false): AppContainerInterface;

    public function singleton(string $abstract, callable|string $concrete = null): AppContainerInterface;

    public function when(string $concrete): ContextualBindingBuilder;

    public function addContextualBinding(string $concrete, string $needs, callable|string $implementation): void;

    /** @param array<string,mixed> $args*/
    public function make(string $abstract, array $args = []): mixed;

    public function forgetInstance(string $abstract): void;

    public function forgetInstances(): void;
}
