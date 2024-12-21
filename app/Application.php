<?php

declare(strict_types=1);

namespace App;

use App\Kernels\Kernel;
use App\Lib\Dispatcher\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

class Application
{
    private Dispatcher $dispatcher;

    private EmitterInterface $responseEmitter;

    public function __construct(
        protected ServerRequestInterface $request
    ) {
    }

    public function withRouting(string $routesFile = ''): static
    {
        if (file_exists($routesFile)) {
            Kernel::getRouter($routesFile);
        }

        return $this;
    }

    public function boot(): static
    {
        $this->dispatcher      = Kernel::getDispatcher();
        $this->responseEmitter = Kernel::getResponseEmitter();
        return $this;
    }

    public function run(): void
    {
        $this->responseEmitter->emit($this->dispatcher->dispatch($this->request));
    }
}
