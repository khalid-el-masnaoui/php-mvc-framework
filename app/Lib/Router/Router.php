<?php

declare(strict_types=1);

namespace App\Lib\Router;

use App\Core\Enums\HttpMethod;
use App\Lib\Utils\BuildResponse;
use App\Core\Attributes\Routes\Route;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Core\Attributes\Middlewares\Middleware;
use App\Core\Exceptions\Routes\RequestHttpMethodUnSupportedException;
use App\Core\Exceptions\Routes\RequestHandlerInvalidResponseException;

/**
 * Router Implementation for handling request routing
 * @author KhalidElMasnaoui
 * @copyright (c)) 2024
 */
class Router implements RequestHandlerInterface
{
    /** @var array<string,array<string,array<callable|object>>> $routes */
    protected array $routes;

    /** @var array<string,array<string,MiddlewareInterface[]>> $middlewares */
    protected array $middlewares;

    /** @var (int|string)[] $routeParameters */
    protected array $routeParameters = [];

    protected string $currentGroupPrefix = '';

    /** @var MiddlewareInterface[]> $currentGroupMiddlewares */
    protected array $currentGroupMiddlewares = [];

    protected string $defaultNamespace = '';

    public function __construct(private ContainerInterface $container)
    {
    }

    /** @return array<string,array<string,array<callable|object>>> */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /** @return array<string,array<string,MiddlewareInterface[]>>  */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getDefaultNamespace(): string
    {
        return $this->defaultNamespace;
    }

    public function setDefaultNamespace(string $namespace): void
    {
        $this->defaultNamespace = str_ends_with($namespace, '\\') ? $namespace : "{$namespace}\\";
    }

    /** @param class-string[] $controllers */
    public function registerRoutesFromControllerAttributes(array $controllers): void
    {
        foreach ($controllers as $controller) {
            $reflectionController = new \ReflectionClass($controller);

            foreach ($reflectionController->getMethods() as $method) {
                $attributes = $method->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF);

                foreach ($attributes as $attribute) {
                    $route = $attribute->newInstance();

                    $this->addRoute($route->method->value, $route->routePath, [$controller, $method->getName()]);
                }
            }
        }
    }

    /** @param class-string[] $controllers */
    public function registerMiddlewaresFromControllerAttributes(array $controllers): void
    {
        foreach ($controllers as $controller) {
            $reflectionController = new \ReflectionClass($controller);

            foreach ($reflectionController->getMethods() as $method) {
                $attributes = $method->getAttributes(Middleware::class, \ReflectionAttribute::IS_INSTANCEOF);

                foreach ($attributes as $attribute) {
                    $middleware = $attribute->newInstance();

                    $this->addMiddlewares($middleware->method->value, $middleware->routePath, $middleware->middlewares);
                }
            }
        }
    }

    public function loadFrom(string $filename): void
    {
        if (file_exists($filename)) {
            /** @var $router */
            require_once $filename;

            $currentRoutes = $this->getRoutes();
            $loadedRoutes  = $router->getRoutes();

            $currentMiddlewares = $this->getMiddlewares();
            $loadedMiddlewares  = $router->getMiddlewares();

            $newRoutes      = [];
            $newMiddlewares = [];
            foreach (HttpMethod::values() as $method) {
                $newRoutes[$method]  = array_merge($loadedRoutes[$method] ?? [], $currentRoutes[$method] ?? []);

                $paths = array_merge(array_keys($currentMiddlewares[$method] ?? []), array_keys($loadedMiddlewares[$method] ?? []));

                foreach ($paths as $path) {
                    $newMiddlewares[$method][$path]  = array_unique(array_merge($currentMiddlewares[$method][$path] ?? [], $loadedMiddlewares[$method][$path] ?? []), SORT_REGULAR);
                }
            }

            $this->routes      = $newRoutes;
            $this->middlewares = $newMiddlewares; // @phpstan-ignore assign.propertyType
        }
    }

    /**
     * @param string[] $handler
     * @param MiddlewareInterface[] $middlewares
    */
    public function addRoute(string $method, string $route, callable|array $handler, array $middlewares = []): void
    {
        $method = $this->parseMethod($method);

        $route                         = $this->currentGroupPrefix . $route;
        $route                         = $this->parseRoute($route);

        $handler[0]                    = $this->defaultNamespace . ltrim($handler[0], '\\');
        $this->routes[$method][$route] = $handler; // @phpstan-ignore assign.propertyType

        $middlewares                        = $middlewares === [] ? $this->currentGroupMiddlewares : $middlewares;
        $this->middlewares[$method][$route] = $middlewares;
    }

    /** @param MiddlewareInterface[] $middlewares */
    public function addGroup(string $prefix, callable $callback, array $middlewares = []): void
    {
        $previousGroupPrefix           = $this->currentGroupPrefix;
        $this->currentGroupPrefix      = $previousGroupPrefix . $prefix;
        $this->currentGroupMiddlewares = $middlewares;
        $callback($this);
        $this->currentGroupPrefix      = $previousGroupPrefix;
        $this->currentGroupMiddlewares = [];
    }

    /**
     * @param string[] $handler
     * @param MiddlewareInterface[] $middlewares
    */
    public function get(string $route, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $route, $handler, $middlewares);
    }

    /**
     * @param string[] $handler
     * @param MiddlewareInterface[] $middlewares
    */
    public function post(string $route, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $route, $handler, $middlewares);
    }

    /**
     * @param string[] $handler
     * @param MiddlewareInterface[] $middlewares
    */
    public function put(string $route, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $route, $handler, $middlewares);
    }

    /**
     * @param string[] $handler
     * @param MiddlewareInterface[] $middlewares
    */
    public function delete(string $route, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $route, $handler, $middlewares);
    }

    /** @param MiddlewareInterface[] $middlewares */
    public function addMiddlewares(string $method, string $route, array $middlewares): void
    {
        $method = $this->parseMethod($method);

        $route                              = $this->currentGroupPrefix . $route;
        $route                              = $this->parseRoute($route);
        $this->middlewares[$method][$route] = array_merge($this->middlewares[$method][$route] ?? [], $middlewares);
    }

    protected function parseRoute(string $route): string
    {
        $route       = rtrim($route, '/');
        return str_starts_with($route, '/') ? $route : "/{$route}";
    }

    /** @throws RequestHttpMethodUnSupportedException */
    protected function parseMethod(string $method): string
    {
        $parsedMethod =  strtoupper(trim($method));
        if (!in_array($parsedMethod, HttpMethod::values())) {
            throw new RequestHttpMethodUnSupportedException('Http Method Not Allowed', 405);
        }

        return $parsedMethod;
    }

    /** @throws RequestHttpMethodUnSupportedException|RequestHandlerInvalidResponseException */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // dd($this->routes);
        // dd($this->middlewares);
        // dd($request->getAttributes());

        $path       = $this->parseRoute($request->getUri()->getPath());
        $method     = $this->parseMethod($request->getMethod());

        // Try to match the route using exact matching
        $handler = $this->routes[$method][$path] ?? [];

        return $this->dispatch($handler, [$request]); // @phpstan-ignore argument.type
    }

    /**
     * @param string[] $handler
     * @param mixed[] $args
    */
    protected function dispatch(callable|array $handler, array $args = []): ResponseInterface
    {
        $found = true;

        $controllerResponse = '';
        if (is_callable($handler)) {
            $controllerResponse = call_user_func($handler, $args);
        }

        if (empty($handler)) {
            $found   = false;
            $handler = ['', ''];
        }

        [$class, $method] = $handler;

        if (class_exists($class)) {
            $class = $this->container->get($class);

            if (method_exists($class, $method)) {
                $controllerResponse = call_user_func_array([$class, $method], $args); // @phpstan-ignore argument.type
            }
        }

        return BuildResponse::get($controllerResponse, $found); // @phpstan-ignore method.staticCall
    }
}
