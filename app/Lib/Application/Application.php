<?php

declare(strict_types=1);

namespace App\Lib\Application;

use App\Kernels\Http\Kernel;
use App\Kernels\KernelInterface;
use App\Lib\Application\Traits\Singleton;
use App\Lib\Application\Container\AppContainer;

final class Application extends AppContainer
{
    use Singleton;

    private KernelInterface $kernel;

    /** @var string[] */
    private array $routesFiles;

    /** @param string $routesFiles */
    public function withRouting(string ...$routesFiles): static
    {
        $this->routesFiles = $routesFiles;

        return $this;
    }

    public function boot(): static
    {
        $this->kernel = Kernel::singleton()->boot($this->routesFiles);
        // $this->register();

        return $this;
    }

    public function run(): void
    {
        $this->kernel->dispatch();
    }
}
