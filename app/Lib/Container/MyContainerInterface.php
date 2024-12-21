<?php

declare(strict_types=1);

namespace App\Lib\Container;

use Psr\Container\ContainerInterface;

interface MyContainerInterface extends ContainerInterface
{
    /** @param mixed[] $parameters */
    public function setParameters(array $parameters): void;

    public function getParameter(string $key): mixed;

    public function set(string $key, mixed $definition, bool $singleton = false): self;

    public function delegate(string $prefix, ContainerInterface $container): self;

    /** @param mixed[] $args  */
    public function injectStaticMethod(string $class, string $method, array $args = []): mixed;

    /** @param mixed[] $args  */
    public function injectConstructor(string $class, array $args = []): object;

    /** @param mixed[] $args  */
    public function injectMethod(object $instance, string $method, array $args = []): mixed;

    /** @param mixed[] $args  */
    public function injectFunction(mixed $function, array $args = []): mixed;
}
