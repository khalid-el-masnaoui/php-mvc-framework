<?php

declare(strict_types=1);

namespace Tests\Unit\Lib\Router;

use Mockery;
use App\Lib\Router\Router;
use PHPUnit\Framework\TestCase;
use App\Lib\Attributes\Routes\Get;
use App\Lib\Enums\EnumToArrayTrait;
use App\Lib\Attributes\Routes\Route;
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Lib\Attributes\Middlewares\Middleware;
use App\Lib\Attributes\Middlewares\GetMiddleware;
use App\Lib\Psr15\Middlewares\SetAttributesMiddleware;

#[CoversClass(Router::class)]
#[UsesClass(Route::class)]
#[UsesClass(Get::class)]
#[UsesClass(Middleware::class)]
#[UsesClass(GetMiddleware::class)]
#[UsesClass(EnumToArrayTrait::class)]
final class RouterTest extends TestCase
{
    private ?Router $router;

    protected function setUp(): void
    {
        /** @suppress  PHP0406 */

        /** @var ContainerInterface */
        $containerInterface                     = Mockery::mock(ContainerInterface::class);
        $this->router                           = new Router($containerInterface);
    }

    protected function tearDown(): void
    {
        $this->router = null;
    }

    #[Test]
    public function itRegistersRoutesAndMiddlewaresFromControllersAttributes(): void
    {
        $controller = new class () {
            #[Get('/test', 'testRoute')]
            #[GetMiddleware([SetAttributesMiddleware::class], 'testRoute')]
            public function index(): void
            {
            }
        };

        $this->router?->registerRoutesAndMiddlewaresFromControllerAttributes(([$controller::class]));

        $expectedHandler     = [0 => $controller::class, 1 => 'index'];
        $expectedMiddlewares = [SetAttributesMiddleware::class];

        $actualHandler     = $this->router?->getRoutes()['GET']['/test']['handler'];
        $actualMiddlewares = $this->router?->getRoutes()['GET']['/test']['middlewares'];

        $this->assertEquals($expectedHandler, $actualHandler);
        $this->assertEquals($expectedMiddlewares, $actualMiddlewares);
    }
}
