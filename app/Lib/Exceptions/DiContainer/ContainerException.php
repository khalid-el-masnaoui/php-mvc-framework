<?php

declare(strict_types=1);

namespace App\Lib\Exceptions\DiContainer;

use Psr\Container\ContainerExceptionInterface;

class ContainerException extends \Exception implements ContainerExceptionInterface
{
}
