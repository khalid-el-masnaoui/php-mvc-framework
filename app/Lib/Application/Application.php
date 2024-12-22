<?php

declare(strict_types=1);

namespace App\Lib\Application;

use App\Kernels\Kernel;
use App\Lib\Application\Traits\Singleton;
use App\Lib\Application\Container\AppContainer;

final class Application extends AppContainer
{
    use Singleton;

    private Kernel $kernel;

    private string $routesFile;

    public function withRouting(string $routesFile = ''): static
    {
        $this->routesFile = $routesFile;

        return $this;
    }

    public function boot(): static
    {
        $this->kernel = Kernel::singleton()->boot($this->routesFile);

        return $this;
    }

    public function run(): void
    {
        $this->kernel->dispatch();
    }
}
