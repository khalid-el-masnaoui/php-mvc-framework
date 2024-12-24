<?php

declare(strict_types=1);

namespace App\Lib\Application;

use App\Kernels\Http\Kernel;
use App\Kernels\KernelInterface;
use App\Lib\Application\Container\AppContainer;
use App\Lib\Application\Container\AppContainerInterface;
use App\Lib\Application\Support\ServiceProviderInterface;

final class Application extends AppContainer implements AppContainerInterface
{
    private static ?Application $instance = null;

    private KernelInterface $kernel;

    /** @var string[] */
    private array $routesFiles;

    public static function getInstance(): Application
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /** @param string $routesFiles */
    public function withRouting(string ...$routesFiles): static
    {
        $this->routesFiles = $routesFiles;

        return $this;
    }

    public function boot(): static
    {
        $this->kernel = Kernel::singleton()->boot($this->routesFiles);
        $this->registerServiceProviders();
        return $this;
    }

    private function registerServiceProviders(): void
    {
        /** @suppress  PHP0441 */
        $providers = [];
        try {
            $providers = config('app', 'providers');
        } catch (\Exception) {
            $providers = [];
        }

        /** @var ServiceProviderInterface[] */
        $providers = is_string($providers) ? [] : $providers;

        array_unshift($providers, \App\Lib\Application\Container\AppServiceProvider::class);

        foreach ($providers as $class) {
            $provider = new $class($this);

            $provider->register();
            $provider->boot();
        }
    }

    public function run(): void
    {
        $this->kernel->dispatch();
    }
}
