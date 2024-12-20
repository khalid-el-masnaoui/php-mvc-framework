<?php

declare(strict_types=1);

namespace App\Core\Exceptions\Services;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
