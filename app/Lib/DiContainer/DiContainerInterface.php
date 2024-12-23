<?php

declare(strict_types=1);

namespace App\Lib\DiContainer;

use Psr\Container\ContainerInterface;

interface DiContainerInterface extends ContainerInterface
{
    /** @param mixed[] $parameters */
    public function setParameters(array $parameters): ContainerInterface;

    public function getParameter(string $key): mixed;

    public function set(string $key, mixed $definition, bool $singleton = false): ContainerInterface;

    public function delegate(string $prefix, ContainerInterface $container): ContainerInterface;

    /**
     * @param class-string $class
     * @param mixed[] $args
    */
    public function injectStaticMethod(string $class, string $method, array $args = []): mixed;

    /**
     * @param class-string $class
     * @param mixed[] $args
    */
    public function injectConstructor(string $class, array $args = []): object;

    /** @param mixed[] $args  */
    public function injectMethod(object $instance, string $method, array $args = []): mixed;

    /** @param mixed[] $args  */
    public function injectFunction(mixed $function, array $args = []): mixed;
}
