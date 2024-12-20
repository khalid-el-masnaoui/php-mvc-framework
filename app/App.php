<?php

declare(strict_types=1);

namespace App;

use App\Kernels\Kernel;
use Psr\Http\Message\ServerRequestInterface;
use App\Kernels\Http\Middlewares\Dispatcher\Dispatcher;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

class App
{
    private Dispatcher $dispatcher;

    private EmitterInterface $responseEmitter;


    public function __construct(
        protected ServerRequestInterface $request
    ) {
    }

    public function boot(): static
    {
        Kernel::getRouter();
        $this->dispatcher = Kernel::getDispatcher();
        $this->responseEmitter = Kernel::getResponseEmitter();
        return $this;
    }

    public function run(): void
    {
        $response = $this->dispatcher->dispatch($this->request);
        $this->responseEmitter->emit($response);
    }
}
