<?php

declare(strict_types=1);

namespace App\Kernels;

interface KernelInterface
{
    public static function singleton(): KernelInterface;

    /** @param string[] $routesFiles */
    public function boot(array $routesFiles = []): KernelInterface;

    public function dispatch(): void;
}
