<?php

declare(strict_types=1);

namespace App\Lib\Application\Container;

use App\Lib\Application\Container\AppContainerInterface;
use App\Lib\Application\Container\ContextualBindingBuilder;
use App\Lib\Exceptions\Container\AbstractNotFoundException;
use App\Lib\Exceptions\Container\ConcreteNotFoundException;
use App\Lib\Exceptions\Container\InterfaceNotBoundException;
use App\Lib\Exceptions\Container\UnresolvableDependencyException;
use App\Lib\Exceptions\Container\ConcreteNotInstantiableException;

class AppContainer implements AppContainerInterface
{
    /** @var array<string,array{resolver:callable,shared:bool}> */
    protected array $bindings = [];

    /** @var array<string,array<string,callable>> */
    protected array $contextualBindings = [];

    /** @var array<string,callable> */
    protected array $instances = [];

    /** @var string[] */
    protected array $buildStack = [];

    public function bind(string $abstract, callable|string $concrete = null, bool $shared = false): AppContainerInterface
    {
        if ($shared) {
            unset($this->instances[$abstract]);
        }

        // Mainly used to bind singletons, or just register a class to the container.
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $concrete = $this->wrapConcrete($concrete);

        $this->bindings[$abstract] = [
            'resolver' => $concrete,
            'shared'   => $shared,
        ];

        return $this;
    }

    public function singleton(string $abstract, callable|string $concrete = null): AppContainerInterface
    {
        return $this->bind($abstract, $concrete, true);
    }

    public function when(string $concrete): ContextualBindingBuilder
    {
        return new ContextualBindingBuilder($this, $concrete);
    }

    /** @param callable|string $implementation */
    public function addContextualBinding(string $concrete, string $needs, callable|string $implementation): void
    {
        $this->contextualBindings[$concrete][$needs] = $this->wrapConcrete($implementation);
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || $this->needsContextualBinding($abstract);
    }

    /**
     * @param array<string,mixed> $args
     * @throws InterfaceNotBoundException|AbstractNotFoundException
    */
    public function make(string $abstract, array $args = []): mixed
    {
        $this->buildStack[] = $abstract;

        // Try to automatically make an abstract even if it's not bound.
        if (!$this->has($abstract)) {
            if (interface_exists($abstract)) {
                throw new InterfaceNotBoundException("Interface {$abstract} is not bound to a concrete");
            }

            if (!class_exists($abstract)) {
                throw new AbstractNotFoundException("Abstract {$abstract} not found");
            }

            $object = $this->makeWithDependencies($abstract, $args);
        } else {
            $object = $this->isShared($abstract)
                ? $this->resolveSharedConcrete($abstract, $args)
                : $this->resolveConcrete($abstract, $args);
        }

        array_pop($this->buildStack);

        return $object;
    }

    public function get(string $abstract): mixed
    {
        return $this->make($abstract);
    }

    public function forgetInstance(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    public function forgetInstances(): void
    {
        $this->instances = [];
    }

    public function flush(): void
    {
        $this->bindings           = [];
        $this->instances          = [];
        $this->contextualBindings = [];
        $this->buildStack         = [];
    }

    /**
     * @throws ConcreteNotFoundException
     * @throws ConcreteNotInstantiableException
     */
    protected function wrapConcrete(callable|string $concrete): callable
    {
        if (is_callable($concrete)) {
            return $concrete;
        }

        // if(is_array($concrete)){

        // }

        if (!class_exists($concrete) && !interface_exists($concrete)) {
            throw new ConcreteNotFoundException("Concrete `$concrete` not found");
        }

        if (!$this->isInstantiable($concrete)) {
            throw new ConcreteNotInstantiableException("Concrete {$concrete} is not instantiable");
        }

        // By default, try auto resolving a concrete.
        return function (AppContainer $container, array $args) use ($concrete): object|null {
            return $container->makeWithDependencies($concrete, $args);
        };
    }

    /** @param  class-string $concrete */
    protected function isInstantiable(string $concrete): bool
    {
        $reflection = new \ReflectionClass($concrete);

        return $reflection->isInstantiable();
    }

    protected function isShared(string $abstract): bool
    {
        return !empty($this->bindings[$abstract]['shared']);
    }

    /** @param array<string,mixed> $args */
    protected function resolveSharedConcrete(string $abstract, array $args): mixed
    {
        if ($this->needsContextualBinding($abstract)) {
            return $this->resolveContextualConcrete($abstract, $args);
        }

        if (!isset($this->instances[$abstract])) {
            $resolve = $this->bindings[$abstract]['resolver'];

            $this->instances[$abstract] = $resolve($this, $args);
        }

        return $this->instances[$abstract];
    }

    /** @param array<string,mixed> $args */
    protected function resolveConcrete(string $abstract, array $args): mixed
    {
        if ($this->needsContextualBinding($abstract)) {
            return $this->resolveContextualConcrete($abstract, $args);
        }

        $resolve = $this->bindings[$abstract]['resolver'];

        return $resolve($this, $args);
    }

    /** @param array<string,mixed> $args */
    protected function resolveContextualConcrete(string $abstract, array $args): mixed
    {
        $resolve = $this->getContextualConcrete($abstract);

        return $resolve($this, $args);
    }

    /**
     * @param class-string $concrete
     * @param array<string,mixed> $args
     * @throws UnresolvableDependencyException
    */
    protected function makeWithDependencies(string $concrete, array $args): object|null
    {
        $dependencies      = $this->resolveDependencies($concrete);
        $finalDependencies = [];

        foreach ($dependencies as $dep) {
            // User-defined args.
            if (array_key_exists($dep->getName(), $args)) {
                $finalDependencies[] = $args[$dep->getName()];

                continue;
            }

            // Default constructor args.
            if ($dep->isDefaultValueAvailable()) {
                $finalDependencies[] = $dep->getDefaultValue();

                continue;
            }

            // Variadic args (...$args).
            if ($dep->isVariadic()) {
                $variadicDependency = $this->resolveVariadicDependency($dep);

                if (!is_null($variadicDependency)) {
                    $finalDependencies[] = $variadicDependency;
                }

                continue;
            }

            // Unresolvable dependency.
            if (!$this->isResolvableDependency($dep)) {
                throw new UnresolvableDependencyException("Dependency {$dep->getName()} cannot be resolved");
            }

            /** @var \ReflectionNamedType */
            $reflectionType      = $dep->getType();
            $finalDependencies[] = $this->make($reflectionType->getName());
        }

        $reflection = new \ReflectionClass($concrete);

        return $reflection->newInstanceArgs($finalDependencies);
    }

    /**
     * @param class-string $concrete
     * @return \ReflectionParameter[]

     */
    protected function resolveDependencies(string $concrete): array
    {
        $reflection  = new \ReflectionClass($concrete);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return [];
        }

        return $constructor->getParameters();
    }

    protected function resolveVariadicDependency(\ReflectionParameter $dep): mixed
    {
        /** @var \ReflectionNamedType */
        $reflectionType      = $dep->getType();
        return $this->isResolvableDependency($dep)
            ? $this->make($reflectionType->getName())
            : null;
    }

    protected function isResolvableDependency(\ReflectionParameter $dep): bool
    {
        $reflectionType      = $dep->getType();
        if ($reflectionType instanceof \ReflectionUnionType) {
            return false;
        }
        /** @var \ReflectionNamedType $reflectionType*/
        return $dep->hasType() && !$reflectionType->isBuiltin();
    }

    protected function needsContextualBinding(string $abstract): bool
    {
        return (bool) $this->getContextualConcrete($abstract);
    }

    protected function getContextualConcrete(string $abstract): mixed
    {
        return $this->contextualBindings[end($this->buildStack)][$abstract] ?? null;
    }
}
