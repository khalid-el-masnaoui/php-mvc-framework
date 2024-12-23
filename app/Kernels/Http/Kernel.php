<?php

declare(strict_types=1);

namespace App\Kernels\Http;

use Closure;
use Dotenv\Dotenv;
use Noodlehaus\Config;
use App\Lib\Views\View;
use Middlewares\Whoops;
use Middlewares\Minifier;
use Middlewares\Shutdown;
use App\Lib\Router\Router;
use Middlewares\ContentType;
use Middlewares\GzipEncoder;
use Middlewares\ErrorHandler;
use App\Kernels\KernelInterface;
use App\Lib\Helpers\ClassFinder;
use Middlewares\ContentEncoding;
use App\Lib\DiContainer\Container;
use Psr\Container\ContainerInterface;
use App\Lib\Psr15\Dispatcher\Dispatcher;
use Psr\Http\Server\MiddlewareInterface;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use App\Lib\Psr15\Middlewares\ResponseTimeMiddleware;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use App\Lib\Psr15\Middlewares\Errors\CustomPlainFormatter;
use App\Lib\Psr15\Middlewares\Errors\CustomHtmlErrorFormatter;
use App\Lib\Psr15\Middlewares\Errors\CustomJsonErrorFormatter;

final class Kernel implements KernelInterface
{
    private static ?ContainerInterface $container = null;

    private static ?Config $config = null;

    private static ?Router $router = null;

    private static ?Dispatcher $dispatcher = null;

    private static ?EmitterInterface $responseEmitter = null;

    private static ?self $instance = null;

    public static function singleton(): KernelInterface
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /** @inheritDoc */
    public function boot(array $routesFiles = []): static
    {
        $this->getContainer();
        $this->getConfig();
        $this->getRouter($routesFiles);
        $this->getDispatcher();
        $this->getResponseEmitter();

        return $this;
    }

    public function dispatch(): void
    {
        $request         = ServerRequestFactory::fromGlobals();
        $this->getResponseEmitter()->emit($this->getDispatcher()->dispatch($request));
    }

    public static function getContainer(): ContainerInterface
    {
        if (self::$container !== null) {
            return self::$container;
        }

        $container = new Container();
        // $container->set(Config::class, concrete: Kernel::getConfig());

        self::$container = $container;

        return self::$container;
    }

    public static function getConfig(): Config
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();

        self::$config = new Config(__DIR__ . '/../../../config');

        return self::$config;
    }

    /** @param string[] $routesFiles */
    public static function getRouter(array $routesFiles = []): Router
    {
        if (self::$router !== null) {
            return self::$router;
        }

        self::$router = Kernel::getContainer()->get(Router::class);

        //load and register routes and their middlewares (from file and attributes)
        Kernel::registerRoutesAndMiddlewaresFromAttributes();
        self::$router->loadFrom($routesFiles);

        return self::$router;
    }

    public static function getDispatcher(): Dispatcher
    {
        if (self::$dispatcher !== null) {
            return self::$dispatcher;
        }

        self::$dispatcher = new Dispatcher(Kernel::getMiddlewares(), Kernel::getRouter());

        return self::$dispatcher;
    }

    public static function getResponseEmitter(): EmitterInterface
    {
        if (self::$responseEmitter !== null) {
            return self::$responseEmitter;
        }

        self::$responseEmitter = new SapiEmitter();

        return self::$responseEmitter;
    }

    private static function registerRoutesAndMiddlewaresFromAttributes(): void
    {
        $composerLoader = require __DIR__ . '/../../../vendor/autoload.php';

        $finder = new ClassFinder($composerLoader);

        $controllerClasses = $finder->namespace('App\Kernels\Http\Controllers')->filterBy(function (\ReflectionClass $reflectionClass) {
            $classAttributes        = $reflectionClass->getAttributes();
            $classMethods           = $reflectionClass->getMethods();

            // dd($classAttributes);
            if (!empty($classAttributes)) {
                return true;
            }

            foreach ($classMethods as $method) {
                if (!empty($method->getAttributes())) {
                    return true;
                }
            }

            return false;

            /* custom logic goes here, must return true or false */
        })->search();

        /** @var class-string[] */
        $controllers = array_values($controllerClasses);

        Kernel::getRouter()->registerRoutesAndMiddlewaresFromControllerAttributes(
            $controllers
        );
    }

    /** @return array<string|Closure|MiddlewareInterface|array<string|bool|Closure|MiddlewareInterface>>  */
    private static function getMiddlewares(): array
    {
        $middlewares = [];

        $routeMiddlewares = Kernel::getRouter()->getRoutes();
        array_walk($routeMiddlewares, function ($routes, $httpMethod) use (&$middlewares): void {
            $httpMethod = strtolower($httpMethod);
            array_walk($routes, function ($registeredMiddlewares, $route) use (&$middlewares, $httpMethod): void {
                $registeredMiddlewares = $registeredMiddlewares['middlewares'];
                if (empty($registeredMiddlewares)) {
                    return;
                }

                $middlewareConditions = [fn ($request): bool => strtolower($request->getMethod()) === $httpMethod, $route];

                foreach ($registeredMiddlewares as $middleware) {
                    /** @var MiddlewareInterface */
                    $middleware          = new $middleware();
                    $middlewares[]       = [...$middlewareConditions, $middleware];
                }
            });
        });

        return [...Kernel::getGlobalMiddlewares(), ...$middlewares];
    }

    /** @return array<string|Closure|MiddlewareInterface|array<string|bool|Closure|MiddlewareInterface>> */
    private static function getGlobalMiddlewares(): array
    {
        $errorHandler =  [Kernel::getConfig()->get('environment')['debug'] === false, new ErrorHandler([
            new CustomJsonErrorFormatter(),
            new CustomHtmlErrorFormatter(),
            new CustomPlainFormatter(),
            new CustomPlainFormatter(),
        ])];

        $whoopsErrorHandler = [Kernel::getConfig()->get('environment')['debug'] === true, new Whoops()];

        $maintenanceMiddleware = [Kernel::getConfig()->get('environment')['maintenance'] === true, (new Shutdown())->retryAfter(60 * 5)->render(fn () => View::make('maintenance'))];

        return [$errorHandler, $whoopsErrorHandler, $maintenanceMiddleware, new ResponseTimeMiddleware(), new GzipEncoder(), Minifier::html(),  Minifier::css(),  Minifier::js(), new ContentType(), new ContentEncoding()];
    }
}
