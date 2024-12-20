<?php


declare(strict_types=1);

namespace App\Kernels;

use App\Config;
use Dotenv\Dotenv;
use Middlewares\Whoops;
use Middlewares\Minifier;
use Middlewares\Shutdown;
use App\Lib\Router\Router;
use Middlewares\ContentType;
use Middlewares\GzipEncoder;
use Middlewares\ErrorHandler;
use App\Lib\Container\Container;
use Middlewares\ContentEncoding;
use App\Core\Services\Views\View;
use App\Lib\Dispatcher\Dispatcher;
use Psr\Container\ContainerInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use App\Kernels\Http\Middlewares\ResponseTimeMiddleware;
use App\Kernels\Http\Middlewares\Errors\CustomHtmlErrorFormatter;
use App\Kernels\Http\Middlewares\Errors\CustomJsonErrorFormatter;
use App\Kernels\Http\Middlewares\Errors\CustomPlainFormatter;

class Kernel
{
    private static ?ContainerInterface $container = null;

    private static ?Config $config = null;

    private static ?Router $router = null;

    private static ?Dispatcher $dispatcher = null;

    private static ?EmitterInterface $responseEmitter = null;


    public static function getContainer(): ContainerInterface
    {
        if (self::$container !== null) {
            return self::$container;
        }

        $container = new Container();
        $container->set(ContainerInterface::class, Container::class);

        self::$container = $container;


        return self::$container;
    }

    public static function getConfig(): Config
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $dotenv = Dotenv::createImmutable(__DIR__."/../../");
        $dotenv->load();

        self::$config = new Config($_ENV);

        return self::$config;
    }

    public static function getRouter(): Router
    {
        if (self::$router !== null) {
            return self::$router;
        }

        self::$router = Kernel::getContainer()->get(Router::class);

        //load and register routes and their middlewares (from file and attributes)
        Kernel::registerRoutesAndMiddlewaresFromAttributes();
        self::$router->loadFrom(__DIR__."/../../routes/http.php");


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
        #TODO
        $controllers = [
            'App\Kernels\Http\Controllers\HomeController'
        ];

        Kernel::getRouter()->registerRoutesFromControllerAttributes(
            $controllers
        );

        Kernel::getRouter()->registerMiddlewaresFromControllerAttributes(
            $controllers
        );

    }

    private static function getMiddlewares(): array
    {
        $middlewares = [];

        $routeMiddlewares = Kernel::getRouter()->getMiddlewares();
        array_walk($routeMiddlewares, function ($routes, $httpMethod) use (&$middlewares): void {

            $httpMethod = strtolower($httpMethod);
            array_walk($routes, function ($registeredMiddlewares, $route) use (&$middlewares, $httpMethod): void {
                if (empty($registeredMiddlewares)) {
                    return;
                }

                $middlewareConditions = [fn ($request): bool => strtolower($request->getMethod()) === $httpMethod, $route];

                foreach ($registeredMiddlewares as $middleware) {
                    $middlewares[] = [...$middlewareConditions, $middleware];
                }

            });
        });

        return [...Kernel::getGlobalMiddlewares(), ...$middlewares];
    }

    private static function getGlobalMiddlewares(): array
    {
        $errorHandler =  [Kernel::getConfig()->environment["debug"] === false , new ErrorHandler([
            new CustomJsonErrorFormatter(),
            new CustomHtmlErrorFormatter(),
            new CustomPlainFormatter(),
            new CustomPlainFormatter(),
        ])];

        $whoopsErrorHandler = [Kernel::getConfig()->environment["debug"] === true  ,new Whoops()];

        $maintenanceMiddleware = [Kernel::getConfig()->environment["maintenance"] === true, (new Shutdown())->retryAfter(60 * 5)->render(fn () => View::make('maintenance'))];

        return [$errorHandler, $whoopsErrorHandler, $maintenanceMiddleware, new ResponseTimeMiddleware(), new GzipEncoder(), Minifier::html(),  Minifier::css(),  Minifier::js(), new ContentType(), new ContentEncoding()];
    }
}
