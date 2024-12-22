<?php

declare(strict_types=1);

namespace App\Lib\Exceptions\Views;

use Psr\Container\NotFoundExceptionInterface;

class ViewNotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
