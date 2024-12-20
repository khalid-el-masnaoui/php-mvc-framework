<?php

declare(strict_types=1);

namespace Tests\Unit\Lib\Router;

use App\Lib\Router\Router;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Router::class)]
class RouterTest extends TestCase
{
    #[Test]
    public function itWorks(): void
    {
        $this->assertTrue(3 === 3);
    }
}
