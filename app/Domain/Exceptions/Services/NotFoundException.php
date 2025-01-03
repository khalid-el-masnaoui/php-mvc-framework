<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\Services;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
