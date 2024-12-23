<?php

declare(strict_types=1);

namespace App\Lib\DiContainer;

use Psr\Container\ContainerInterface;
use App\Lib\Exceptions\DiContainer\DiContainerDuplicateDefinitionException;
use App\Lib\Exceptions\DiContainer\DiContainerDependencyResolutionException;

class DiContainer implements DiContainerInterface
{
    /** @var array<string,array<string, mixed>> */
    protected array $definitions = [];

    /** @var array<string|int,string[]> */
    protected array $types = [];

    /** @var array<string,mixed> */
    protected array $instances = [];

    /** @var array<string,ContainerInterface> */
    protected array $delegates = [];

    /** @var mixed[] */
    protected array $parameters = [];

    protected bool $useDeepTypeResolution;

    public function __construct()
    {
        $this->set('DI', $this);
        $this->set(ContainerInterface::class, $this);
        $this->delegates['reflectionContainer'] = new Container();
    }

    /**
     * Enable/disable deep type resolution on or off. By default, this feature
     * is disabled. If enabled, when Di can't find a definition for a particular
     * type, it will attempt to instantiate a new instance of the type
     * automatically and use this instance for injection.
     *
     */
    public function setDeepTypeResolution(bool $status): void
    {
        $this->useDeepTypeResolution = $status;
    }

    /** @inheritDoc */
    public function setParameters(array $parameters): ContainerInterface
    {
        if (empty($this->parameters)) {
            $this->parameters = $parameters;
        }
        return $this;
    }

    public function getParameter(string $key): mixed
    {
        if (isset($this->parameters[$key]) || array_key_exists($key, $this->parameters)) {
            return $this->parameters[$key];
        } else {
            return null;
        }
    }

    /**
     * Setup a container definition. By default, all definitions are singletons,
     * but this can be changed via the $singleton aregument. Additionally, an
     * optional fourth argument, $type, can be specified to assist with type-
     * based injection. If no type is specified, then the container can't use
     * the definition for type-based injection unless the definition is simply
     * an object (as opposed to a Closure). This is because the container won't
     * know what type of of instance the definition will create.
     * @throws DiContainerDuplicateDefinitionException
     */
    public function set(string $key, mixed $definition, bool $singleton = true, ?string $type = null): ContainerInterface
    {
        if (!isset($this->definitions[$key])) {
            $this->definitions[$key] = [
                'singleton'        => $singleton,
                'definition'       => $definition
            ];

            // if the definition is an instance, use it's types
            $class = !is_string($definition) ? get_class($definition) : $definition;
            if ($class !== 'Closure') {
                $type         = $class;
                $reflection   = new \ReflectionClass($class); // @phpstan-ignore argument.type

                $types = $reflection->getInterfaceNames();
                foreach ($types as $interface) {
                    if (!isset($this->types[$interface])) {
                        $this->types[$interface] = [];
                    }
                    $this->types[$interface][] = $key;
                }
            }

            // if $type is specified, set the definition type
            if (!is_null($type)) {
                $this->definitions[$key]['type'] = $type;

                if (!isset($this->types[$type])) {
                    $this->types[$type] = [];
                }

                $this->types[$type][] = $key;
            }
        } else {
            throw new DiContainerDuplicateDefinitionException("Duplicate definition for {$key}");
        }

        return $this;
    }

    public function get(string $key): ?object
    {
        if (!isset($this->definitions[$key])) {
            // check if the key is delegated to another container
            foreach ($this->delegates as $secondaryContainer) {
                try {
                    $reflectionContainer = $secondaryContainer->get($key);
                    if ($reflectionContainer) {
                        return $reflectionContainer;
                    }
                } catch (\Throwable $e) {
                    return null;
                }
            }

            return null;
        }

        $resource    = $this->definitions[$key];
        $return      = null;

        if ($resource['singleton'] === true && isset($this->instances[$key])) {
            $return = $this->instances[$key];
        } else {
            if (is_callable($resource['definition'])) {
                $return = $this->injectFunction($resource['definition'], ['di' => '%DI']);
                if ($resource['singleton'] === true) {
                    $this->instances[$key] = $return;
                }
            } else {
                $return = $resource['definition'];
            }
        }

        if (is_string($return)) {
            $return = $this->get($return);
        }

        return $return;
    }

    public function has(string $key): bool
    {
        // if key is delegated, ask delegate container
        foreach ($this->delegates as $secondaryContainer) {
            return $secondaryContainer->has($key);
        }
        return isset($this->definitions[$key]);
    }

    public function delegate(string $secondaryContainer, ContainerInterface $container): ContainerInterface
    {
        $this->delegates[$secondaryContainer] = $container;
        return $this;
    }

    /** @return array<string,mixed> */
    public function find(string $prefix): array
    {
        $results = [];
        foreach ($this->definitions as $key => $resource) {
            if (strpos($key, $prefix) === 0) {
                $results[$key] = $this->get($key);
            }
        }
        return $results;
    }

    /**
     * @param array<string,mixed> $args
     * @return mixed[]
     * @throws DiContainerDependencyResolutionException
     */
    protected function resolveArguments(\ReflectionMethod|\ReflectionFunction $reflection, array $args = []): array
    {
        $invokeArgs = [];
        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();

            $type = null;
            try {
                $class = $param->getClass();
                if (!is_null($class)) {
                    $type = $class->getName();
                }
            } catch (\ReflectionException $e) {
                $words = explode(' ', $e->getMessage());
                throw new DiContainerDependencyResolutionException("Failed to resolve dependency {$words[1]}");
            }

            if (isset($args[$name]) || array_key_exists($name, $args)) {
                // resolve parameter based on supplied $args
                $invokeArgs[] = $this->resolveArgument($args[$name]);
            } elseif (!is_null($type) &&
                (class_exists($type) || interface_exists($type) || trait_exists($type))) {
                // resolve parameter based on type
                $invokeArgs[] = $this->resolveType($type);
            }
        }

        return $invokeArgs;
    }

    /**
     * @param mixed $arg
     * @return mixed
     */
    protected function resolveArgument(mixed $arg): mixed
    {
        /** recursively resolve the array entries */
        if (is_array($arg)) {
            foreach ($arg as $k => $v) {
                $arg[$k] = $this->resolveArgument($v);
            }
            $invokeArg = $arg;
        }

        /*
         * If the argument is a placeholder for dependency (i.e. "%service"),
         * then try and resolve the dependency
         */ elseif (is_string($arg) && strpos($arg, '%') === 0) {
            $ret = $this->get(substr($arg, 1));
            if ($ret !== null) {
                $invokeArg = $ret;
            } else {
                $invokeArg = $arg;
            }
        }

        /*
         * If the argument is a placeholder for a parameter (i.e. "%param"),
         * then try and resolve the parameter
         */ elseif (is_string($arg) && strpos($arg, ':') === 0) {
            $ret = $this->getParameter(substr($arg, 1));
            if ($ret !== null) {
                $invokeArg = $ret;
            } else {
                $invokeArg = $arg;
            }
        }

        /*
         * If the argument is a class name (i.e. starts with a '\'), then try
         * and resolve the type
         */ elseif (is_string($arg) && strpos($arg, '\\') === 0) {
            try {
                /** @var class-string $arg */
                $invokeArg = $this->resolveType($arg);
            } catch (DiContainerDependencyResolutionException $e) {
                $invokeArg = $arg;
            }
        }

        /*
         * The argument is just a regular string, so do nothing
         */ else {
            $invokeArg = $arg;
        }

        return $invokeArg;
    }

    /**
     * Given a type (class), try and resolve the type using dependencies that
     * have been registered with the container. If that fails, try and
     * instantiate a new instance of the class instead.
     *
     * @param class-string $type
     * @return object
     * @throws DiContainerDependencyResolutionException
     */
    public function resolveType(string $type)
    {
        $object = null;

        // strip leading slashes
        $type = ltrim($type, '\\');

        // resolution can only happen if one definition exists for the type
        if (isset($this->types[$type])) {
            if (count($this->types[$type]) === 1) {
                $def    = $this->types[$type][0];
                $object = $this->get($def);
            }
        } elseif ($this->useDeepTypeResolution) {
            $object = $this->injectConstructor($type);
        }

        if (is_null($object)) {
            throw new DiContainerDependencyResolutionException("Failed to resolve dependency {$type}");
        }

        return $object;
    }

    /**
     * {@inheritDoc}
     */
    public function injectStaticMethod(string $class, string $method, array $args = []): mixed
    {
        $invokeArgs = $this->resolveArguments(new \ReflectionMethod($class, $method), $args);
        return call_user_func_array([$class, $method], $invokeArgs); // @phpstan-ignore argument.type
    }

    /**
     * {@inheritDoc}
     */
    public function injectConstructor(string $class, array $args = []): object
    {
        $reflection = new \ReflectionClass($class);
        if ($reflection->getConstructor() !== null) {
            $invokeArgs = $this->resolveArguments($reflection->getConstructor(), $args);
            return $reflection->newInstanceArgs($invokeArgs);
        } else {
            return new $class();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function injectMethod(object $instance, string $method, array $args = []): mixed
    {
        $invokeArgs = $this->resolveArguments(new \ReflectionMethod($instance, $method), $args);
        return call_user_func_array([$instance, $method], $invokeArgs); // @phpstan-ignore argument.type
    }

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     */
    public function injectFunction(mixed $function, array $args = []): mixed
    {
        $functionDoesntExist = !is_object($function) && !function_exists($function);
        $objectIsNotClosure  = is_object($function) && !($function instanceof \Closure);

        if ($functionDoesntExist && $objectIsNotClosure) {
            throw new \InvalidArgumentException(
                '$function expects name of existing function or a Closure'
            );
        }

        $invokeArgs = $this->resolveArguments(new \ReflectionFunction($function), $args);
        return call_user_func_array($function, $invokeArgs);
    }
}
