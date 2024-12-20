<?php

declare(strict_types=1);

namespace Lib;

use App\Core\Enums\HttpMethod;
use Lib\Container;
use App\Core\Attributes\Routes\Route;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Kernels\Http\Handlers\NotFoundHandler;
use App\Core\Attributes\Middlewares\Middleware;
use App\Kernels\Http\Handlers\MethodNotAllowedHandler;
use App\Core\Exceptions\Routes\RequestHttpMethodUnSupported;

/**
 * Router Implementation for handling request routing
 * @author KhalidElMasnaoui
 * @copyright (c)) 2024
 */
class Router implements RequestHandlerInterface
{
    protected string $currentGroupPrefix = '';

    /** @var array<string,array<string,array<callable|object>>> $routes */
    protected array $routes;

    /** @var array<string,array<string,MiddlewareInterface[]>> $routeMiddlewares */
    protected array $routeMiddlewares;

    /** @var (int|string)[] $routeParameters */
    protected array $routeParameters = [];

    protected array $currentGroupMiddlewares = [];

    public function __construct(private ContainerInterface $container)
    {
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getRouteMiddlewares(): array
    {
        return $this->routeMiddlewares;
    }

    public function registerRoutesFromControllerAttributes(array $controllers)
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

    public function registerMiddlewaresFromControllerAttributes(array $controllers)
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

    /**
     * @param MiddlewareInterface[] $middlewares
     * @throws RequestHttpMethodUnSupported
    */
    public function addRoute(string $method, string $route, callable|array $handler, array $middlewares = []): void
    {
        $method = $this->parseMethod($method);
        if (!in_array($method, HttpMethod::values())) {
            throw new RequestHttpMethodUnSupported();
        }

        $route                         = $this->currentGroupPrefix . $route;
        $route                         = $this->parseRoute($route);
        $this->routes[$method][$route] = $handler;

        $middlewares = $middlewares === [] ? $this->currentGroupMiddlewares : $middlewares;
        $this->routeMiddlewares[$method][$route] = $middlewares;

    }

    public function addGroup(string $prefix, callable|array $callback, array $middlewares = []): void
    {
        $previousGroupPrefix      = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;
        $this->currentGroupMiddlewares = $middlewares;
        $callback($this);
        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentGroupMiddlewares = [];

    }

    public function get(string $route, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $route, $handler, $middlewares);
    }

    public function post(string $route, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $route, $handler, $middlewares);
    }

    public function put(string $route, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $route, $handler, $middlewares);
    }

    public function delete(string $route, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $route, $handler, $middlewares);
    }

    /** @param MiddlewareInterface[] $middlewares */
    public function addMiddlewares(string $method, string $route, array $middlewares): void
    {
        $method = $this->parseMethod($method);
        if (!in_array($method, HttpMethod::values())) {
            throw new RequestHttpMethodUnSupported();
        }

        $route                         = $this->currentGroupPrefix . $route;
        $route                         = $this->parseRoute($route);
        $this->routeMiddlewares[$method][$route] = array_merge($this->routeMiddlewares[$method][$route] ?? [], $middlewares);
    }

    protected function parseRoute(string $route): string
    {
        $route       = rtrim($route, '/');
        return str_starts_with($route, '/') ? $route : "/{$route}";
    }

    protected function parseMethod(string $method): string
    {
        return strtoupper(trim($method));
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // dd($this->routes);
        // dd($this->routeMiddlewares);
        // dd($request->getAttributes());

        $path       = $this->parseRoute($request->getUri()->getPath());
        $method     = $this->parseMethod($request->getMethod());

        // Check if the method exists in routes
        if (!isset($this->routes[$method])) {
            return (new MethodNotAllowedHandler())->handle($request);
        }

        // Try to match the route using exact matching
        $handler = $this->routes[$method][$path] ?? [];

        if ($handler !== []) {
            return $this->dispatch($request, $handler, []);
        }

        return  (new NotFoundHandler())->handle($request);
    }

    protected function dispatch(ServerRequestInterface $request, callable|array $handler, $args = []): ResponseInterface
    {
        array_unshift($args, $request);

        $controllerResponse = "";
        if (is_callable($handler)) {
            $controllerResponse = call_user_func($handler, $args);
        }

        [$class, $method] = $handler;

        if (class_exists($class)) {
            $class = $this->container->get($class);

            if (method_exists($class, $method)) {
                $controllerResponse = call_user_func_array([$class, $method], $args);
            }
        }

        return $this->constructResponse($controllerResponse) ?? (new NotFoundHandler())->handle($request);
    }

    private function constructResponse(mixed $controllerResponse): ?ResponseInterface
    {
        if ($controllerResponse instanceof ResponseInterface) {
            return $controllerResponse;
        }

        if ($this->isHTML($controllerResponse)) {
            return new HtmlResponse($controllerResponse);
        }

        if (is_string($controllerResponse)) {
            return new TextResponse($controllerResponse);
        }

        if (is_array($controllerResponse)) {
            return new JsonResponse($controllerResponse);
        }

        //returning collections and entities, models ...

        return null;
    }

    private function isHTML($text): bool
    {
        $processed = htmlentities($text);
        if ($processed === $text) {
            return false;
        }
        return true;
    }
}
