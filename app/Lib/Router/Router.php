<?php

declare(strict_types=1);

namespace App\Lib\Router;

use App\Lib\Enums\HttpMethod;
use App\Lib\Application\Support\ResponseBuilder;
use App\Lib\Attributes\Routes\Route;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Lib\Attributes\Middlewares\Middleware;
use App\Lib\Exceptions\Routes\RequestHttpMethodUnSupportedException;

/**
 * Router Implementation for handling request routing
 * @author KhalidElMasnaoui
 * @copyright (c)) 2024
 */
class Router implements RequestHandlerInterface
{
    /** @var array<string,array<string,array<string, mixed>>> */
    protected array $routes = [];

    /** @var array<string,string[]> */
    protected array $routeNames = [];

    protected string $currentGroupPrefix = '';

    /** @var class-string[]> */
    protected array $currentGroupMiddlewares = [];

    protected string $defaultNamespace = '';

    public function __construct(private ContainerInterface $container)
    {
    }

    /** @return array<string,array<string,array<string, mixed>>> */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /** @return array<string,string[]> */
    public function getRouteNames(): array
    {
        return $this->routeNames;
    }

    public function getDefaultNamespace(): string
    {
        return $this->defaultNamespace;
    }

    public function setDefaultNamespace(string $namespace): void
    {
        $this->defaultNamespace = str_ends_with($namespace, '\\') ? $namespace : "{$namespace}\\";
    }

    /** @return array{0:string, 1:string, 2:array<string|int>} */
    protected function parseRoute(string $path, bool $fromRequest = false): array
    {
        $path       = rtrim($path, '/');
        $path       =    str_starts_with($path, '/') ? $path : "/{$path}";

        $regex      = '';
        $parameters = [];

        //get dynamic and optional parameters
        if ($fromRequest === false) {
            if (str_contains($path, '{')) {
                if (!str_contains($path, '?}')) {
                    $regex = preg_replace('/{[^\/]+}/', '([^/]+)', $path);
                } else {
                    $regex = preg_replace('/{[^\/?]+}/', '([^/]+)', $path) ?? $path;
                    $regex = preg_replace('/\/{[^\/]+\?}/', '(/.+)?', $regex);
                }

                $regex   = str_replace('/', '\/', $regex); // @phpstan-ignore argument.type
                $regex   = "^$regex$";

                preg_match_all('/{([^\/\?]+)\??}/', $path, $matches);
                if (isset($matches[1]) && count($matches) > 0) { // @phpstan-ignore-line
                    $parameters = $matches[1];
                }
            } else {
                $regex = '^' . str_replace('/', '\/', $path) . '$';
            }
        }

        return [$path, $regex, $parameters];
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

    /** @return array{0:array<string,mixed>, 1:string[]|int[]} */
    protected function matchRoute(string $method, string $path): array
    {
        $route     = [];
        $arguments = [];
        if (isset($this->routes[$method][$path])) {
            $route = $this->routes[$method][$path];
        } else {
            // Check against all regex routes.
            foreach ($this->routes[$method] as $routePath => $routeData) {
                if (isset($routeData['regex']) && preg_match("/{$routeData['regex']}/", $path, $matches)) {
                    array_shift($matches); // Remove the full match
                    $matches   = array_map(fn ($m) => ltrim($m, '/'), $matches);
                    $route     = $routeData;
                    $arguments = array_combine($route['parameters'], array_pad($matches, count($route['parameters']), null));

                    break;
                }
            }
        }

        return [$route, $arguments];
    }

    /**
     * @param object|callable|string[] $handler
     * @param class-string[] $middlewares
     * @throws \RuntimeException
    */
    public function addRoute(string $method, string $path, object|callable|array $handler, array $middlewares = [], ?string $name = null): void
    {
        if (in_array($name, array_keys($this->routeNames))) {
            throw new \RuntimeException('Route name already in use', 500);
        }

        $method = $this->parseMethod($method);

        $path                       = $this->currentGroupPrefix . $path;
        [$path,$regex, $parameters] = $this->parseRoute($path);

        if (is_array($handler)) {
            $handler[0]                         = $this->defaultNamespace . ltrim($handler[0], '\\');
        }
        $middlewares                        = $middlewares === [] ? $this->currentGroupMiddlewares : $middlewares;

        $this->routes[$method][$path] = ['handler' => $handler, 'regex' => $regex, 'parameters' => $parameters, 'middlewares' => $middlewares];

        if (!empty($name)) {
            $this->routeNames[$name]                    = ['method' => $method, 'path' => $path];
            $this->routes[$method][$path]['name']       = $name;
        }
    }

    /** @param class-string[] $middlewares */
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
     * @param object|callable|string[] $handler
     * @param class-string[] $middlewares
    */
    public function get(string $path, object|callable|array $handler, array $middlewares = [], ?string $name = null): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares, $name);
    }

    /**
     * @param object|callable|string[] $handler
     * @param class-string[] $middlewares
    */
    public function post(string $path, object|callable|array $handler, array $middlewares = [], ?string $name = null): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares, $name);
    }

    /**
     * @param object|callable|string[] $handler
     * @param class-string[] $middlewares
    */
    public function put(string $path, object|callable|array $handler, array $middlewares = [], ?string $name = null): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares, $name);
    }

    /**
     * @param object|callable|string[] $handler
     * @param class-string[] $middlewares
    */
    public function delete(string $path, object|callable|array $handler, array $middlewares = [], ?string $name = null): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares, $name);
    }

    /**
     * @param class-string[] $middlewares
    */
    public function addMiddlewares(array $middlewares, ?string $routeName = null, string $method = null, ?string $path = null): void
    {
        $route = $this->getRoute($routeName, $method, $path);

        if ($route === null || $route['data'] === null) {
            // throw new \RuntimeException('No such route exist', 500);
            return;
        }
        $this->routes[$route['method']][$route['path']]['middlewares'] =  array_merge($route['data']['middlewares'], $middlewares);
    }

    /** @return null|array{method:string,path:string,data:mixed} */
    protected function getRoute(?string $routeName = null, string $method = null, ?string $path = null): array|null
    {
        if (!empty($routeName) && !in_array($routeName, array_keys($this->routeNames))) {
            $routeName = null;
        }

        if ($routeName === null && (empty($method) || $path === null)) {
            return null;
        }

        if (!empty($routeName)) {
            [$method, $path] = [$this->routeNames[$routeName]['method'], $this->routeNames[$routeName]['path']];
        } else {
            $method                                 = $this->parseMethod($method); // @phpstan-ignore argument.type
            [$path,,]                               = $this->parseRoute($path); // @phpstan-ignore argument.type
        }

        return ['method' => $method, 'path' => $path, 'data' => $this->routes[$method][$path] ?? null];
    }

    /** @param class-string[] $controllers */
    public function registerRoutesAndMiddlewaresFromControllerAttributes(array $controllers): void
    {
        foreach ($controllers as $controller) {
            $reflectionController = new \ReflectionClass($controller);

            $classRouteAttributes      = $reflectionController->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF);
            $classMiddlewareAttributes = $reflectionController->getAttributes(Middleware::class, \ReflectionAttribute::IS_INSTANCEOF);

            foreach ($classRouteAttributes as $attribute) {
                $route = $attribute->newInstance();

                $handler = $reflectionController->newInstance();
                $this->addRoute($route->method->value, $route->routePath, $handler, $route->middlewares, $route->name);
            }

            foreach ($classMiddlewareAttributes as $attribute) {
                $middleware = $attribute->newInstance();

                $this->addMiddlewares($middleware->middlewares, $middleware->routeName, $middleware->method?->value, $middleware->routePath);
            }

            foreach ($reflectionController->getMethods() as $method) {
                $methodRouteAttributes      = $method->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF);
                $methodMiddlewareAttributes = $method->getAttributes(Middleware::class, \ReflectionAttribute::IS_INSTANCEOF);

                foreach ($methodRouteAttributes as $attribute) {
                    $route = $attribute->newInstance();

                    $handler = [$controller, $method->getName()];
                    $this->addRoute($route->method->value, $route->routePath, $handler, $route->middlewares, $route->name);
                }

                foreach ($methodMiddlewareAttributes as $attribute) {
                    $middleware = $attribute->newInstance();

                    $this->addMiddlewares($middleware->middlewares, $middleware->routeName, $middleware->method?->value, $middleware->routePath);
                }
            }
        }
    }

    /** @param string[] $filenames */
    public function loadFrom(array $filenames): void
    {
        foreach ($filenames as $filename) {
            if (file_exists($filename)) {
                /** @var $router */
                require_once $filename;

                $currentRoutes = $this->getRoutes();
                $loadedRoutes  = $router->getRoutes();

                $newRoutes = [];
                foreach (HttpMethod::values() as $method) {
                    $paths = array_merge(array_keys($currentRoutes[$method] ?? []), array_keys($loadedRoutes[$method] ?? []));

                    foreach ($paths as $path) {
                        $newRoutes[$method][$path] = array_merge($currentRoutes[$method][$path] ?? [], $loadedRoutes[$method][$path] ?? []);
                    }
                }

                $this->routes = $newRoutes; // @phpstan-ignore assign.propertyType
            }
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        [$path,,]                        = $this->parseRoute($request->getUri()->getPath(), true);
        $method                          = $this->parseMethod($request->getMethod());

        [$route,$arguments]     = $this->matchRoute($method, $path);

        $handler = $route['handler'] ?? [];

        $arguments = ['request' => $request, ...$arguments];

        return $this->dispatch($handler, $arguments);
    }

    /**
     * @param object|callable|string[] $handler
     * @param mixed[] $args
    */
    protected function dispatch(object|callable|array $handler, array $args = []): ResponseInterface
    {
        $notFound = true;

        $controllerResponse = '';
        if (is_callable($handler)) {
            $controllerResponse = call_user_func($handler, $args);
            $notFound           = false;
        } else {
            if (empty($handler) || !is_array($handler)) {
                $handler = ['', ''];
            }
            [$class, $method] = $handler;

            if (class_exists($class)) {
                $class = $this->container->get($class);

                if (method_exists($class, $method)) {
                    $controllerResponse = call_user_func_array([$class, $method], $args); // @phpstan-ignore argument.type
                    $notFound           = false;
                }
            }
        }
        return ResponseBuilder::make($controllerResponse, $notFound); // @phpstan-ignore method.staticCall
    }
}
