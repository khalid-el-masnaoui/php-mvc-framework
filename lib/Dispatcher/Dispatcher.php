<?php

declare(strict_types=1);

namespace Lib\Dispatcher;

use App\Kernels\Http\Handlers\NotFoundHandler;
use Closure;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Kernels\Http\Middlewares\Dispatcher\Matchers\Path;

class Dispatcher implements MiddlewareInterface, RequestHandlerInterface
{
    /** @param MiddlewareInterface[]|string[]|array[]|Closure[] $middlewares */
    public function __construct(private array $middlewares, private RequestHandlerInterface $requestHandler = (new NotFoundHandler()), private ?ContainerInterface $container = null)
    {
    }

    /**
     * Magic method to execute the dispatcher as a callable
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->dispatch($request);
    }

    /**
     * Return the next available middleware in the stack. (FIFO Queue)
     * @return MiddlewareInterface|false
     */
    private function get(ServerRequestInterface $request): bool|MiddlewareInterface
    {
        $middleware = current($this->middlewares);
        next($this->middlewares);

        if ($middleware === false) {
            return $middleware;
        }

        if (is_array($middleware)) {
            $conditions = $middleware;
            $middleware = array_pop($conditions);

            foreach ($conditions as $condition) {
                if ($condition === true) {
                    continue;
                }

                if ($condition === false) {
                    return $this->get($request);
                }

                if (is_string($condition)) {
                    $condition = new Path($condition);
                } elseif (!is_callable($condition)) {
                    throw new InvalidArgumentException('Invalid matcher. Must be a boolean, string or a callable');
                }

                if (!$condition($request)) {
                    return $this->get($request);
                }
            }
        }

        if (is_string($middleware)) {
            if ($this->container === null) {
                throw new InvalidArgumentException(sprintf('No valid middleware provided (%s)', $middleware));
            }

            $middleware = $this->container->get($middleware);
        }

        if ($middleware instanceof Closure) {
            return self::createMiddlewareFromClosure($middleware);
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        throw new InvalidArgumentException(sprintf('No valid middleware provided (%s)', is_object($middleware) ? get_class($middleware) : gettype($middleware)));
    }

    /**
     * Dispatch the request, return a response.
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        reset($this->middlewares);

        return $this->handle($request);
    }

    /**
     * @see RequestHandlerInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->get($request);

        if ($middleware === false) {
            return $this->requestHandler->handle($request);
        }

        return $middleware->process($request, $this);
    }

    /**
     * @see MiddlewareInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $this->requestHandler = $next;
        return $this->dispatch($request);
    }

    private static function createMiddlewareFromClosure(Closure $handler): MiddlewareInterface
    {
        return new class ($handler) implements MiddlewareInterface {
            private $handler;

            public function __construct(Closure $handler)
            {
                $this->handler = $handler;
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
            {
                return call_user_func($this->handler, $request, $next);
            }
        };
    }
}
