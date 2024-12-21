<?php

declare(strict_types=1);

namespace Tests\Unit\Lib\Router;

use App\Core\Enums\EnumToArrayTrait;
use Mockery;
use App\Lib\Router\Router;
use PHPUnit\Framework\TestCase;
use App\Core\Attributes\Routes\Get;
use App\Core\Attributes\Routes\Route;
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Core\Attributes\Middlewares\GetMiddleware;
use App\Core\Attributes\Middlewares\Middleware;
use App\Kernels\Http\Middlewares\SetAttributesMiddleware;
use Psr\Http\Server\MiddlewareInterface;

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
    public function itRegistersRoutesFromControllersAttributes(): void
    {
        $controller = new class () {
            #[Get('/test')]
            public function index(): void
            {
            }
        };

        $this->router?->registerRoutesFromControllerAttributes(([$controller::class]));

        $expected = [
            'GET' => [
                '/test' => [
                    0 => $controller::class,
                    1 => 'index'
                ]
            ]
        ];

        $this->assertEquals($expected, $this->router?->getRoutes());
    }

    #[Test]
    public function itRegistersMiddlewaresFromControllersAttributes(): void
    {
        /** @var MiddlewareInterface */

        $controller = new class () {
            #[GetMiddleware('/test', [SetAttributesMiddleware::class])]
            public function index(): void
            {
            }
        };

        $this->router?->registerMiddlewaresFromControllerAttributes(([$controller::class]));

        $expected = [
            'GET' => [
                '/test' => [
                    0 => SetAttributesMiddleware::class,
                ]
            ]
        ];

        $this->assertEquals($expected, $this->router?->getMiddlewares());
    }
}
